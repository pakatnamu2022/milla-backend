<?php

namespace App\Jobs;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncReceptionDetailsJob implements ShouldQueue
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
        $purchaseOrder = VehiclePurchaseOrder::find($this->purchaseOrderId);

        if (!$purchaseOrder) {
            Log::error("Purchase order not found: {$this->purchaseOrderId}");
            return;
        }

        try {
            // Esperar a que la recepción tenga ProcesoEstado = 1
            $this->waitForReceptionSync($purchaseOrder);

            // Sincronizar los detalles de la recepción
            $resource = new VehiclePurchaseOrderResource($purchaseOrder);
            $data = $resource->toArray(request());

            $syncService->sync('ap_vehicle_purchase_order_reception_det', $data, 'create');
            $syncService->sync('ap_vehicle_purchase_order_reception_det_s', $data, 'create');

            Log::info("Reception details synced successfully for PO: {$this->purchaseOrderId}");
        } catch (\Exception $e) {
            Log::error("Failed to sync reception details for PO {$this->purchaseOrderId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Espera a que la recepción tenga ProcesoEstado = 1
     */
    protected function waitForReceptionSync(VehiclePurchaseOrder $purchaseOrder): void
    {
        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $dbtp = DB::connection('dbtp')
                ->table('neInTbRecepcion')
                ->where('RecepcionId', $purchaseOrder->number_guide)
                ->first();

            if ($dbtp && $dbtp->ProcesoEstado == 1) {
                // Verificar si hay error
                if (!empty($dbtp->ProcesoError)) {
                    throw new \Exception("Error en sincronización de la recepción: {$dbtp->ProcesoError}");
                }
                return;
            }

            sleep(2);
            $attempt++;
        }

        throw new \Exception("Timeout esperando sincronización de la recepción");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed SyncReceptionDetailsJob for PO {$this->purchaseOrderId}: {$exception->getMessage()}");
    }
}
