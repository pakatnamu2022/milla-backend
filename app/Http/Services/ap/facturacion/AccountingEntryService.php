<?php

namespace App\Http\Services\ap\facturacion;

use App\Models\ap\configuracionComercial\vehiculo\ApClassArticleAccountMapping;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingEntryService
{
  /**
   * Genera el siguiente número de asiento correlativo
   * Usa lockForUpdate para evitar race conditions
   */
  public function getNextAsientoNumber(): int
  {
    return DB::connection('dbtp')->transaction(function () {
      $max = DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->lockForUpdate()
        ->max('Asiento');

      return $max ? ($max + 1) : 1;
    }, 5); // 5 reintentos
  }

  /**
   * Genera todas las líneas de detalle del asiento para un documento
   */
  public function generateAccountingLines(ElectronicDocument $document, int $asientoNumber): array
  {
    $lines = [];
    $lineNumber = 1;
    $sedeDynCode = $this->getSedeDynCode($document);
    $classId = $this->getClassIdFromDocument($document);

    // Validar que existan mapeos de cuentas
    $this->validateAccountMapping($classId);

    foreach ($document->items as $item) {
      $accountLines = $this->processItem($item, $sedeDynCode, $asientoNumber, $lineNumber, $classId);
      $lines = array_merge($lines, $accountLines);
      $lineNumber += count($accountLines);
    }

    // Validar balance antes de retornar
    $this->validateBalance($lines);

    return $lines;
  }

  /**
   * Procesa un item de factura y genera sus líneas contables
   */
  protected function processItem(
    ElectronicDocumentItem $item,
    string                 $sedeDynCode,
    int                    $asientoNumber,
    int                    &$lineNumber,
    int                    $classId
  ): array
  {
    $lines = [];

    // Obtener mapeos de cuentas para esta clase de artículo
    $priceMapping = ApClassArticleAccountMapping::where('ap_class_article_id', $classId)
      ->where('account_type', 'PRECIO')
      ->where('status', true)
      ->first();

    $discountMapping = ApClassArticleAccountMapping::where('ap_class_article_id', $classId)
      ->where('account_type', 'DESCUENTO')
      ->where('status', true)
      ->first();

    if (!$priceMapping) {
      throw new Exception("No existe mapeo de cuenta PRECIO para class_id={$classId}");
    }

    if (!$discountMapping) {
      throw new Exception("No existe mapeo de cuenta DESCUENTO para class_id={$classId}");
    }

    $valorUnitario = (float)$item->valor_unitario;
    $descuento = (float)($item->descuento ?? 0);

    // Línea 1: Cuenta Origen Precio (según is_debit_origin)
    if ($valorUnitario > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $priceMapping->getFullAccountOrigin($sedeDynCode),
        'Debito' => $priceMapping->is_debit_origin ? round($valorUnitario, 2) : 0.00,
        'Credito' => $priceMapping->is_debit_origin ? 0.00 : round($valorUnitario, 2),
        'Descripcion' => 'Reversion precio unitario',
      ];
    }

    // Línea 2: Cuenta Origen Descuento (según is_debit_origin) - solo si hay descuento
    if ($descuento > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $discountMapping->getFullAccountOrigin($sedeDynCode),
        'Debito' => $discountMapping->is_debit_origin ? round($descuento, 2) : 0.00,
        'Credito' => $discountMapping->is_debit_origin ? 0.00 : round($descuento, 2),
        'Descripcion' => 'Reversion descuento',
      ];
    }

    // Línea 3: Cuenta Destino Precio (inverso a origen)
    if ($valorUnitario > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $priceMapping->getFullAccountDestination($sedeDynCode),
        'Debito' => $priceMapping->is_debit_origin ? 0.00 : round($valorUnitario, 2),
        'Credito' => $priceMapping->is_debit_origin ? round($valorUnitario, 2) : 0.00,
        'Descripcion' => 'Balance precio',
      ];
    }

    // Línea 4: Cuenta Destino Descuento (inverso a origen) - solo si hay descuento
    if ($descuento > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $discountMapping->getFullAccountDestination($sedeDynCode),
        'Debito' => $discountMapping->is_debit_origin ? 0.00 : round($descuento, 2),
        'Credito' => $discountMapping->is_debit_origin ? round($descuento, 2) : 0.00,
        'Descripcion' => 'Balance descuento',
      ];
    }

    return $lines;
  }

  /**
   * Obtiene el class_id desde el documento electrónico
   */
  protected function getClassIdFromDocument(ElectronicDocument $document): int
  {
    if (!$document->vehicleMovement) {
      throw new Exception('El documento no tiene vehicleMovement asociado');
    }

    $vehicle = $document->vehicleMovement->vehicle;
    if (!$vehicle) {
      throw new Exception('El vehicleMovement no tiene vehículo asociado');
    }

    $model = $vehicle->model;
    if (!$model || !$model->class_id) {
      throw new Exception('El vehículo no tiene modelo o class_id definido');
    }

    return $model->class_id;
  }

  /**
   * Obtiene el código Dynamics de la sede emisora
   */
  protected function getSedeDynCode(ElectronicDocument $document): string
  {
    if (!$document->seriesModel || !$document->seriesModel->sede) {
      throw new Exception('El documento no tiene serie o sede asociada');
    }

    $dynCode = $document->seriesModel->sede->dyn_code;
    if (empty($dynCode)) {
      throw new Exception('La sede no tiene dyn_code definido');
    }

    return $dynCode;
  }

  /**
   * Valida que la suma de débitos sea igual a la suma de créditos
   */
  public function validateBalance(array $lines): void
  {
    $totalDebitos = array_sum(array_column($lines, 'Debito'));
    $totalCreditos = array_sum(array_column($lines, 'Credito'));

    // Tolerancia de 0.01 por redondeos
    if (abs($totalDebitos - $totalCreditos) > 0.01) {
      $message = sprintf(
        'Balance contable inválido: Débitos=%.2f, Créditos=%.2f, Diferencia=%.2f',
        $totalDebitos,
        $totalCreditos,
        abs($totalDebitos - $totalCreditos)
      );

      Log::error('Validación de balance falló', [
        'debitos' => $totalDebitos,
        'creditos' => $totalCreditos,
        'diferencia' => abs($totalDebitos - $totalCreditos),
        'lines' => $lines,
      ]);

      throw new Exception($message);
    }

    Log::info('Balance contable validado correctamente', [
      'debitos' => $totalDebitos,
      'creditos' => $totalCreditos,
      'total_lines' => count($lines),
    ]);
  }

  /**
   * Valida que existan mapeos de cuentas activos para el class_id
   */
  public function validateAccountMapping(int $classId): void
  {
    $priceMapping = ApClassArticleAccountMapping::where('ap_class_article_id', $classId)
      ->where('account_type', 'PRECIO')
      ->where('status', true)
      ->exists();

    $discountMapping = ApClassArticleAccountMapping::where('ap_class_article_id', $classId)
      ->where('account_type', 'DESCUENTO')
      ->where('status', true)
      ->exists();

    if (!$priceMapping) {
      throw new Exception("No existe mapeo de cuenta PRECIO activo para class_id={$classId}");
    }

    if (!$discountMapping) {
      throw new Exception("No existe mapeo de cuenta DESCUENTO activo para class_id={$classId}");
    }
  }
}
