<?php

namespace App\Jobs;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPurchaseOrderReceptionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $purchaseOrderId
    ) {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(DatabaseSyncService $syncService): void
    {
        $purchaseOrder = VehiclePurchaseOrder::with(['vehicleStatus'])->find($this->purchaseOrderId);

        if (!$purchaseOrder) {
            Log::error("Purchase order not found: {$this->purchaseOrderId}");
            return;
        }

        try {
            // Validar que la OC esté en estado 1 (procesada)
            $this->waitForPurchaseOrderSync($purchaseOrder);

            // Sincronizar la recepción (NI)
            $resource = new VehiclePurchaseOrderResource($purchaseOrder);
            $syncService->sync('ap_vehicle_purchase_order_reception', $resource->toArray(request()), 'create');

            // Despachar el job para sincronizar los detalles de la recepción
            SyncReceptionDetailsJob::dispatch($this->purchaseOrderId);

            Log::info("Purchase order reception synced successfully for PO: {$this->purchaseOrderId}");
        } catch (\Exception $e) {
            Log::error("Failed to sync purchase order reception for PO {$this->purchaseOrderId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Verifica que la OC tenga ProcesoEstado = 1
     */
    protected function waitForPurchaseOrderSync(VehiclePurchaseOrder $purchaseOrder): void
    {
        $dbtp = DB::connection('dbtp')
            ->table('neInTbOrdenCompra')
            ->where('OrdenCompraId', $purchaseOrder->number)
            ->first();

        if (!$dbtp) {
            throw new \Exception("OC no encontrada en tabla intermedia");
        }

        if ($dbtp->ProcesoEstado != 1) {
            throw new \Exception("OC aún no procesada. ProcesoEstado: {$dbtp->ProcesoEstado}");
        }

        // Verificar si hay error
        if (!empty($dbtp->ProcesoError)) {
            throw new \Exception("Error en sincronización de la OC: {$dbtp->ProcesoError}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed SyncPurchaseOrderReceptionJob for PO {$this->purchaseOrderId}: {$exception->getMessage()}");
    }
}