<?php

namespace App\Http\Services\ap\facturacion;

use App\Models\ap\configuracionComercial\vehiculo\ApClassArticleAccountMapping;
use App\Models\ap\facturacion\ElectronicDocument;
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

    // Obtener solo el item donde anticipo_regularizacion = 0
    $item = $document->items->where('anticipo_regularizacion', 0)->first();

    if (!$item) {
      throw new Exception('No se encontró item con anticipo_regularizacion = 0');
    }

    $totalValorUnitario = (float)$item->valor_unitario;
    $totalDescuento = (float)($item->descuento ?? 0);

    // Generar las líneas contables con los totales
    $lines = $this->generateAccountingLinesForTotals(
      $totalValorUnitario,
      $totalDescuento,
      $sedeDynCode,
      $asientoNumber,
      $lineNumber,
      $classId
    );

    // Validar balance antes de retornar
    $this->validateBalance($lines);

    return $lines;
  }

  /**
   * Genera las líneas contables para los totales del documento
   */
  protected function generateAccountingLinesForTotals(
    float  $totalValorUnitario,
    float  $totalDescuento,
    string $sedeDynCode,
    int    $asientoNumber,
    int    &$lineNumber,
    int    $classId
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

    // LÍNEA 1 - REVERSIÓN PRECIO: Cuenta Origen (según is_debit_origin de PRECIO)
    // PRECIO tiene is_debit_origin = true, entonces va en DÉBITO
    if ($totalValorUnitario > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $priceMapping->getFullAccountOrigin($sedeDynCode),
        'Debito' => $priceMapping->is_debit_origin ? round($totalValorUnitario, 2) : 0.00,
        'Credito' => $priceMapping->is_debit_origin ? 0.00 : round($totalValorUnitario, 2),
        'Descripcion' => 'Reversion precio unitario',
      ];
    }

    // LÍNEA 2 - REVERSIÓN DESCUENTO: Cuenta Origen (según is_debit_origin de DESCUENTO)
    // DESCUENTO tiene is_debit_origin = false, entonces va en CRÉDITO
    if ($totalDescuento > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $discountMapping->getFullAccountOrigin($sedeDynCode),
        'Debito' => $discountMapping->is_debit_origin ? round($totalDescuento, 2) : 0.00,
        'Credito' => $discountMapping->is_debit_origin ? 0.00 : round($totalDescuento, 2),
        'Descripcion' => 'Reversion descuento',
      ];
    }

    // LÍNEA 3 - BALANCE PRECIO: Cuenta Destino (INVERSO a is_debit_origin)
    // Como PRECIO tiene is_debit_origin = true, la cuenta destino va en CRÉDITO
    if ($totalValorUnitario > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $priceMapping->getFullAccountDestination($sedeDynCode),
        'Debito' => !$priceMapping->is_debit_origin ? round($totalValorUnitario, 2) : 0.00,
        'Credito' => !$priceMapping->is_debit_origin ? 0.00 : round($totalValorUnitario, 2),
        'Descripcion' => 'Balance precio',
      ];
    }

    // LÍNEA 4 - BALANCE DESCUENTO: Cuenta Destino (INVERSO a is_debit_origin)
    // Como DESCUENTO tiene is_debit_origin = false, la cuenta destino va en DÉBITO
    if ($totalDescuento > 0) {
      $lines[] = [
        'Asiento' => $asientoNumber,
        'Linea' => $lineNumber++,
        'CuentaNumero' => $discountMapping->getFullAccountDestination($sedeDynCode),
        'Debito' => !$discountMapping->is_debit_origin ? round($totalDescuento, 2) : 0.00,
        'Credito' => !$discountMapping->is_debit_origin ? 0.00 : round($totalDescuento, 2),
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
