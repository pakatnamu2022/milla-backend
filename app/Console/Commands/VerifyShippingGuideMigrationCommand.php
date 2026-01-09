<?php

namespace App\Console\Commands;

use App\Http\Services\DatabaseSyncService;
use App\Jobs\VerifyAndMigrateShippingGuideJob;
use App\Models\ap\comercial\ShippingGuides;
use Illuminate\Console\Command;

class VerifyShippingGuideMigrationCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'shipping-guide:verify-migration {--id= : ID de la guía de remisión específica} {--all : Verificar todas las guías pendientes} {--limit=100 : Número máximo de guías a procesar (default: 100)} {--sync : Ejecutar inmediatamente sin usar cola}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y migra guías de remisión pendientes';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $shippingGuideId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    // Por defecto usa cola, solo sync si se especifica --sync
    $useSync = $sync;

    if ($shippingGuideId) {
      // Verificar una guía específica
      $shippingGuide = ShippingGuides::find($shippingGuideId);

      if (!$shippingGuide) {
        $this->error("Guía de remisión no encontrada: {$shippingGuideId}");
        return 1;
      }

      if ($useSync) {
        $this->info("Ejecutando verificación para la guía: {$shippingGuide->document_number}");
        $syncService = app(DatabaseSyncService::class);
        $job = new VerifyAndMigrateShippingGuideJob($shippingGuide->id);

        try {
          $job->handle($syncService);
          $this->info("✓ Verificación completada.");
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        $this->info("Despachando job de verificación para la guía: {$shippingGuide->document_number}");
        VerifyAndMigrateShippingGuideJob::dispatch($shippingGuide->id);
        $this->info("Job despachado a la cola.");
      }

      return 0;
    }

    if ($all) {
      // Verificar todas las guías pendientes (limitado por --limit)
      $limit = (int) $this->option('limit');
      $pendingGuides = ShippingGuides::whereIn('migration_status', [
        'pending',
        'in_progress',
        'failed'
      ])
      ->orderBy('id')
      ->limit($limit)
      ->get();

      if ($pendingGuides->isEmpty()) {
        $this->info("No hay guías pendientes de migración.");
        return 0;
      }

      $this->info("Encontradas {$pendingGuides->count()} guías pendientes de migración.");

      if ($useSync) {
        $bar = $this->output->createProgressBar($pendingGuides->count());
        $bar->start();

        $syncService = app(DatabaseSyncService::class);
        foreach ($pendingGuides as $guide) {
          try {
            $job = new VerifyAndMigrateShippingGuideJob($guide->id);
            $job->handle($syncService);
          } catch (\Exception $e) {
            $this->newLine();
            $this->error("Error en guía {$guide->document_number}: {$e->getMessage()}");
          }
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Verificación completada.");
      } else {
        $bar = $this->output->createProgressBar($pendingGuides->count());
        $bar->start();

        foreach ($pendingGuides as $guide) {
          VerifyAndMigrateShippingGuideJob::dispatch($guide->id);
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
    $this->line("Ejemplos:");
    $this->line("  php artisan shipping-guide:verify-migration --id=123");
    $this->line("  php artisan shipping-guide:verify-migration --all --limit=100");
    return 1;
  }
}