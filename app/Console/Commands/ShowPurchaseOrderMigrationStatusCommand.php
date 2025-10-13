<?php

namespace App\Console\Commands;

use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Illuminate\Console\Command;

class ShowPurchaseOrderMigrationStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:migration-status {--id= : ID de la orden de compra específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra el estado de migración de las órdenes de compra de vehículos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $purchaseOrderId = $this->option('id');

        if ($purchaseOrderId) {
            return $this->showSingleOrderStatus($purchaseOrderId);
        }

        return $this->showAllOrdersStatus();
    }

    /**
     * Muestra el estado de una orden específica
     */
    protected function showSingleOrderStatus(int $purchaseOrderId): int
    {
        $purchaseOrder = VehiclePurchaseOrder::with(['migrationLogs'])->find($purchaseOrderId);

        if (!$purchaseOrder) {
            $this->error("Orden de compra no encontrada: {$purchaseOrderId}");
            return 1;
        }

        $this->info("=== Orden de Compra: {$purchaseOrder->number} ===");
        $this->info("Estado de migración: {$purchaseOrder->migration_status}");
        if ($purchaseOrder->migrated_at) {
            $this->info("Migrado el: {$purchaseOrder->migrated_at->format('Y-m-d H:i:s')}");
        }

        $this->newLine();
        $this->info("=== Detalle de pasos de migración ===");

        if ($purchaseOrder->migrationLogs->isEmpty()) {
            $this->warn("No hay logs de migración para esta orden.");
            return 0;
        }

        $headers = ['Paso', 'Estado', 'Proceso Estado', 'Intentos', 'Último Intento', 'Error'];
        $rows = [];

        foreach ($purchaseOrder->migrationLogs as $log) {
            $rows[] = [
                $log->step,
                $log->status,
                $log->proceso_estado ?? 'N/A',
                $log->attempts,
                $log->last_attempt_at ? $log->last_attempt_at->format('Y-m-d H:i:s') : 'N/A',
                $log->error_message ? substr($log->error_message, 0, 50) . '...' : '',
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }

    /**
     * Muestra el resumen de todas las órdenes
     */
    protected function showAllOrdersStatus(): int
    {
        $this->info("=== Resumen de Migración de Órdenes de Compra ===");

        $statuses = [
            'pending' => 'Pendientes',
            'in_progress' => 'En Progreso',
            'completed' => 'Completadas',
            'failed' => 'Fallidas',
        ];

        foreach ($statuses as $status => $label) {
            $count = VehiclePurchaseOrder::where('migration_status', $status)->count();
            $this->info("{$label}: {$count}");
        }

        $this->newLine();

        // Mostrar órdenes pendientes o con problemas
        $pendingOrders = VehiclePurchaseOrder::whereIn('migration_status', [
            'pending',
            'in_progress',
            'failed'
        ])->limit(20)->get();

        if ($pendingOrders->isNotEmpty()) {
            $this->info("=== Últimas 20 órdenes pendientes/fallidas ===");

            $headers = ['ID', 'Número OC', 'Estado', 'Creada', 'Último Intento'];
            $rows = [];

            foreach ($pendingOrders as $order) {
                $lastLog = $order->migrationLogs()
                    ->whereNotNull('last_attempt_at')
                    ->orderBy('last_attempt_at', 'desc')
                    ->first();

                $rows[] = [
                    $order->id,
                    $order->number,
                    $order->migration_status,
                    $order->created_at->format('Y-m-d H:i'),
                    $lastLog?->last_attempt_at?->format('Y-m-d H:i') ?? 'N/A',
                ];
            }

            $this->table($headers, $rows);
        }

        $this->newLine();
        $this->info("Usa --id=<ID> para ver el detalle de una orden específica.");

        return 0;
    }
}
