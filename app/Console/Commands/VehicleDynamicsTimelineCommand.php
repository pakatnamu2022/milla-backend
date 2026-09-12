<?php

namespace App\Console\Commands;

use App\Models\ap\comercial\Vehicles;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Traza TODO lo que se envió a Dynamics (vía la intermedia dbtp) para un vehículo
 * específico, a lo largo de toda su vida: orden de compra, recepción, transferencias
 * de inventario, guías de venta, asientos contables (y sus reversiones), notas
 * internas y activos — todo ordenado cronológicamente por VIN o por ap_vehicles.id.
 *
 * ap_vehicles_id en ap_vehicle_purchase_order_migration_log NO está poblado de forma
 * confiable en la mayoría de los pasos (solo en asset_transaction), así que el
 * enlace real con el vehículo se hace por las otras FK del log según el paso:
 *   - purchase_order / reception*         -> vehicle_purchase_order_id (ap_purchase_order.id)
 *   - inventory_transfer* / sale_shipping_guide* / accounting_entry* -> shipping_guide_id
 *   - sales_client / sales_document* / sales_doc_fv                  -> electronic_document_id
 *   - internal_note_transaction*                                     -> internal_note_id
 *   - asset_transaction*                                             -> ap_vehicles_id (directo)
 */
class VehicleDynamicsTimelineCommand extends Command
{
  protected $signature = 'vehicle:dynamics-timeline
    {vin? : VIN del vehículo}
    {--vehicle_id= : ID del vehículo (ap_vehicles.id), alternativa al VIN}
    {--accounts : Además del timeline, consulta dbtp y muestra las cuentas de inventario/contables realmente enviadas por cada paso}
    {--csv= : Ruta de archivo CSV donde exportar el timeline}';

  protected $description = 'Línea de tiempo de todo lo enviado a Dynamics (OC, recepción, transferencias, ventas, guías, asientos contables, notas internas, activos) para un vehículo, por VIN o vehicle_id';

  public function handle(): int
  {
    $vehicle = $this->resolveVehicle();

    if (!$vehicle) {
      return self::FAILURE;
    }

    $this->info("Vehículo #{$vehicle->id} — VIN: {$vehicle->vin}");
    $this->line('');

    $purchaseOrderIds = $vehicle->purchaseOrders()->pluck('ap_purchase_order.id');
    $guideIds = $vehicle->shippingGuides()->pluck('shipping_guides.id');
    $documentIds = $vehicle->electronicDocuments()->pluck('ap_billing_electronic_documents.id');
    $noteIds = ApInternalNote::whereHas(
      'workOrder',
      fn($q) => $q->where('vehicle_id', $vehicle->id)
    )->pluck('id');

    $logs = VehiclePurchaseOrderMigrationLog::query()
      ->where(function ($q) use ($purchaseOrderIds, $guideIds, $documentIds, $noteIds, $vehicle) {
        $q->whereIn('vehicle_purchase_order_id', $purchaseOrderIds)
          ->orWhereIn('shipping_guide_id', $guideIds)
          ->orWhereIn('electronic_document_id', $documentIds)
          ->orWhereIn('internal_note_id', $noteIds)
          ->orWhere('ap_vehicles_id', $vehicle->id);
      })
      ->orderBy('created_at')
      ->orderBy('id')
      ->get();

    if ($logs->isEmpty()) {
      $this->warn('No se encontraron registros de sincronización a Dynamics para este vehículo.');
      return self::SUCCESS;
    }

    $rows = $logs->map(function (VehiclePurchaseOrderMigrationLog $log) {
      return [
        'id_log' => $log->id,
        'fecha' => optional($log->created_at)->format('Y-m-d H:i:s'),
        'paso' => $log->step,
        'tabla_dbtp' => $log->table_name,
        'referencia_external_id' => $log->external_id,
        'estado' => $log->status,
        'entidad' => $this->describeEntity($log),
        'intentos' => $log->attempts,
      ];
    });

    $this->table(
      ['ID Log', 'Fecha', 'Paso', 'Tabla dbtp', 'Referencia/External ID', 'Estado', 'Entidad', 'Intentos'],
      $rows->toArray()
    );

    if ($csvPath = $this->option('csv')) {
      $handle = fopen($csvPath, 'w');
      fputcsv($handle, array_keys($rows->first()));
      foreach ($rows as $row) {
        fputcsv($handle, $row);
      }
      fclose($handle);
      $this->info("Exportado a: {$csvPath}");
    }

    if ($this->option('accounts')) {
      $this->line('');
      $this->info('=== Cuentas realmente enviadas a Dynamics (consultadas en dbtp) ===');
      foreach ($logs as $log) {
        $this->printAccountsForLog($log);
      }
    } else {
      $this->line('');
      $this->comment('Agrega --accounts para ver las cuentas de inventario/contables reales que se enviaron a dbtp en cada paso.');
    }

    return self::SUCCESS;
  }

  protected function resolveVehicle(): ?Vehicles
  {
    $vehicleId = $this->option('vehicle_id');
    $vin = $this->argument('vin');

    if (!$vehicleId && !$vin) {
      $this->error('Debes indicar un VIN o --vehicle_id=');
      return null;
    }

    $vehicle = $vehicleId
      ? Vehicles::find($vehicleId)
      : Vehicles::where('vin', $vin)->first();

    if (!$vehicle) {
      $this->error('Vehículo no encontrado con ese ' . ($vehicleId ? 'vehicle_id' : 'VIN') . '.');
      return null;
    }

    return $vehicle;
  }

  protected function describeEntity(VehiclePurchaseOrderMigrationLog $log): string
  {
    if ($log->vehicle_purchase_order_id) {
      return "OC #{$log->vehicle_purchase_order_id}";
    }
    if ($log->shipping_guide_id) {
      return "Guía #{$log->shipping_guide_id}";
    }
    if ($log->electronic_document_id) {
      return "Doc #{$log->electronic_document_id}";
    }
    if ($log->internal_note_id) {
      return "NI #{$log->internal_note_id}";
    }
    if ($log->ap_vehicles_id) {
      return "Vehículo #{$log->ap_vehicles_id}";
    }
    return 'N/A';
  }

  /**
   * Consulta dbtp para el paso dado y muestra las cuentas realmente enviadas.
   * Solo los pasos de detalle listados abajo llevan cuentas en su payload; los
   * demás (cabeceras, series, documentos de venta, recepción) no tienen columnas
   * de cuenta en la intermedia y se omiten.
   */
  protected function printAccountsForLog(VehiclePurchaseOrderMigrationLog $log): void
  {
    $rows = match ($log->step) {
      VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL
        => $this->fetchInventoryDetailAccounts('neInTbTransferenciaInventarioDet', 'TransferenciaId', $log->external_id),

      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL,
      VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL,
      VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_DETAIL
        => $this->fetchInventoryDetailAccounts('neInTbTransaccionInventarioDet', 'TransaccionId', $log->external_id),

      VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL_REVERSAL
        => $this->fetchAccountingEntryAccounts($log->external_id),

      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL
        => $this->fetchOrdenCompraAccounts($log->external_id),

      default => null,
    };

    if ($rows === null) {
      return;
    }

    $this->line("--- Log #{$log->id} | {$log->step} | Referencia: {$log->external_id} ---");

    if ($rows->isEmpty()) {
      $this->warn('  (sin filas en dbtp para esta Referencia — puede no haberse sincronizado aún)');
      return;
    }

    $this->table(array_keys((array) $rows->first()), $rows->map(fn($r) => (array) $r)->toArray());
  }

  protected function fetchInventoryDetailAccounts(string $table, string $keyColumn, ?string $externalId): Collection
  {
    if (!$externalId) {
      return collect();
    }

    return DB::connection('dbtp')
      ->table($table)
      ->where($keyColumn, $externalId)
      ->orderBy('Linea')
      ->get(['Linea', 'ArticuloId', 'CuentaInventario', 'CuentaContrapartida']);
  }

  protected function fetchAccountingEntryAccounts(?string $referencia): Collection
  {
    if (!$referencia) {
      return collect();
    }

    $header = DB::connection('dbtp')
      ->table('neInTbIntegracionAsientoCab')
      ->where('Referencia', $referencia)
      ->first();

    if (!$header) {
      return collect();
    }

    return DB::connection('dbtp')
      ->table('neInTbIntegracionAsientoDet')
      ->where('Asiento', $header->Asiento)
      ->orderBy('Linea')
      ->get(['Linea', 'CuentaNumero', 'Debito', 'Credito', 'Descripcion']);
  }

  protected function fetchOrdenCompraAccounts(?string $ordenCompraId): Collection
  {
    if (!$ordenCompraId) {
      return collect();
    }

    return DB::connection('dbtp')
      ->table('neInTbOrdenCompraDet')
      ->where('OrdenCompraId', $ordenCompraId)
      ->orderBy('Linea')
      ->get(['Linea', 'ArticuloId', 'CuentaNumeroInventario']);
  }
}
