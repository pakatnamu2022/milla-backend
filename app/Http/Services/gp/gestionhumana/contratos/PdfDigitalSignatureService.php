<?php

namespace App\Http\Services\gp\gestionhumana\contratos;

use RuntimeException;

/**
 * Incrusta una firma digital PKCS#7/X.509 dentro de un PDF ya generado por
 * dompdf, mediante una actualización incremental (ISO 32000-1 §7.5.6): agrega
 * los objetos /Sig, /Widget y /AcroForm y firma exactamente los bytes
 * declarados en /ByteRange usando openssl_pkcs7_sign — sin ninguna librería
 * de firma de PDF de terceros (TCPDF, etc.), igual a como lo hace el legacy
 * internamente, pero con OpenSSL nativo de PHP.
 *
 * Asume la estructura clásica que produce dompdf (xref tabular en texto, sin
 * cross-reference streams ni object streams).
 */
class PdfDigitalSignatureService
{
  private const RESERVE_BYTES = 8192;
  private const NUM_FIELD_WIDTH = 10;

  public function sign(string $pdf, string $certPath, string $keyPath, string $keyPassword, array $info = []): string
  {
    $maxObj = $this->findMaxObjectNumber($pdf);
    [$rootObjNum, $prevXref] = $this->findTrailerInfo($pdf);
    $rootBody = $this->findObjectBody($pdf, $rootObjNum);
    [$pageObjNum, $pageBody] = $this->findFirstPageObject($pdf);

    $sigObjNum = $maxObj + 1;
    $widgetObjNum = $maxObj + 2;
    $acroFormObjNum = $maxObj + 3;

    $hexLen = self::RESERVE_BYTES * 2;
    $name = $this->pdfString($info['Name'] ?? '');
    $location = $this->pdfString($info['Location'] ?? '');
    $reason = $this->pdfString($info['Reason'] ?? '');
    $contact = $this->pdfString($info['ContactInfo'] ?? '');
    $date = 'D:' . now()->format('YmdHis') . "+00'00'";

    $brPad = str_pad('0', self::NUM_FIELD_WIDTH);

    $sigObj = "{$sigObjNum} 0 obj\n<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /adbe.pkcs7.detached"
      . " /Name {$name} /M ({$date}) /Location {$location} /Reason {$reason} /ContactInfo {$contact}"
      . " /ByteRange [0 {$brPad} {$brPad} {$brPad}]"
      . " /Contents <" . str_repeat('0', $hexLen) . ">\n>>\nendobj\n";

    $contentsOpenOffsetInObj = strpos($sigObj, '/Contents <') + strlen('/Contents <') - 1;
    $br2OffsetInObj = strpos($sigObj, '/ByteRange [0 ') + strlen('/ByteRange [0 ');
    $br3OffsetInObj = $br2OffsetInObj + self::NUM_FIELD_WIDTH + 1;
    $br4OffsetInObj = $br3OffsetInObj + self::NUM_FIELD_WIDTH + 1;

    $widgetObj = "{$widgetObjNum} 0 obj\n<< /Type /Annot /Subtype /Widget /FT /Sig /Rect [0 0 0 0] /F 4"
      . " /P {$pageObjNum} 0 R /V {$sigObjNum} 0 R /T (Signature1) >>\nendobj\n";

    $acroFormObj = "{$acroFormObjNum} 0 obj\n<< /Fields [{$widgetObjNum} 0 R] /SigFlags 3 >>\nendobj\n";

    $newPageBody = $this->insertBeforeClosingDict($pageBody, "/Annots [{$widgetObjNum} 0 R]");
    $newPageObj = "{$pageObjNum} 0 obj\n{$newPageBody}\nendobj\n";

    $newRootBody = $this->insertBeforeClosingDict($rootBody, "/AcroForm {$acroFormObjNum} 0 R");
    $newRootObj = "{$rootObjNum} 0 obj\n{$newRootBody}\nendobj\n";

    $baseLen = strlen($pdf);
    $appendix = "\n";
    $offsets = [];

    $offsets[$rootObjNum] = $baseLen + strlen($appendix);
    $appendix .= $newRootObj;

    $offsets[$pageObjNum] = $baseLen + strlen($appendix);
    $appendix .= $newPageObj;

    $sigObjOffsetInAppendix = strlen($appendix);
    $offsets[$sigObjNum] = $baseLen + strlen($appendix);
    $appendix .= $sigObj;

    $offsets[$widgetObjNum] = $baseLen + strlen($appendix);
    $appendix .= $widgetObj;

    $offsets[$acroFormObjNum] = $baseLen + strlen($appendix);
    $appendix .= $acroFormObj;

    $xrefOffset = $baseLen + strlen($appendix);
    $newSize = $maxObj + 4;
    $xref = "xref\n";
    foreach ([$rootObjNum, $pageObjNum, $sigObjNum, $widgetObjNum, $acroFormObjNum] as $num) {
      $xref .= "{$num} 1\n" . sprintf("%010d %05d n \n", $offsets[$num], 0);
    }
    $xref .= "trailer\n<< /Size {$newSize} /Root {$rootObjNum} 0 R /Prev {$prevXref} >>\nstartxref\n{$xrefOffset}\n%%EOF";

    $buffer = $pdf . $appendix . $xref;

    $contentsOpenOffset = $baseLen + $sigObjOffsetInAppendix + $contentsOpenOffsetInObj;
    $contentsCloseOffset = $contentsOpenOffset + $hexLen + 2;
    $totalLen = strlen($buffer);

    $byteRange = [0, $contentsOpenOffset, $contentsCloseOffset, $totalLen - $contentsCloseOffset];

    $br2Abs = $baseLen + $sigObjOffsetInAppendix + $br2OffsetInObj;
    $br3Abs = $baseLen + $sigObjOffsetInAppendix + $br3OffsetInObj;
    $br4Abs = $baseLen + $sigObjOffsetInAppendix + $br4OffsetInObj;

    $buffer = substr_replace($buffer, str_pad((string) $byteRange[1], self::NUM_FIELD_WIDTH), $br2Abs, self::NUM_FIELD_WIDTH);
    $buffer = substr_replace($buffer, str_pad((string) $byteRange[2], self::NUM_FIELD_WIDTH), $br3Abs, self::NUM_FIELD_WIDTH);
    $buffer = substr_replace($buffer, str_pad((string) $byteRange[3], self::NUM_FIELD_WIDTH), $br4Abs, self::NUM_FIELD_WIDTH);

    $signedRangeContent = substr($buffer, 0, $contentsOpenOffset) . substr($buffer, $contentsCloseOffset);

    $derSignature = $this->computeDetachedSignature($signedRangeContent, $certPath, $keyPath, $keyPassword);
    $hexSignature = bin2hex($derSignature);

    if (strlen($hexSignature) > $hexLen) {
      throw new RuntimeException('La firma digital generada excede el espacio reservado en el PDF.');
    }

    $paddedHex = str_pad($hexSignature, $hexLen, '0');
    $buffer = substr_replace($buffer, $paddedHex, $contentsOpenOffset + 1, $hexLen);

    return $buffer;
  }

  private function computeDetachedSignature(string $content, string $certPath, string $keyPath, string $keyPassword): string
  {
    $tmpDir = sys_get_temp_dir();
    $inFile = tempnam($tmpDir, 'pdfsig_in_');
    $outFile = tempnam($tmpDir, 'pdfsig_out_');

    try {
      file_put_contents($inFile, $content);

      $ok = openssl_pkcs7_sign(
        $inFile,
        $outFile,
        'file://' . $certPath,
        ['file://' . $keyPath, $keyPassword],
        [],
        PKCS7_BINARY | PKCS7_DETACHED
      );

      if (!$ok) {
        throw new RuntimeException('No se pudo generar la firma PKCS#7: ' . openssl_error_string());
      }

      $raw = file_get_contents($outFile);

      return $this->extractDetachedSignatureDer($raw);
    } finally {
      @unlink($inFile);
      @unlink($outFile);
    }
  }

  /**
   * openssl_pkcs7_sign() en modo detached produce un mensaje MIME
   * "multipart/signed" (el contenido firmado + la firma en partes separadas
   * por boundary), no solo cabeceras+base64. Aquí se extrae específicamente
   * la parte "application/x-pkcs7-signature" y se decodifica su DER.
   */
  private function extractDetachedSignatureDer(string $raw): string
  {
    if (!preg_match('/boundary="([^"]+)"/', $raw, $bm)) {
      throw new RuntimeException('No se pudo interpretar la salida de la firma PKCS#7 (sin boundary).');
    }

    $boundary = $bm[1];
    $parts = explode('--' . $boundary, $raw);

    foreach ($parts as $part) {
      if (!preg_match('/^Content-Type:\s*application\/x-pkcs7-signature/mi', ltrim($part, "-\r\n"))) {
        continue;
      }

      $part = ltrim($part, "-\r\n");
      $sections = preg_split("/\r?\n\r?\n/", $part, 2);

      if (count($sections) !== 2) {
        continue;
      }

      $der = base64_decode(preg_replace('/\s+/', '', $sections[1]));

      if ($der !== false && $der !== '') {
        return $der;
      }
    }

    throw new RuntimeException('No se encontró la firma PKCS#7 en la salida generada.');
  }

  private function findMaxObjectNumber(string $pdf): int
  {
    preg_match_all('/(\d+)\s+0\s+obj/', $pdf, $m);

    if (empty($m[1])) {
      throw new RuntimeException('El PDF no contiene objetos válidos.');
    }

    return max(array_map('intval', $m[1]));
  }

  /** @return array{0:int,1:int} [rootObjNum, prevXrefOffset] */
  private function findTrailerInfo(string $pdf): array
  {
    if (!preg_match('/trailer\s*<<(.*?)>>/s', $pdf, $tm)) {
      throw new RuntimeException('No se encontró el trailer del PDF.');
    }

    if (!preg_match('/\/Root\s+(\d+)\s+0\s+R/', $tm[1], $rm)) {
      throw new RuntimeException('No se encontró /Root en el trailer del PDF.');
    }

    if (!preg_match('/startxref\s*(\d+)/', $pdf, $sx)) {
      throw new RuntimeException('No se encontró startxref en el PDF.');
    }

    return [(int) $rm[1], (int) $sx[1]];
  }

  private function findObjectBody(string $pdf, int $objNum): string
  {
    if (!preg_match('/(?:^|\n)' . $objNum . '\s+0\s+obj\s*(.*?)\s*endobj/s', $pdf, $m)) {
      throw new RuntimeException("No se encontró el objeto {$objNum} del PDF.");
    }

    return trim($m[1]);
  }

  /** @return array{0:int,1:string} [pageObjNum, pageBody] */
  private function findFirstPageObject(string $pdf): array
  {
    preg_match_all('/(\d+)\s+0\s+obj\s*(.*?)\s*endobj/s', $pdf, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
      if (preg_match('/\/Type\s*\/Page(?=[\s>])/', $m[2])) {
        return [(int) $m[1], trim($m[2])];
      }
    }

    throw new RuntimeException('No se encontró ninguna página en el PDF.');
  }

  private function insertBeforeClosingDict(string $body, string $insertion): string
  {
    if (!str_ends_with($body, '>>')) {
      throw new RuntimeException('Estructura de diccionario PDF inesperada.');
    }

    return substr($body, 0, -2) . " {$insertion} >>";
  }

  private function pdfString(string $value): string
  {
    $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);

    return '(' . $escaped . ')';
  }
}
