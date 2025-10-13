<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\BusinessPartners;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSupplierDirectionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $supplierId
    ) {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(DatabaseSyncService $syncService): void
    {
        $supplier = BusinessPartners::find($this->supplierId);

        if (!$supplier) {
            Log::error("Supplier not found: {$this->supplierId}");
            return;
        }

        try {
            // Esperar a que el proveedor tenga ProcesoEstado = 1
            $this->waitForSupplierSync($supplier);

            // Sincronizar la dirección del proveedor
            $syncService->sync('business_partners_directions_ap_supplier', $supplier->toArray(), 'create');

            Log::info("Supplier direction synced successfully for supplier: {$this->supplierId}");
        } catch (\Exception $e) {
            Log::error("Failed to sync supplier direction for supplier {$this->supplierId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Espera a que el proveedor tenga ProcesoEstado = 1
     */
    protected function waitForSupplierSync(BusinessPartners $supplier): void
    {
        $maxAttempts = 30; // 30 intentos
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $dbtp = DB::connection('dbtp')
                ->table('neInTbProveedor')
                ->where('NumeroDocumento', $supplier->num_doc)
                ->first();

            if ($dbtp && $dbtp->ProcesoEstado == 1) {
                // Verificar si hay error
                if (!empty($dbtp->ProcesoError)) {
                    throw new \Exception("Error en sincronización del proveedor: {$dbtp->ProcesoError}");
                }
                return;
            }

            sleep(2); // Esperar 2 segundos antes de reintentar
            $attempt++;
        }

        throw new \Exception("Timeout esperando sincronización del proveedor");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed SyncSupplierDirectionJob for supplier {$this->supplierId}: {$exception->getMessage()}");
    }
}