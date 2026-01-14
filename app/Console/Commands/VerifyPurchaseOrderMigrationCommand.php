<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ValidatesPendingJobs;
use App\Http\Services\DatabaseSyncService;
use App\Jobs\VerifyAndMigratePurchaseOrderJob;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Console\Command;

class VerifyPurchaseOrderMigrationCommand extends Command
{
    use ValidatesPendingJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:verify-migration {--id= : ID de la orden de compra específica} {--all : Verificar todas las órdenes pendientes} {--limit=100 : Número máximo de órdenes a procesar (default: 100)} {--sync : Ejecutar inmediatamente sin usar cola}';

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
                $this->info("Job despachado a la cola.");
            }

            return 0;
        }

        if ($all) {
            // Validar límite de jobs pendientes antes de despachar (solo si usa cola)
            if (!$useSync && !$this->canDispatchMoreJobs(VerifyAndMigratePurchaseOrderJob::class)) {
                return 0;
            }

            // Verificar todas las órdenes pendientes (limitado por --limit)
            $limit = (int) $this->option('limit');
            $pendingOrders = PurchaseOrder::whereIn('migration_status', [
                'pending',
                'in_progress',
                'failed'
            ])
            ->orderBy('id')
            ->limit($limit)
            ->get();

            if ($pendingOrders->isEmpty()) {
                $this->info("No hay órdenes pendientes de migración.");
                return 0;
            }

            $this->info("Encontradas {$pendingOrders->count()} órdenes pendientes de migración.");

            if ($useSync) {
                $bar = $this->output->createProgressBar($pendingOrders->count());
                $bar->start();

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
                $bar = $this->output->createProgressBar($pendingOrders->count());
                $bar->start();

                foreach ($pendingOrders as $order) {
                    VerifyAndMigratePurchaseOrderJob::dispatch($order->id);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("Jobs despachados a la cola.");
            }

            return 0;
        }

        // Si no se especifica ninguna opción, mostrar ayuda
        $this->error("Debe especificar --id o --all para procesar órdenes.");
        $this->line("Ejemplos:");
        $this->line("  php artisan po:verify-migration --id=123");
        $this->line("  php artisan po:verify-migration --all --limit=100");
        return 1;
    }
}
