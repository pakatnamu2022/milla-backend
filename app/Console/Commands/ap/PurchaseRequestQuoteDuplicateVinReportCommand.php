<?php

namespace App\Console\Commands\ap;

use App\Models\ap\comercial\PurchaseRequestQuote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reporte de vehículos (VIN) asignados a más de una solicitud/cotización
 * activa a la vez. Sirve para detectar el bug de "doble VIN asignado", en el
 * que un mismo `ap_vehicle_id` quedó vinculado a varias filas de
 * `purchase_request_quote` sin eliminar.
 */
class PurchaseRequestQuoteDuplicateVinReportCommand extends Command
{
  protected $signature = 'purchase-request-quote:duplicate-vin-report
    {--csv : Exportar como CSV en lugar de tabla}';

  protected $description = 'Reporte de solicitudes de compra con el mismo VIN (vehículo) asignado a más de una cotización';

  public function handle(): int
  {
    $duplicatedVehicleIds = PurchaseRequestQuote::query()
      ->whereNotNull('ap_vehicle_id')
      ->groupBy('ap_vehicle_id')
      ->havingRaw('COUNT(*) > 1')
      ->pluck('ap_vehicle_id');

    if ($duplicatedVehicleIds->isEmpty()) {
      $this->info('No se encontraron vehículos con VIN asignado a más de una solicitud.');
      return self::SUCCESS;
    }

    $quotes = PurchaseRequestQuote::with(['vehicle', 'sede', 'holder'])
      ->whereIn('ap_vehicle_id', $duplicatedVehicleIds)
      ->orderBy('ap_vehicle_id')
      ->orderBy('id')
      ->get();

    $rows = [];
    foreach ($quotes->groupBy('ap_vehicle_id') as $vehicleId => $group) {
      /** @var \App\Models\ap\comercial\PurchaseRequestQuote $first */
      $first = $group->first();
      $vin = $first->vehicle?->vin ?? 'N/A';
      $plate = $first->vehicle?->plate ?? '-';

      foreach ($group as $quote) {
        $rows[] = [
          'ap_vehicle_id' => $vehicleId,
          'vin'           => $vin,
          'plate'         => $plate,
          'quote_id'      => $quote->id,
          'correlative'   => $quote->getFullCorrelativeAttribute(),
          'sede'          => $quote->sede?->abreviatura ?? '-',
          'holder'        => $quote->holder?->full_name ?? '-',
          'is_approved'   => $quote->is_approved ? 'Sí' : 'No',
          'is_paid'       => $quote->is_paid ? 'Sí' : 'No',
          'status'        => $quote->status ? 'Activa' : 'Inactiva',
          'created_at'    => $quote->created_at?->format('Y-m-d H:i') ?? '-',
        ];
      }
    }

    $this->info(
      'VINs con doble asignación: ' . $duplicatedVehicleIds->count() .
      ' | Solicitudes involucradas: ' . count($rows)
    );
    $this->newLine();

    $headers = ['ap_vehicle_id', 'vin', 'plate', 'quote_id', 'correlative', 'sede', 'holder', 'is_approved', 'is_paid', 'status', 'created_at'];

    if ($this->option('csv')) {
      $this->line(implode(',', $headers));
      foreach ($rows as $row) {
        $this->line(implode(',', array_map(
          fn($v) => str_contains((string)$v, ',') ? '"' . str_replace('"', '""', $v) . '"' : $v,
          array_values($row)
        )));
      }
      return self::SUCCESS;
    }

    $this->table(
      ['ID Vehículo', 'VIN', 'Placa', 'ID Cotización', 'Correlativo', 'Sede', 'Titular', 'Aprobada', 'Pagada', 'Estado', 'Creada'],
      $rows
    );

    return self::SUCCESS;
  }
}
