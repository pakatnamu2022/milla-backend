<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollInsurance;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PayrollInsuranceOncoplusImport implements ToCollection, WithHeadingRow
{
  private int $periodId;
  private int $businessPartnerId;
  private array $results = [
    'created' => 0,
    'updated' => 0,
    'errors' => [],
    'rows_processed' => 0,
    'skipped' => 0,
  ];

  public function __construct(int $periodId, int $businessPartnerId)
  {
    $this->periodId = $periodId;
    $this->businessPartnerId = $businessPartnerId;
  }

  public function collection(Collection $rows): void
  {
    $headerRow = null;
    $dataStarted = false;

    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // +2 porque la fila 1 es el header
      $rowArray = $row->toArray();

      // Detectar la fila de TOTALES para detener el procesamiento
      $firstColumnValue = $this->getFirstNonEmptyValue($rowArray);
      if (strtoupper(trim($firstColumnValue)) === 'TOTALES') {
        break; // Detener procesamiento al llegar a TOTALES
      }

      // Verificar si la fila está vacía
      if ($this->isRowEmpty($rowArray)) {
        continue; // Ignorar filas vacías
      }

      try {
        $this->processRow($rowArray, $rowNumber);
        $this->results['rows_processed']++;
      } catch (Exception $e) {
        $this->results['errors'][] = "Fila {$rowNumber}: " . $e->getMessage();
      }
    }
  }

  private function processRow(array $row, int $rowNumber): void
  {
    // Extraer datos del Excel usando los encabezados exactos
    $docNumberAffiliate = $this->extractValue($row, ['num_doc_afiliado', 'núm_doc_afiliado']);
    $rateWithTax = $this->extractValue($row, ['tarifa_con_igv']);
    $contractingName = $this->extractValue($row, ['contratante']);
    $numDocContracting = $this->extractValue($row, ['num_doc_contratante', 'núm_doc_contratante']);

    // Validar campos requeridos
    if (empty($numDocContracting)) {
      throw new Exception('El campo "Núm Doc Contratante" es requerido');
    }

    // Buscar worker_id por vat (num_doc_contracting)
    $worker = Worker::withoutGlobalScope('working')
      ->where('vat', trim($numDocContracting))
      ->first();

    if (!$worker) {
      throw new Exception("No se encontró trabajador con documento: {$numDocContracting}");
    }

    // Limpiar y validar tarifa
    $rateWithTaxValue = $this->parseDecimal($rateWithTax);

    DB::beginTransaction();
    try {
      // Buscar si ya existe un registro con los mismos datos
      $existingRecord = PayrollInsurance::where('worker_id', $worker->id)
        ->where('period_id', $this->periodId)
        ->where('business_partner_id', $this->businessPartnerId)
        ->where('doc_number_affiliate', trim($docNumberAffiliate))
        ->first();

      $data = [
        'worker_id' => $worker->id,
        'period_id' => $this->periodId,
        'business_partner_id' => $this->businessPartnerId,
        'doc_number_affiliate' => trim($docNumberAffiliate),
        'rate_with_tax' => $rateWithTaxValue,
        'contracting_name' => trim($contractingName),
        'num_doc_contracting' => trim($numDocContracting),
      ];

      if ($existingRecord) {
        $existingRecord->update($data);
        $this->results['updated']++;
      } else {
        PayrollInsurance::create($data);
        $this->results['created']++;
      }

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Extrae un valor del array usando diferentes posibles nombres de columna
   */
  private function extractValue(array $row, array $possibleKeys): ?string
  {
    foreach ($possibleKeys as $key) {
      if (isset($row[$key])) {
        return $row[$key];
      }
    }
    return null;
  }

  /**
   * Convierte un valor a decimal
   */
  private function parseDecimal($value): float
  {
    if (empty($value)) {
      return 0.00;
    }

    // Si es string, quitar comas y convertir
    if (is_string($value)) {
      $value = str_replace(',', '', trim($value));
    }

    return (float)number_format((float)$value, 2, '.', '');
  }

  /**
   * Obtiene el primer valor no vacío de un array
   */
  private function getFirstNonEmptyValue(array $row): string
  {
    foreach ($row as $value) {
      if (!empty($value)) {
        return (string)$value;
      }
    }
    return '';
  }

  /**
   * Verifica si una fila está completamente vacía
   */
  private function isRowEmpty(array $row): bool
  {
    foreach ($row as $value) {
      if (!empty($value) && trim($value) !== '') {
        return false;
      }
    }
    return true;
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
