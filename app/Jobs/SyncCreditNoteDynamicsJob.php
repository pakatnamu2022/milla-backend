<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCreditNoteDynamicsJob implements ShouldQueue
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
   * Si no, procesa todas las OCs que no tienen credit_note_dynamics
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
      // Log::error("Error in SyncCreditNoteDynamicsJob: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Procesa todas las órdenes de compra sin credit_note_dynamics
   */
  protected function processAllPurchaseOrders(): void
  {
    // Obtener OCs que no tienen credit_note_dynamics o está vacío
    $purchaseOrders = VehiclePurchaseOrder::where(function ($query) {
      $query->whereNull('credit_note_dynamics')
        ->orWhere('credit_note_dynamics', '');
    })
      ->whereNotNull('number')
      ->get();

    if ($purchaseOrders->isEmpty()) {
      // Log::info("No hay órdenes de compra pendientes de sincronizar credit_note_dynamics");
      return;
    }

    // Log::info("Procesando {$purchaseOrders->count()} órdenes de compra para sincronizar credit_note_dynamics");

    foreach ($purchaseOrders as $order) {
      try {
        $this->processPurchaseOrder($order->id);
      } catch (\Exception $e) {
        // Log::error("Failed to process credit_note_dynamics for purchase order {$order->id}: {$e->getMessage()}");
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

    // Si ya tiene credit_note_dynamics, no volver a procesar
    if (!empty($purchaseOrder->credit_note_dynamics)) {
      // Log::info("Purchase order {$purchaseOrder->number} already has credit_note_dynamics: {$purchaseOrder->credit_note_dynamics}");
      return;
    }

    // Log::info("Consulting PA for credit note of purchase order: {$purchaseOrder->number}");

    try {
      // Ejecutar el Procedimiento Almacenado
      $result = $this->consultStoredProcedure($purchaseOrder->number);

      if ($result && !empty($result->DocumentoNumero)) {
        $credit_note = trim($result->DocumentoNumero);

        // Actualizar el campo credit_note_dynamics y marcar como anulada
        $purchaseOrder->update([
          'credit_note_dynamics' => $credit_note,
          'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO,
        ]);
        // Crear movimiento usando el servicio
        $movementService = new VehicleMovementService();
        $movementService->storeReturnedVehicleMovement($purchaseOrder->id, $credit_note);
        // Log::info("Credit Note Dynamics updated for PO {$purchaseOrder->number}: {$credit_note}");

        // NOTA: El job de actualización en Dynamics se disparará cuando el usuario edite manualmente la OC
        // Log::info("OC {$purchaseOrder->number} marcada con NC. Esperando edición manual para sincronizar con Dynamics.");
      } else {
        // Log::info("No credit note found yet for PO {$purchaseOrder->number}");
      }
    } catch (\Exception $e) {
      // Log::error("Error consulting PA for credit note PO {$purchaseOrder->number}: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Consulta el Procedimiento Almacenado
   */
  protected function consultStoredProcedure(string $orderNumber): ?object
  {
    try {
      // Ejecutar el PA: EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion @pOrdenCompraId = 'OC1400000001'
      $results = DB::connection('dbtest')
        ->select("EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion @pOrdenCompraId = '{$orderNumber}'");

      // El PA debería retornar un resultado con el campo DocumentoNumero
      if (!empty($results) && isset($results[0])) {
        return $results[0];
      }

      return null;
    } catch (\Exception $e) {
      // Log::error("Error executing stored procedure for credit note order {$orderNumber}: {$e->getMessage()}");
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    // Log::error("Failed SyncCreditNoteDynamicsJob: {$exception->getMessage()}");
  }
}
