<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ValidatesPendingJobs;
use App\Http\Services\DatabaseSyncService;
use App\Jobs\MigrateProductReceptionToDynamicsJob;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use Illuminate\Console\Command;

class VerifyProductReceptionMigrationCommand extends Command
{
  use ValidatesPendingJobs;

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'product-reception:verify-migration {--id= : ID de la recepción específica} {--all : Verificar todas las recepciones pendientes} {--limit=100 : Número máximo de recepciones a procesar (default: 100)} {--sync : Ejecutar inmediatamente sin usar cola}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y migra recepciones de productos (POSVENTA) pendientes a Dynamics';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $receptionId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    // Por defecto usa cola, solo sync si se especifica --sync
    $useSync = $sync;

    if ($receptionId) {
      // Verificar una recepción específica
      $reception = TransferReception::with('shippingGuide')->find($receptionId);

      if (!$reception) {
        $this->error("Recepción no encontrada: {$receptionId}");
        return 1;
      }

      if (!$reception->shippingGuide) {
        $this->error("La recepción no tiene guía de remisión asociada.");
        return 1;
      }

      // Validar que sea del área POSVENTA
      if ($reception->shippingGuide->area_id !== ApMasters::AREA_POSVENTA) {
        $this->error("La recepción no es del área POSVENTA (area_id: {$reception->shippingGuide->area_id})");
        return 1;
      }

      if ($useSync) {
        $this->info("Ejecutando verificación para la recepción: {$reception->reception_number}");
        $syncService = app(DatabaseSyncService::class);
        $job = new MigrateProductReceptionToDynamicsJob($reception->id);

        try {
          $job->handle($syncService);
          $this->info("✓ Verificación completada.");
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        $this->info("Despachando job de verificación para la recepción: {$reception->reception_number}");
        MigrateProductReceptionToDynamicsJob::dispatch($reception->id);
        $this->info("Job despachado a la cola.");
      }

      return 0;
    }

    if ($all) {
      // Validar límite de jobs pendientes antes de despachar (solo si usa cola)
      if (!$useSync && !$this->canDispatchMoreJobs(MigrateProductReceptionToDynamicsJob::class)) {
        return 0;
      }

      // Verificar todas las recepciones pendientes (limitado por --limit)
      $limit = (int)$this->option('limit');

      // Buscar recepciones con guías de POSVENTA pendientes de migración
      $pendingReceptions = TransferReception::whereHas('shippingGuide', function ($query) {
        $query->where('area_id', ApMasters::AREA_POSVENTA)
          ->whereIn('migration_status', [
            VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
            VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
            'failed', // backward compat: registros existentes en BD
          ])
          ->where('aceptada_por_sunat', true);
      })
        ->with('shippingGuide:id,correlative,migration_status,area_id,aceptada_por_sunat')
        ->where('status', TransferReception::STATUS_PENDING) // Solo recepciones pendientes
        ->whereNull('deleted_at') // Excluir recepciones eliminadas
        ->orderBy('id', 'desc')
        ->limit($limit)
        ->get();

      if ($pendingReceptions->isEmpty()) {
        $this->info("No hay recepciones de productos pendientes de migración.");
        return 0;
      }

      $this->info("Encontradas {$pendingReceptions->count()} recepciones pendientes de migración.");

      if ($useSync) {
        $bar = $this->output->createProgressBar($pendingReceptions->count());
        $bar->start();

        $syncService = app(DatabaseSyncService::class);
        foreach ($pendingReceptions as $reception) {
          try {
            $job = new MigrateProductReceptionToDynamicsJob($reception->id);
            $job->handle($syncService);
          } catch (\Exception $e) {
            $this->newLine();
            $this->error("Error en recepción {$reception->reception_number}: {$e->getMessage()}");
          }
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Verificación completada.");
      } else {
        $bar = $this->output->createProgressBar($pendingReceptions->count());
        $bar->start();

        foreach ($pendingReceptions as $reception) {
          MigrateProductReceptionToDynamicsJob::dispatch($reception->id);
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Jobs despachados a la cola.");
      }

      return 0;
    }

    // Si no se especifica ninguna opción, mostrar ayuda
    $this->error("Debe especificar --id o --all para procesar recepciones.");
    $this->line("");
    $this->line("Ejemplos:");
    $this->line("  php artisan product-reception:verify-migration --id=123");
    $this->line("  php artisan product-reception:verify-migration --all --limit=100");
    $this->line("  php artisan product-reception:verify-migration --all --sync");
    return 1;
  }
}
