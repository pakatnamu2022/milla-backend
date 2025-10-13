<?php

namespace App\Jobs;

use App\Models\ap\comercial\VehiclePurchaseOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncInvoiceDynamicsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $purchaseOrderId = null
    ) {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     * Si se proporciona un ID, procesa solo esa OC
     * Si no, procesa todas las OCs que no tienen invoice_dynamics
     */
    public function handle(): void
    {
        try {
            if ($this->purchaseOrderId) {
                $this->processPurchaseOrder($this->purchaseOrderId);
            } else {
                $this->processAllPurchaseOrders();
            }
        } catch (\Exception $e) {
            Log::error("Error in SyncInvoiceDynamicsJob: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Procesa todas las órdenes de compra sin invoice_dynamics
     */
    protected function processAllPurchaseOrders(): void
    {
        // Obtener OCs que no tienen invoice_dynamics o está vacío
        $purchaseOrders = VehiclePurchaseOrder::where(function ($query) {
            $query->whereNull('invoice_dynamics')
                  ->orWhere('invoice_dynamics', '');
        })
        ->whereNotNull('number')
        ->get();

        if ($purchaseOrders->isEmpty()) {
            Log::info("No hay órdenes de compra pendientes de sincronizar invoice_dynamics");
            return;
        }

        Log::info("Procesando {$purchaseOrders->count()} órdenes de compra para sincronizar invoice_dynamics");

        foreach ($purchaseOrders as $order) {
            try {
                $this->processPurchaseOrder($order->id);
            } catch (\Exception $e) {
                Log::error("Failed to process invoice_dynamics for purchase order {$order->id}: {$e->getMessage()}");
                // Continuar con la siguiente orden
                continue;
            }
        }
    }

    /**
     * Procesa una orden de compra específica
     */
    protected function processPurchaseOrder(int $purchaseOrderId): void
    {
        $purchaseOrder = VehiclePurchaseOrder::find($purchaseOrderId);

        if (!$purchaseOrder) {
            Log::error("Purchase order not found: {$purchaseOrderId}");
            return;
        }

        if (!$purchaseOrder->number) {
            Log::warning("Purchase order {$purchaseOrderId} has no number, skipping");
            return;
        }

        // Si ya tiene invoice_dynamics, no hacer nada
        if (!empty($purchaseOrder->invoice_dynamics)) {
            Log::info("Purchase order {$purchaseOrder->number} already has invoice_dynamics: {$purchaseOrder->invoice_dynamics}");
            return;
        }

        Log::info("Consulting PA for purchase order: {$purchaseOrder->number}");

        try {
            // Ejecutar el Procedimiento Almacenado
            $result = $this->consultStoredProcedure($purchaseOrder->number);

            if ($result && !empty($result->NumeroDocumento)) {
                // Actualizar el campo invoice_dynamics
                $purchaseOrder->update([
                    'invoice_dynamics' => $result->NumeroDocumento
                ]);

                Log::info("Invoice Dynamics updated for PO {$purchaseOrder->number}: {$result->NumeroDocumento}");
            } else {
                Log::info("No invoice found yet for PO {$purchaseOrder->number}");
            }
        } catch (\Exception $e) {
            Log::error("Error consulting PA for PO {$purchaseOrder->number}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Consulta el Procedimiento Almacenado
     */
    protected function consultStoredProcedure(string $orderNumber): ?object
    {
        try {
            // Ejecutar el PA: EXEC nePoReporteSeguimientoOrdenCompra_Factura @pOrdenCompraId = 'OC1400000001'
            $results = DB::connection('dbtest')
                ->select("EXEC nePoReporteSeguimientoOrdenCompra_Factura @pOrdenCompraId = '{$orderNumber}'");

            // El PA debería retornar un resultado con el campo NumeroDocumento
            if (!empty($results) && isset($results[0])) {
                return $results[0];
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error executing stored procedure for order {$orderNumber}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed SyncInvoiceDynamicsJob: {$exception->getMessage()}");
    }
}
