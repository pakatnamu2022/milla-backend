<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\CreditNoteSyncLog;
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
    $startTime = microtime(true); // Inicio de medición
    $creditNoteNumber = null;
    $errorMessage = null;
    $status = 'error';

    try {
      $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

      if (!$purchaseOrder) {
        $errorMessage = "Orden de compra #{$purchaseOrderId} no encontrada";
        throw new \Exception($errorMessage);
      }

      if (!$purchaseOrder->number) {
        $errorMessage = "La orden de compra #{$purchaseOrderId} no tiene número asignado";
        throw new \Exception($errorMessage);
      }

      // Si ya tiene credit_note_dynamics, no volver a procesar
      if (!empty($purchaseOrder->credit_note_dynamics)) {
        $status = 'success';
        $creditNoteNumber = $purchaseOrder->credit_note_dynamics;
        return; // Se registrará en finally
      }

      // Ejecutar el Procedimiento Almacenado
      $result = $this->consultStoredProcedure($purchaseOrder->number);

      if ($result && !empty($result->DocumentoNumero)) {
        $creditNoteNumber = trim($result->DocumentoNumero);

        // Actualizar el campo credit_note_dynamics
        $purchaseOrder->update([
          'credit_note_dynamics' => $creditNoteNumber,
        ]);

        if ($purchaseOrder->vehicle_movement_id) {
          // Crear movimiento usando el servicio
          $movementService = new VehicleMovementService();
          $movementService->storeReturnedVehicleMovement($purchaseOrder->id, $creditNoteNumber);
        }

        $status = 'success';
      } else {
        // No se encontró NC en Dynamics, pero no es un error
        $status = 'success';
        $errorMessage = 'No se encontró credit note en Dynamics';
      }
    } catch (\Exception $e) {
      $errorMessage = $e->getMessage();
      throw $e; // Re-lanzar para que el job se marque como fallido
    } finally {
      // Calcular tiempo de ejecución
      $executionTime = (int)((microtime(true) - $startTime) * 1000); // en milisegundos

      // Registrar en log
      CreditNoteSyncLog::create([
        'purchase_order_id' => $purchaseOrderId,
        'attempted_at' => now(),
        'status' => $status,
        'credit_note_number' => $creditNoteNumber,
        'error_message' => $errorMessage,
        'execution_time' => $executionTime,
      ]);
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
    // Si el job falla completamente (no se pudo ejecutar processPurchaseOrder)
    // registrar en el log si aún no se registró
    if ($this->purchaseOrderId) {
      // Verificar si ya se registró en processPurchaseOrder
      $existsLog = CreditNoteSyncLog::where('purchase_order_id', $this->purchaseOrderId)
        ->whereDate('attempted_at', now()->toDateString())
        ->exists();

      if (!$existsLog) {
        CreditNoteSyncLog::create([
          'purchase_order_id' => $this->purchaseOrderId,
          'attempted_at' => now(),
          'status' => 'error',
          'error_message' => $exception->getMessage(),
        ]);
      }
    }
  }
}
