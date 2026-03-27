<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\CreditNoteSyncLog;
use App\Models\ap\compras\SupplierCreditNote;
use App\Models\ap\compras\SupplierCreditNoteDetail;
use App\Models\ap\postventa\gestionProductos\Products;
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

      // SEPARACIÓN: COMERCIAL vs POSTVENTA
      if ($purchaseOrder->type_operation_id === ApMasters::TIPO_OPERACION_COMERCIAL) {
        // Proceso COMERCIAL (no tocar)
        $result = $this->consultStoredProcedure($purchaseOrder->number, 'find');

        [$status, $creditNoteNumber, $errorMessage] = $this->processComercialCreditNote($purchaseOrder, $result);
      } elseif ($purchaseOrder->type_operation_id === ApMasters::TIPO_OPERACION_POSTVENTA) {
        // Proceso POSTVENTA (separado)
        $results = $this->consultStoredProcedure($purchaseOrder->number, 'all');

        [$status, $creditNoteNumber, $errorMessage] = $this->processPostventaCreditNote($purchaseOrder, $results);
      } else {
        $errorMessage = "La orden de compra #{$purchaseOrderId} tiene un tipo de operación no válido";
        throw new \Exception($errorMessage);
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
   * Procesa la Nota de Crédito para COMERCIAL
   * Mantiene la lógica original sin modificaciones
   */
  protected function processComercialCreditNote(PurchaseOrder $purchaseOrder, $result): array
  {
    $creditNoteNumber = null;
    $errorMessage = null;
    $status = 'error';

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

    return [$status, $creditNoteNumber, $errorMessage];
  }

  /**
   * Procesa la Nota de Crédito para POSTVENTA
   * Crea registros en SupplierCreditNote y SupplierCreditNoteDetail
   */
  protected function processPostventaCreditNote(PurchaseOrder $purchaseOrder, array $results): array
  {
    $creditNoteNumber = null;
    $errorMessage = null;
    $status = 'error';

    if (empty($results)) {
      $status = 'success';
      $errorMessage = 'No se encontró credit note en Dynamics';
      return [$status, $creditNoteNumber, $errorMessage];
    }

    DB::beginTransaction();
    try {
      // Tomar los datos de la primera fila para la cabecera
      $firstRow = $results[0];

      // Extraer el número de documento limpio (RE00000084)
      $documentId = trim($firstRow->DocumentoId ?? '');
      $creditNoteNumber = $documentId;

      // Buscar si ya existe la NC
      $existingCreditNote = SupplierCreditNote::where('credit_note_number', $creditNoteNumber)->first();
      if ($existingCreditNote) {
        DB::rollBack();
        $status = 'success';
        $errorMessage = 'La nota de crédito ya existe';
        return [$status, $creditNoteNumber, $errorMessage];
      }

      // Crear la cabecera de la Nota de Crédito
      $supplierCreditNote = SupplierCreditNote::create([
        'credit_note_number' => $creditNoteNumber,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_reception_id' => $purchaseOrder->reception?->id ?? null,
        'supplier_id' => $purchaseOrder->supplier_id,
        'credit_note_date' => isset($firstRow->DocumentoFechaEmision)
          ? \Carbon\Carbon::parse($firstRow->DocumentoFechaEmision)->format('Y-m-d')
          : now(),
        'reason' => SupplierCreditNote::REASON_RETURN,
        'subtotal' => abs((float)($firstRow->DocumentoValorCompra ?? 0)),
        'tax_amount' => abs((float)($firstRow->DocumentoIgv ?? 0)),
        'total' => abs((float)($firstRow->DocumentoPrecioCompra ?? 0)),
        'status' => SupplierCreditNote::STATUS_APPROVED,
        'notes' => 'Creada automáticamente desde Dynamics',
        'approved_by' => $purchaseOrder->created_by, // Asignar el mismo usuario que creó la OC
        'approved_at' => now(),
      ]);

      // Crear los detalles de la Nota de Crédito
      foreach ($results as $row) {
        $dynCode = trim($row->ArticuloId ?? '');

        // Buscar el producto por dyn_code
        $product = Products::where('dyn_code', $dynCode)->first();

        if (!$product) {
          \Log::warning("Producto no encontrado con dyn_code: {$dynCode}");
          continue;
        }

        SupplierCreditNoteDetail::create([
          'supplier_credit_note_id' => $supplierCreditNote->id,
          'product_id' => $product->id,
          'quantity' => abs((float)($row->ItemCantidadFacturada ?? 0)),
          'unit_price' => abs((float)($row->ItemCostoUnitario ?? 0)),
          'discount_percentage' => 0,
          'tax_rate' => 18, // IGV Perú
          'subtotal' => abs((float)($row->ItemCostoTotal ?? 0)),
          'notes' => trim($row->ArticuloNombre ?? ''),
        ]);
      }

      // Actualizar el campo credit_note_dynamics en la OC
      $purchaseOrder->update([
        'credit_note_dynamics' => $creditNoteNumber,
      ]);

      // Generar movimiento de inventario de salida por devolución
      // Esto resta del stock las cantidades que se están devolviendo al proveedor
      $inventoryService = new InventoryMovementService();
      $movement = $inventoryService->createReturnOutFromCreditNote($supplierCreditNote);

      \Log::info("Movimiento de inventario creado para NC {$creditNoteNumber}: {$movement->movement_number}");

      DB::commit();
      $status = 'success';
    } catch (\Exception $e) {
      DB::rollBack();
      $errorMessage = $e->getMessage();
      throw $e;
    }

    return [$status, $creditNoteNumber, $errorMessage];
  }

  /**
   * Consulta el Procedimiento Almacenado
   */
  protected function consultStoredProcedure(string $orderNumber, string $files): \stdClass|array
  {
    try {
      // Ejecutar el PA: EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion @pOrdenCompraId = 'OC1400000001'
      $results = DB::connection('dbtest')
        ->select("EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion @pOrdenCompraId = '{$orderNumber}'");

      // El PA debería retornar un resultado con el campo DocumentoNumero
      if (!empty($results) && isset($results[0])) {
        if ($files === 'find') {
          return $results[0];
        } elseif ($files === 'all') {
          return $results;
        }
      }

      return [];
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
