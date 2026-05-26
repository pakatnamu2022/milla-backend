<?php

namespace App\Console\Commands;

use App\Http\Services\DatabaseSyncService;
use App\Jobs\MigrateProductReceptionToDynamicsJob;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Illuminate\Console\Command;

class VerifyShippingGuidesPostventaMigrationCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'shipping-guides-postventa:verify-migration {--id= : ID de la guía de remisión específica} {--all : Verificar todas las guías pendientes} {--limit=100 : Número máximo de guías a procesar (default: 100)} {--sync : Ejecutar inmediatamente sin usar cola}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y migra guías de remisión de productos (POSVENTA) pendientes a Dynamics';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $guideId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    // Por defecto usa cola, solo sync si se especifica --sync
    $useSync = $sync;

    if ($guideId) {
      // Verificar una guía específica
      $guide = ShippingGuides::with('inventoryMovement')->find($guideId);

      if (!$guide) {
        $this->error("Guía de remisión no encontrada: {$guideId}");
        return 1;
      }

      // Validar que sea del área POSVENTA
      if ($guide->area_id !== ApMasters::AREA_POSVENTA) {
        $this->error("La guía no es del área POSVENTA (area_id: {$guide->area_id})");
        return 1;
      }

      // Validar que esté aceptada por SUNAT
      if (!$guide->aceptada_por_sunat) {
        $this->error("La guía no ha sido aceptada por SUNAT aún.");
        return 1;
      }

      // Mostrar estado actual de migración
      $this->info("=== Estado de Migración ===");
      $this->info("Guía: {$guide->document_number}");
      $this->info("Estado migración: {$guide->migration_status}");
      $this->info("Status Dynamics: " . ($guide->status_dynamic ? 'Migrado ✓' : 'Pendiente ✗'));
      $this->info("Migrado en: " . ($guide->migrated_at ? $guide->migrated_at->format('Y-m-d H:i:s') : 'N/A'));
      $this->newLine();

      // Mostrar logs de migración
      $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $guide->id)
        ->orderBy('created_at', 'desc')
        ->get();

      if ($logs->isNotEmpty()) {
        $this->info("=== Logs de Migración ({$logs->count()}) ===");
        foreach ($logs as $log) {
          $statusIcon = match($log->status) {
            'completed' => '✓',
            'failed' => '✗',
            'in_progress' => '⏳',
            default => '○'
          };
          $this->line("{$statusIcon} {$log->step} [{$log->status}] - ProcesoEstado: {$log->proceso_estado}");
          if ($log->error_message) {
            $this->error("  Error: {$log->error_message}");
          }
        }
        $this->newLine();
      } else {
        $this->warn("No hay logs de migración para esta guía.");
        $this->newLine();
      }

      // Si ya está completamente migrado, preguntar si re-migrar
      if ($guide->status_dynamic === 1 && $guide->migration_status === 'completed') {
        if (!$this->confirm('La guía ya está migrada correctamente. ¿Desea re-migrar?', false)) {
          $this->info("Operación cancelada.");
          return 0;
        }
      }

      if ($useSync) {
        $this->info("Ejecutando migración para la guía: {$guide->document_number}");
        $syncService = app(DatabaseSyncService::class);
        $job = new MigrateProductReceptionToDynamicsJob($guide->id);

        try {
          $job->handle($syncService);
          $this->info("✓ Migración completada.");
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        $this->info("Despachando job de migración para la guía: {$guide->document_number}");
        MigrateProductReceptionToDynamicsJob::dispatch($guide->id);
        $this->info("Job despachado a la cola.");
      }

      return 0;
    }

    if ($all) {
      // Verificar todas las guías pendientes (limitado por --limit)
      $limit = (int)$this->option('limit');

      // Buscar guías de POSVENTA pendientes de migración
      $pendingGuides = ShippingGuides::with('inventoryMovement')
        ->where('area_id', ApMasters::AREA_POSVENTA)
        ->where('aceptada_por_sunat', true)
        ->where(function ($query) {
          $query->whereIn('migration_status', [
            VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
            VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
            VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
          ])
            ->orWhere('status_dynamic', '!=', 1)
            ->orWhereNull('status_dynamic');
        })
        ->whereHas('inventoryMovement', function ($query) {
          $query->where('item_type', 'PRODUCTO');
        })
        ->whereNull('deleted_at')
        ->orderBy('id', 'desc')
        ->limit($limit)
        ->get();

      if ($pendingGuides->isEmpty()) {
        $this->info("No hay guías de remisión de productos pendientes de migración.");
        return 0;
      }

      $this->info("Encontradas {$pendingGuides->count()} guías pendientes de migración.");

      if ($useSync) {
        $bar = $this->output->createProgressBar($pendingGuides->count());
        $bar->start();

        $syncService = app(DatabaseSyncService::class);
        foreach ($pendingGuides as $guide) {
          try {
            $job = new MigrateProductReceptionToDynamicsJob($guide->id);
            $job->handle($syncService);
          } catch (\Exception $e) {
            $this->newLine();
            $this->error("Error en guía {$guide->document_number}: {$e->getMessage()}");
          }
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Migración completada.");
      } else {
        $bar = $this->output->createProgressBar($pendingGuides->count());
        $bar->start();

        foreach ($pendingGuides as $guide) {
          MigrateProductReceptionToDynamicsJob::dispatch($guide->id);
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Jobs despachados a la cola.");
      }

      return 0;
    }

    // Si no se especifica ninguna opción, mostrar ayuda
    $this->error("Debe especificar --id o --all para procesar guías.");
    $this->line("");
    $this->line("Ejemplos:");
    $this->line("  php artisan shipping-guides-postventa:verify-migration --id=123");
    $this->line("  php artisan shipping-guides-postventa:verify-migration --all --limit=100");
    $this->line("  php artisan shipping-guides-postventa:verify-migration --all --sync");
    $this->newLine();
    $this->line("Consultar estado de una guía:");
    $this->line("  php artisan shipping-guides-postventa:verify-migration --id=123");
    return 1;
  }
}