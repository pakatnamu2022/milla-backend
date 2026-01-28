<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

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
    $this->onQueue('credit_note_sync');
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
      throw $e;
    }
  }

  /**
   * Procesa todas las órdenes de compra sin credit_note_dynamics
   */
  protected function processAllPurchaseOrders(): void
  {
    // Obtener OCs que no tienen credit_note_dynamics o está vacío
    $purchaseOrders = PurchaseOrder::where(function ($query) {
      $query->whereNull('credit_note_dynamics')
        ->orWhere('credit_note_dynamics', '');
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
   * @throws Exception
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

    // Si ya tiene credit_note_dynamics, no volver a procesar
    if (!empty($purchaseOrder->credit_note_dynamics)) {
      return;
    }


    try {
      // Ejecutar el Procedimiento Almacenado
      $result = $this->consultStoredProcedure($purchaseOrder->number);

      if ($result && !empty($result->DocumentoNumero)) {
        $credit_note = trim($result->DocumentoNumero);

        // Actualizar el campo credit_note_dynamics y marcar como anulada
        $purchaseOrder->update([
          'credit_note_dynamics' => $credit_note,
        ]);

        if ($purchaseOrder->vehicle_movement_id) {
          // Crear movimiento usando el servicio
          $movementService = new VehicleMovementService();
          $movementService->storeReturnedVehicleMovement($purchaseOrder->id, $credit_note);
        }

        // NOTA: El job de actualización en Dynamics se disparará cuando el usuario edite manualmente la OC
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
      // Ejecutar el PA: EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion @pOrdenCompraId = 'OC1400000001'
      $results = DB::connection('dbtest')
        ->select("EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion @pOrdenCompraId = '{$orderNumber}'");

      // El PA debería retornar un resultado con el campo DocumentoNumero
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
