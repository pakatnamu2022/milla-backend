<?php

namespace App\Imports\ap\comercial;

use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Importa el Excel de contabilidad (columnas: vin, fecha, factura) para corregir la
 * fecha de emisión y la factura de las órdenes de compra del stock inicial.
 *
 * Solo se actualizan OC que cumplan: number LIKE 'OCSI-%' AND emission_date = 2026-06-30.
 * Con $dryRun = true no se persiste nada: se devuelve el detalle fila por fila para
 * que el usuario confirme antes de aplicar.
 */
class VehicleOcsiInvoiceUpdateByVinImport implements ToCollection, WithHeadingRow
{
  private const OCSI_PREFIX = 'OCSI-';
  private const REFERENCE_EMISSION_DATE = '2026-06-30';

  private bool $dryRun;

  private array $results = [
    'updated'        => 0,
    'errors'         => [],
    'rows_processed' => 0,
    'dry_run'        => true,
    'rows'           => [],
  ];

  public function __construct(bool $dryRun = true)
  {
    $this->dryRun = $dryRun;
    $this->results['dry_run'] = $dryRun;
  }

  public function collection(Collection $rows): void
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // +2 porque la fila 1 es el header
      try {
        $this->processRow($row->toArray(), $rowNumber);
        $this->results['rows_processed']++;
      } catch (Exception $e) {
        $vin = strtoupper(trim($row['vin'] ?? 'N/A'));
        $this->results['errors'][] = "Fila {$rowNumber} (VIN: {$vin}): " . $e->getMessage();
        $this->results['rows'][] = [
          'row'                   => $rowNumber,
          'vin'                   => $vin,
          'status'                => 'error',
          'message'               => $e->getMessage(),
          'oc_number'             => null,
          'current_emission_date' => null,
          'new_emission_date'     => null,
          'current_invoice'       => null,
          'new_invoice'           => null,
        ];
      }
    }
  }

  private function processRow(array $row, int $rowNumber): void
  {
    $vin = strtoupper(trim($row['vin'] ?? ''));
    if (empty($vin)) throw new Exception('El VIN es requerido');

    $newEmissionDate = $this->parseDate($row['fecha'] ?? null);
    if ($newEmissionDate === null) throw new Exception('La fecha es requerida o tiene un formato inválido');

    [$series, $number] = $this->parseInvoice($row['factura'] ?? null);
    $newInvoice = "{$series}-{$number}";

    $vehicle = Vehicles::where('vin', $vin)->whereNull('deleted_at')->first();
    if (!$vehicle) throw new Exception("No se encontró vehículo con VIN {$vin}");

    /** @var PurchaseOrder|null $purchaseOrder */
    $purchaseOrder = $vehicle->purchaseOrder()
      ->where('number', 'like', self::OCSI_PREFIX . '%')
      ->whereDate('emission_date', self::REFERENCE_EMISSION_DATE)
      ->first();

    if (!$purchaseOrder) {
      throw new Exception(
        'El VIN no tiene una orden de compra OCSI con fecha ' . self::REFERENCE_EMISSION_DATE
      );
    }

    $currentEmissionDate = optional($purchaseOrder->emission_date)->format('Y-m-d');
    $currentInvoice = trim(($purchaseOrder->invoice_series ?? '') . '-' . ($purchaseOrder->invoice_number ?? ''), '-');

    $detail = [
      'row'                   => $rowNumber,
      'vin'                   => $vin,
      'status'                => 'ok',
      'message'               => null,
      'oc_number'             => $purchaseOrder->number,
      'current_emission_date' => $currentEmissionDate,
      'new_emission_date'     => $newEmissionDate,
      'current_invoice'       => $currentInvoice ?: null,
      'new_invoice'           => $newInvoice,
    ];

    if (!$this->dryRun) {
      DB::beginTransaction();
      try {
        $purchaseOrder->update([
          'emission_date'  => $newEmissionDate,
          'invoice_series' => $series,
          'invoice_number' => $number,
        ]);
        DB::commit();
      } catch (Exception $e) {
        DB::rollBack();
        throw $e;
      }
    }

    $this->results['updated']++;
    $this->results['rows'][] = $detail;
  }

  /**
   * Separa "F001-00001234" en ['F001', '00001234'] usando el primer guión.
   */
  private function parseInvoice($value): array
  {
    $value = strtoupper(trim((string) $value));
    if ($value === '' || !str_contains($value, '-')) {
      throw new Exception('La factura es requerida y debe tener el formato SERIE-NÚMERO (ej. F001-00001234)');
    }

    [$series, $number] = explode('-', $value, 2);
    $series = trim($series);
    $number = trim($number);

    if ($series === '' || $number === '') {
      throw new Exception('La factura debe tener serie y número (ej. F001-00001234)');
    }

    return [$series, $number];
  }

  private function parseDate($value): ?string
  {
    if ($value === null || $value === '') return null;

    try {
      if (is_numeric($value)) {
        return Date::excelToDateTimeObject($value)->format('Y-m-d');
      }
      return Carbon::parse($value)->format('Y-m-d');
    } catch (Exception $e) {
      return null;
    }
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
