<?php

namespace App\Console\Commands;

use App\Jobs\SyncAccountingEntryJob;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BulkSyncAccountingEntryCommand extends Command
{
  protected $signature = 'accounting-entry:bulk-sync
    {--reset : Eliminar logs existentes de asiento contable antes de re-despachar}
    {--dry-run : Solo mostrar qué se haría, sin ejecutar}';

  protected $description = 'Despacha SyncAccountingEntryJob para todas las guías VENTA completadas en Dynamics';

  public function handle(): int
  {
    $guides = ShippingGuides::with([
      'migrationLogs' => fn($q) => $q->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL,
      ]),
    ])
      ->where('transfer_reason_id', SunatConcepts::TRANSFER_REASON_VENTA)
      ->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('status_dynamic', 1)
      ->whereHas('sedeTransmitter', fn($q) => $q->where('empresa_id', Company::COMPANY_AP_ID))
      ->orderBy('id')
      ->get();

    $dryRun = $this->option('dry-run');
    $reset = $this->option('reset');

    $this->info("Guías VENTA completadas en Dynamics: {$guides->count()}");

    if ($dryRun) {
      $this->warn('[DRY RUN] No se ejecutará ningún cambio.');
    }

    if ($reset && !$dryRun) {
      if (!$this->confirm('¿Eliminar los logs de asiento contable existentes y registros en tablas intermedias?')) {
        $this->info('Cancelado.');
        return 0;
      }
      $this->resetAccountingLogs($guides->pluck('id')->toArray());
    }

    $dispatched = 0;
    $skipped = 0;

    foreach ($guides as $guide) {
      $hasLog = $guide->migrationLogs->isNotEmpty();

      if ($hasLog && !$reset) {
        $skipped++;
        continue;
      }

      if ($dryRun) {
        $this->line("  [DRY RUN] Despachando SyncAccountingEntryJob para guía {$guide->id} ({$guide->document_number})");
        $dispatched++;
        continue;
      }

      SyncAccountingEntryJob::dispatch($guide->id);
      $dispatched++;
    }

    $this->newLine();
    $this->info("Despachados: {$dispatched} | Omitidos (ya tienen log): {$skipped}");

    if ($skipped > 0) {
      $this->line("Use --reset para eliminar logs existentes y re-enviar todos.");
    }

    return 0;
  }

  protected function resetAccountingLogs(array $guideIds): void
  {
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
      VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL,
    ];

    // Obtener referencias antes de borrar para limpiar la tabla intermedia
    $externalIds = VehiclePurchaseOrderMigrationLog::whereIn('shipping_guide_id', $guideIds)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
      ->whereNotNull('external_id')
      ->pluck('external_id')
      ->toArray();

    // Eliminar de tablas intermedias Dynamics
    if (!empty($externalIds)) {
      // Obtener Asiento numbers para borrar el detalle también (solo GPAUP)
      $asientoNumbers = DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->whereIn('Referencia', $externalIds)
        ->pluck('Asiento')
        ->toArray();

      DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->whereIn('Referencia', $externalIds)
        ->delete();

      if (!empty($asientoNumbers)) {
        DB::connection('dbtp')
          ->table('neInTbIntegracionAsientoDet')
          ->whereIn('Asiento', $asientoNumbers)
          ->delete();
      }

      $this->info("Eliminados " . count($externalIds) . " registros de neInTbIntegracionAsientoCab y sus detalles.");
    }

    // Eliminar logs locales
    $deleted = VehiclePurchaseOrderMigrationLog::whereIn('shipping_guide_id', $guideIds)
      ->whereIn('step', $steps)
      ->delete();

    $this->info("Eliminados {$deleted} logs de ap_vehicle_purchase_order_migration_log.");
  }
}
