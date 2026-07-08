<?php

namespace App\Imports\ap\comercial;

use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrderItem;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VehiclePurchaseOrderUpdateByVinImport implements ToCollection, WithHeadingRow
{
  private array $results = [
    'updated'        => 0,
    'errors'         => [],
    'rows_processed' => 0,
  ];

  public function collection(Collection $rows): void
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2;
      try {
        $this->processRow($row->toArray(), $rowNumber);
        $this->results['rows_processed']++;
      } catch (Exception $e) {
        $this->results['errors'][] = "Fila {$rowNumber} (VIN: " . ($row['vin'] ?? 'N/A') . "): " . $e->getMessage();
      }
    }
  }

  private function processRow(array $row, int $rowNumber): void
  {
    $vin      = strtoupper(trim($row['vin']      ?? ''));
    $subtotal = $row['subtotal'] ?? null;
    $igv      = $row['igv']      ?? null;
    $total    = $row['total']    ?? null;

    if (empty($vin))           throw new Exception('El VIN es requerido');
    if ($subtotal === null)    throw new Exception('El subtotal es requerido');
    if ($igv === null)         throw new Exception('El IGV es requerido');
    if ($total === null)       throw new Exception('El total es requerido');

    $subtotal = (float) $subtotal;
    $igv      = (float) $igv;
    $total    = (float) $total;

    $vehicle = Vehicles::where('vin', $vin)->whereNull('deleted_at')->first();
    if (!$vehicle) throw new Exception("No se encontró vehículo con VIN {$vin}");

    $purchaseOrder = $vehicle->purchaseOrder()->first();
    if (!$purchaseOrder) throw new Exception("El vehículo no tiene una orden de compra asociada");

    DB::beginTransaction();
    try {
      $purchaseOrder->update([
        'subtotal' => $subtotal,
        'igv'      => $igv,
        'total'    => $total,
      ]);

      // Actualizar el ítem del vehículo (is_vehicle = true)
      $vehicleItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
        ->where('is_vehicle', true)
        ->first();

      if ($vehicleItem) {
        $vehicleItem->update([
          'unit_price' => $subtotal,
          'total'      => $total,
        ]);
      }

      DB::commit();
      $this->results['updated']++;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
