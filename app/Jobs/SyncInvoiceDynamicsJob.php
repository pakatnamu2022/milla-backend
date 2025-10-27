<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
      // Log::error("Error in SyncInvoiceDynamicsJob: {$e->getMessage()}");
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
    $purchaseOrders = VehiclePurchaseOrder::where(function ($query) {
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
      // Log::info("No hay órdenes de compra pendientes de sincronizar invoice_dynamics");
      return;
    }

    // Log::info("Procesando {$purchaseOrders->count()} órdenes de compra para sincronizar invoice_dynamics");

    foreach ($purchaseOrders as $order) {
      try {
        $this->processPurchaseOrder($order->id);
      } catch (\Exception $e) {
        // Log::error("Failed to process invoice_dynamics for purchase order {$order->id}: {$e->getMessage()}");
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
      // Log::error("Purchase order not found: {$purchaseOrderId}");
      return;
    }

    if (!$purchaseOrder->number) {
      // Log::warning("Purchase order {$purchaseOrderId} has no number, skipping");
      return;
    }

    // Consultar el PA para obtener la factura actual de Dynamics
    // Log::info("Consulting PA for purchase order: {$purchaseOrder->number}");

    try {
      $result = $this->consultStoredProcedure($purchaseOrder->number);

      if (!$result) {
        // Log::info("No result from PA for PO {$purchaseOrder->number}, skipping");
        return;
      }

      $status = trim($result->EstadoDocumento) === 'Hist. Recep.';
      $statusReception = trim($result->EstadoRecepcion) === 'Hist. Recep.';

      if (!$statusReception) {
        // Log::info("Invoice for PO {$purchaseOrder->number} is not in 'Hist. Recep.' Estado de Recepción, skipping");
        return;
      }

      $newInvoice = trim($result->NroDocProvDocumento);
      $newReceipt = trim($result->NumeroDocumento);

      // CASO 2: OC con factura y migration_status='completed' y tiene NC
      // Verificar si la factura cambió (nueva OC con punto)
      if ($purchaseOrder->migration_status === 'completed' && !empty($purchaseOrder->credit_note_dynamics)) {

        // Log::info("Invoice changed detected for PO {$purchaseOrder->number}: {$purchaseOrder->invoice_dynamics} -> {$newInvoice}");

        // Actualizar la factura y cambiar el estado a 'updated_with_nc'
        $purchaseOrder->update([
          'invoice_dynamics' => $newInvoice,
          'receipt_dynamics' => $newReceipt,
          'migration_status' => 'updated_with_nc',
          'status' => !empty($purchaseOrder->invoice_dynamics) && !($newInvoice == $newReceipt) // Si son iguales, marcar como false (anulada)
        ]);

        // Log::info("PO {$purchaseOrder->number} updated with new invoice and marked as 'updated_with_nc'");
        return;
      }

      if (!$status) {
        // Log::info("Invoice for PO {$purchaseOrder->number} is not in 'Hist. Recep.' status, skipping");
        return;
      }

      if (empty($result->NumeroDocumento) || empty($result->NroDocProvDocumento)) {
        // Log::info("No invoice found yet for PO {$purchaseOrder->number}");
        return;
      }

      // CASO 1: OC sin factura (flujo normal inicial)
      if (empty($purchaseOrder->invoice_dynamics)) {
        $purchaseOrder->update([
          'invoice_dynamics' => $newInvoice,
          'receipt_dynamics' => $newReceipt
        ]);

        // Log::info("Invoice Dynamics updated for PO {$purchaseOrder->number}: {$newReceipt} | {$newInvoice}");

        // Crear movimiento de vehículo en tránsito
        try {
          $vehicleMovementService = new VehicleMovementService();
          $vehicleMovementService->storeInTransitVehicleMovement($purchaseOrder->id);
          // Log::info("Vehicle movement created for PO {$purchaseOrder->number} with status VEHICULO EN TRAVESIA");
        } catch (\Exception $e) {
          // Log::error("Error creating vehicle movement for PO {$purchaseOrder->number}: {$e->getMessage()}");
        }
        return;
      }

      $hasInTransitMovement = $purchaseOrder->movements()
        ->where('ap_vehicle_status_id', ApVehicleStatus::VEHICULO_EN_TRAVESIA)
        ->exists();

      // CASO 3: OC con factura pero sin movimiento (recuperación)
      if (!empty($purchaseOrder->invoice_dynamics) && !$hasInTransitMovement) {
        // Log::info("Purchase order {$purchaseOrder->number} has invoice_dynamics but no movement, creating it");
        try {
          $vehicleMovementService = new VehicleMovementService();
          $vehicleMovementService->storeInTransitVehicleMovement($purchaseOrder->id);
          // Log::info("Vehicle movement created for PO {$purchaseOrder->number} with status VEHICULO EN TRAVESIA");
        } catch (Throwable $e) {
          // Log::error("Error creating vehicle movement for PO {$purchaseOrder->number}: {$e->getMessage()}");
        }
      }

    } catch (\Exception $e) {
      // Log::error("Error consulting PA for PO {$purchaseOrder->number}: {$e->getMessage()}");
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
      // Log::error("Error executing stored procedure for order {$orderNumber}: {$e->getMessage()}");
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    // Log::error("Failed SyncInvoiceDynamicsJob: {$exception->getMessage()}");
  }
}
