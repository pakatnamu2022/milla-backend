<?php

namespace App\Console\Commands;

use App\Http\Services\DatabaseSyncService;
use App\Jobs\VerifyAndMigratePurchaseOrderJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Console\Command;

class VerifyPurchaseOrderMigrationCommand extends Command
{

  /**
   * The name and signature of the console command.
   * @var string
   */
  protected $signature = 'po:verify-migration {--id= : ID de la orden de compra específica} {--all : Verificar todas las órdenes pendientes} {--sync : Ejecutar inmediatamente sin usar cola}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y migra órdenes de compra de vehículos pendientes';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $purchaseOrderId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    // Por defecto usa cola, solo sync si se especifica --sync
    $useSync = $sync;

    if ($purchaseOrderId) {
      // Verificar una orden específica
      $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

      if (!$purchaseOrder) {
        $this->error("Orden de compra no encontrada: {$purchaseOrderId}");
        return 1;
      }

      if ($useSync) {
        $this->info("Ejecutando verificación para la orden: {$purchaseOrder->number}");
        $syncService = app(DatabaseSyncService::class);
        $job = new VerifyAndMigratePurchaseOrderJob($purchaseOrder->id);

        try {
          $job->handle($syncService);
          $this->info("✓ Verificación completada.");
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        $this->info("Despachando job de verificación para la orden: {$purchaseOrder->number}");
        VerifyAndMigratePurchaseOrderJob::dispatch($purchaseOrder->id);
        $this->info("Job despachado a la cola (" . ($purchaseOrder->id + 1) . ")");
      }

      return 0;
    }

    if ($all) {
      $pendingOrders = PurchaseOrder::whereIn('migration_status', [
        VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
        VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
      ])
        ->orderBy('id', 'desc')
        ->whereDoesntHave('migrationLogs', fn($q) => $q->where('attempts', '>=', 5))
        ->get();

      if ($pendingOrders->isEmpty()) {
        $this->info("No hay órdenes pendientes de migración.");
        return 0;
      }

      $this->info("Encontradas {$pendingOrders->count()} órdenes pendientes de migración.");

      $bar = $this->output->createProgressBar($pendingOrders->count());
      $bar->start();

      if ($useSync) {
        $syncService = app(DatabaseSyncService::class);
        foreach ($pendingOrders as $order) {
          try {
            $job = new VerifyAndMigratePurchaseOrderJob($order->id);
            $job->handle($syncService);
          } catch (\Exception $e) {
            $this->newLine();
            $this->error("Error en orden {$order->number}: {$e->getMessage()}");
          }
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Verificación completada.");
      } else {
        foreach ($pendingOrders as $order) {
          VerifyAndMigratePurchaseOrderJob::dispatch($order->id);
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        foreach ($pendingOrders as $order) {
          $this->info("Job despachado para la orden id={$order->id}; number={$order->number}");
        }
      }

      return 0;
    }

    // Si no se especifica ninguna opción, mostrar ayuda
    $this->error("Debe especificar --id o --all para procesar órdenes.");
    $this->line("Ejemplos:");
    $this->line("  php artisan po:verify-migration --id=123");
    $this->line("  php artisan po:verify-migration --all");
    return 1;
  }
}
