<?php

namespace App\Console\Commands;

use App\Jobs\VerifyAccountingEntryJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Illuminate\Console\Command;

class VerifyAccountingEntryCommand extends Command
{
  protected $signature = 'accounting-entry:verify
    {--id= : ID de la guía de remisión específica}
    {--all : Verificar todos los asientos pendientes}
    {--sync : Ejecutar inmediatamente sin usar cola}';

  protected $description = 'Verifica asientos contables procesados por GP y marca entregas de vehículos como completadas';

  public function handle(): int
  {
    $shippingGuideId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    if ($shippingGuideId) {
      if ($sync) {
        $this->info("Verificando asiento contable para guía: {$shippingGuideId}");
        $job = new VerifyAccountingEntryJob((int) $shippingGuideId);
        try {
          $job->handle();
          $this->info('✓ Verificación completada.');
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        VerifyAccountingEntryJob::dispatch((int) $shippingGuideId);
        $this->info("Job despachado a la cola para guía: {$shippingGuideId}");
      }
      return 0;
    }

    if ($all) {
      $pendingCount = VehiclePurchaseOrderMigrationLog::where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
        ->where('status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
        ->where('proceso_estado', 0)
        ->whereNotNull('shipping_guide_id')
        ->count();

      if ($pendingCount === 0) {
        $this->info('No hay asientos contables pendientes de verificación.');
        return 0;
      }

      $this->info("Encontrados {$pendingCount} asientos pendientes de verificación.");

      if ($sync) {
        $job = new VerifyAccountingEntryJob();
        try {
          $job->handle();
          $this->info('✓ Verificación completada.');
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        VerifyAccountingEntryJob::dispatch();
        $this->info('Job despachado a la cola.');
      }
      return 0;
    }

    $this->error('Debe especificar --id o --all.');
    $this->line('Ejemplos:');
    $this->line('  php artisan accounting-entry:verify --id=123');
    $this->line('  php artisan accounting-entry:verify --all');
    $this->line('  php artisan accounting-entry:verify --all --sync');
    return 1;
  }
}
