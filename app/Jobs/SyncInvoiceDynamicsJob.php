<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * php artisan queue:work --tries=3
 */
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
  )
  {
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
      throw $e;
    }
  }

  /**
   * Procesa todas las órdenes de compra sin invoice_dynamics
   * O las que están completed con NC (para detectar cambios de factura)
   */
  protected function processAllPurchaseOrders(): void
  {
    // Obtener OCs que:
    // 1. No tienen invoice_dynamics (flujo normal)
    // 2. Están completed y tienen credit_note_dynamics (para detectar cambio de factura)
    $purchaseOrders = PurchaseOrder::where(function ($query) {
      $query->where(function ($q) {
        // Caso 1: Sin invoice
        $q->whereNull('invoice_dynamics')
          ->orWhere('invoice_dynamics', '');
      })->orWhere(function ($q) {
        // Caso 2: Completed con NC (para detectar cambio de factura)
        $q->where('migration_status', 'completed')
          ->whereNotNull('credit_note_dynamics')
          ->where('credit_note_dynamics', '!=', '');
      });
    })
      ->whereNotNull('number')
      ->get();

    if ($purchaseOrders->isEmpty()) {
      return;
    }


    foreach ($purchaseOrders as $order) {
      try {
        $this->processPurchaseOrder($order->id);
      } catch (\Exception $e) {
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
    $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

    if (!$purchaseOrder) {
      return;
    }

    if (!$purchaseOrder->number) {
      return;
    }

    // Consultar el PA para obtener la factura actual de Dynamics
    try {
      $result = $this->consultStoredProcedure($purchaseOrder->number);

      if (!$result) {
        return;
      }

      $status = trim($result->EstadoDocumento) === 'Hist. Recep.';
      $statusReception = trim($result->EstadoRecepcion) === 'Hist. Recep.';

      if (!$statusReception) {
        return;
      }

      $newInvoice = trim($result->NroDocProvDocumento);
      $newReceipt = trim($result->NumeroDocumento);

      // CASO 1: OC con factura y migration_status='completed' y tiene NC
      // Verificar si la factura cambió (nueva OC con punto)
      if ($purchaseOrder->migration_status === 'completed' && !empty($purchaseOrder->credit_note_dynamics)) {

        // Actualizar la factura y cambiar el estado a 'updated_with_nc'
        $purchaseOrder->update([
          'invoice_dynamics' => $newInvoice,
          'receipt_dynamics' => $newReceipt,
          'migration_status' => 'updated_with_nc',
          'status' => !empty($purchaseOrder->invoice_dynamics) && !($newInvoice == $newReceipt) // Si son iguales, marcar como false (anulada)
        ]);

        return;
      }

      if (!$status) {
        return;
      }

      if (empty($result->NumeroDocumento) || empty($result->NroDocProvDocumento)) {
        return;
      }

      /**
       * CASO 2: OC sin factura (flujo normal inicial)
       */
      if (empty($purchaseOrder->invoice_dynamics)) {
        $purchaseOrder->update([
          'invoice_dynamics' => $newInvoice,
          'receipt_dynamics' => $newReceipt
        ]);


        /**
         * Crear movimiento de vehículo en tránsito
         */
        if ($purchaseOrder->vehicle_movement_id) {
          try {
            $vehicleMovementService = new VehicleMovementService();
            $vehicleMovementService->storeInTransitVehicleMovement($purchaseOrder->id);
          } catch (\Exception $e) {
          }
        }
        return;
      }

      $hasInTransitMovement = $purchaseOrder->vehicle()->vehicleMovements()
        ->where('ap_vehicle_status_id', ApVehicleStatus::VEHICULO_EN_TRAVESIA)
        ->exists();

      /**
       * CASO 3: OC con factura pero sin movimiento (recuperación)
       */
      if (!empty($purchaseOrder->invoice_dynamics) && !$hasInTransitMovement && $purchaseOrder->vehicle_movement_id) {
        try {
          $vehicleMovementService = new VehicleMovementService();
          $vehicleMovementService->storeInTransitVehicleMovement($purchaseOrder->id);
        } catch (Throwable $e) {
        }
      }

    } catch (\Exception $e) {
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

      // El PA debería retornar un resultado con el campo NroDocProvDocumento
      if (!empty($results) && isset($results[0])) {
        return $results[0];
      }

      return null;
    } catch (\Exception $e) {
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
  }
}
