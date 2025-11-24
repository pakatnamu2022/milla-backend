<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseReceptionResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReceptionService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseReception::class,
      $request,
      PurchaseReception::filters,
      PurchaseReception::sorts,
      PurchaseReceptionResource::class,
    );
  }

  public function find($id)
  {
    $reception = PurchaseReception::where('id', $id)->first();

    if (!$reception) {
      throw new Exception('Recepción no encontrada');
    }

    return $reception;
  }

  public function store(Mixed $data)
  {
    DB::beginTransaction();
    try {
      // Validate purchase order exists
      $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);

      // VALIDACIÓN 1: Verificar que la orden no tenga recepciones activas
      if ($purchaseOrder->hasActiveReceptions()) {
        throw new Exception('Esta orden de compra ya tiene una recepción activa. No se permite recepcionar nuevamente.');
      }

      // VALIDACIÓN 2: La fecha de recepción no puede ser anterior a la fecha de emisión de la orden
      $receptionDate = Carbon::parse($data['reception_date']);
      if ($receptionDate->lt($purchaseOrder->emission_date)) {
        throw new Exception('La fecha de recepción no puede ser anterior a la fecha de emisión de la orden de compra (' . $purchaseOrder->emission_date->format('Y-m-d') . ')');
      }

      // Generate reception number
      $data['reception_number'] = $this->generateReceptionNumber();

      // Set received by if not provided
      $data['received_by'] = Auth::id();

      // Create reception header
      $details = $data['details'];
      unset($data['details']);

      $reception = PurchaseReception::create($data);

      // Process details
      $totalItems = 0;
      $totalQuantity = 0;
      $allItemsFullyReceived = true;

      foreach ($details as $detail) {
        // Validate detail
        $this->validateReceptionDetail($detail, $purchaseOrder);

        // Set reception id
        $detail['purchase_reception_id'] = $reception->id;

        // Create detail
        PurchaseReceptionDetail::create($detail);

        $totalItems++;
        $totalQuantity += $detail['quantity_received'];

        // Check if ALL OC items are fully received (only for ORDERED items)
        if ($detail['reception_type'] === 'ORDERED' && $detail['purchase_order_item_id']) {
          $orderItem = PurchaseOrderItem::find($detail['purchase_order_item_id']);
          if ($orderItem) {
            // quantity_received already contains only good items
            $quantityReceived = $detail['quantity_received'];
            $totalReceivedNow = $orderItem->quantity_received + $quantityReceived;

            // If not fully received, mark as incomplete
            if ($totalReceivedNow < $orderItem->quantity) {
              $allItemsFullyReceived = false;
            }
          }
        }
      }

      // CÁLCULO AUTOMÁTICO DE STATUS:
      // - APPROVED: Si se recepcionó todo lo pedido
      // - INCOMPLETE: Si falta mercancía
      $status = $allItemsFullyReceived ? 'APPROVED' : 'INCOMPLETE';

      // Update reception totals and status
      $reception->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
        'status' => $status,
      ]);

      // SIEMPRE procesar la recepción (tanto APPROVED como INCOMPLETE)
      // Actualiza OC, crea movimiento de inventario y actualiza stock
      $this->processReception($reception);

      DB::commit();
      return new PurchaseReceptionResource($reception->load([
        'purchaseOrder',
        'warehouse',
        'receivedByUser',
        'details.product'
      ]));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new PurchaseReceptionResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    DB::beginTransaction();
    try {
      $reception = $this->find($data['id']);

      $purchaseOrder = $reception->purchaseOrder;

      // VALIDACIÓN: La fecha de recepción no puede ser anterior a la fecha de emisión de la orden
      if (isset($data['reception_date'])) {
        $receptionDate = Carbon::parse($data['reception_date']);
        if ($receptionDate->lt($purchaseOrder->emission_date)) {
          throw new Exception('La fecha de recepción no puede ser anterior a la fecha de emisión de la orden de compra (' . $purchaseOrder->emission_date->format('Y-m-d') . ')');
        }
      }

      // Update only reception header fields
      $reception->update($data);

      DB::commit();
      return new PurchaseReceptionResource($reception->fresh([
        'purchaseOrder',
        'warehouse',
        'receivedByUser',
        'details.product'
      ]));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $reception = $this->find($id);

      // IMPORTANTE: Revertir los cambios antes de eliminar
      $stockService = new ProductWarehouseStockService();

      foreach ($reception->details as $detail) {
        $quantityReceived = $detail->quantity_received;
        $observedQuantity = $detail->observed_quantity ?? 0;
        $totalProcessed = $quantityReceived + $observedQuantity;

        // Revertir stock físico (quantity) - para TODOS los tipos
        if ($quantityReceived > 0) {
          $stockService->removeStock(
            $detail->product_id,
            $reception->warehouse_id,
            $quantityReceived
          );
        }

        // Solo revertir PurchaseOrderItem para items de tipo ORDERED
        if ($detail->reception_type === 'ORDERED' && $detail->purchase_order_item_id) {
          $orderItem = PurchaseOrderItem::find($detail->purchase_order_item_id);
          if ($orderItem) {
            // Revertir quantity_received en PurchaseOrderItem
            $orderItem->quantity_received -= $quantityReceived;
            $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
            $orderItem->save();

            // Revertir quantity_pending_credit_note si había observaciones
            if ($observedQuantity > 0) {
              $stockService->removePendingCreditNote(
                $detail->product_id,
                $reception->warehouse_id,
                $observedQuantity
              );
            }

            // Volver a agregar a quantity_in_transit
            $stockService->addInTransitStock(
              $detail->product_id,
              $reception->warehouse_id,
              $totalProcessed
            );
          }
        }
      }

      // Delete associated inventory movements and details
      $inventoryMovements = InventoryMovement::where('reference_type', PurchaseReception::class)
        ->where('reference_id', $reception->id)
        ->get();

      foreach ($inventoryMovements as $movement) {
        // Delete movement details first (soft delete)
        $movement->details()->delete();
        // Delete movement (soft delete)
        $movement->delete();
      }

      // Delete all details first (soft delete)
      $reception->details()->delete();

      // Delete reception (soft delete)
      $reception->delete();

      DB::commit();
      return response()->json(['message' => 'Recepción eliminada correctamente. Se han revertido todas las cantidades y movimientos de inventario.']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Process reception (ALWAYS - both APPROVED and INCOMPLETE)
   * - Updates PurchaseOrderItem quantities
   * - Creates inventory movement
   * - Updates product_warehouse_stock (quantity, quantity_in_transit, quantity_pending_credit_note)
   *
   * IMPORTANT: Frontend sends:
   * - quantity_received: Physical units that arrived in good condition
   * - observed_quantity: Units that arrived damaged/missing
   * These are SEPARATE values, NOT to be subtracted from each other
   */
  protected function processReception($reception)
  {
    $stockService = new ProductWarehouseStockService();

    // Update purchase order item quantities
    foreach ($reception->details as $detail) {
      // Only update OC items for ORDERED type
      if ($detail->reception_type === 'ORDERED' && $detail->purchase_order_item_id) {
        $orderItem = PurchaseOrderItem::find($detail->purchase_order_item_id);
        if ($orderItem) {
          // quantity_received already represents only the good items
          $quantityReceived = $detail->quantity_received;
          $observedQuantity = $detail->observed_quantity ?? 0;
          $totalProcessed = $quantityReceived + $observedQuantity;

          // Update quantities in PurchaseOrderItem
          $orderItem->quantity_received += $quantityReceived;
          $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
          $orderItem->save();

          // Update quantity_pending_credit_note if there are observations
          if ($observedQuantity > 0) {
            $stockService->addPendingCreditNote(
              $detail->product_id,
              $reception->warehouse_id,
              $observedQuantity
            );
          }

          // Remove from in-transit (total processed: received + observed)
          $stockService->removeInTransitStock(
            $detail->product_id,
            $reception->warehouse_id,
            $totalProcessed
          );
        }
      }
    }

    // Create inventory movement and update stock (quantity field)
    // This will add ONLY the quantity_received to physical stock
    $inventoryMovementService = new InventoryMovementService();
    try {
      $inventoryMovementService->createFromPurchaseReception($reception);
    } catch (Exception $e) {
      throw new Exception('Error al crear el movimiento de inventario y actualizar stock: ' . $e->getMessage());
    }
  }

  /**
   * Validate reception detail
   */
  protected function validateReceptionDetail($detail, $purchaseOrder)
  {
    // ORDERED type must have purchase_order_item_id
    if ($detail['reception_type'] === 'ORDERED' && empty($detail['purchase_order_item_id'])) {
      throw new Exception('Los productos ORDERED deben tener purchase_order_item_id');
    }

    // BONUS/GIFT/SAMPLE must NOT have purchase_order_item_id
    if (in_array($detail['reception_type'], ['BONUS', 'GIFT', 'SAMPLE']) && !empty($detail['purchase_order_item_id'])) {
      throw new Exception('Los productos BONUS/GIFT/SAMPLE no deben tener purchase_order_item_id');
    }

    // observed_quantity must be less than or equal to quantity_received
    $observedQuantity = $detail['observed_quantity'] ?? 0;
    $quantityReceived = $detail['quantity_received'];

    if ($observedQuantity > $quantityReceived) {
      throw new Exception('La cantidad observada no puede ser mayor a la cantidad recibida');
    }

    // If observed_quantity > 0, must have reason_observation
    if ($observedQuantity > 0 && empty($detail['reason_observation'])) {
      throw new Exception('Debe indicar la razón de la observación cuando hay productos observados');
    }

    // Validate that we don't receive more than ordered (for ORDERED type)
    if ($detail['reception_type'] === 'ORDERED' && !empty($detail['purchase_order_item_id'])) {
      $orderItem = PurchaseOrderItem::find($detail['purchase_order_item_id']);
      if ($orderItem) {
        $quantityAccepted = $quantityReceived - $observedQuantity;
        $totalThatWillBeReceived = $orderItem->quantity_received + $quantityAccepted;
        if ($totalThatWillBeReceived > $orderItem->quantity) {
          throw new Exception("No puede recibir más de lo ordenado para el producto ID {$detail['product_id']}. Ordenado: {$orderItem->quantity}, Ya recibido: {$orderItem->quantity_received}, Intenta recibir: {$quantityAccepted}");
        }
      }
    }
  }

  /**
   * Generate unique reception number
   */
  protected function generateReceptionNumber()
  {
    $year = date('Y');
    $lastReception = PurchaseReception::withTrashed()
      ->whereYear('created_at', $year)
      ->orderBy('id', 'desc')
      ->first();

    $correlative = 1;
    if ($lastReception) {
      // Extract number from REC-2025-0001
      $parts = explode('-', $lastReception->reception_number);
      if (count($parts) === 3) {
        $correlative = intval($parts[2]) + 1;
      }
    }

    return sprintf('REC-%s-%04d', $year, $correlative);
  }

  /**
   * Get receptions by purchase order
   */
  public function getByPurchaseOrder($purchaseOrderId)
  {
    $receptions = PurchaseReception::byPurchaseOrder($purchaseOrderId)
      ->with(['warehouse', 'receivedByUser', 'reviewedByUser', 'details.product'])
      ->get();

    return PurchaseReceptionResource::collection($receptions);
  }
}
