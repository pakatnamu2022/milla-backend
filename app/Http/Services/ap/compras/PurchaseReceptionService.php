<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseReceptionResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\ap\postventa\taller\ApSupplierOrderService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequestDetails;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use App\Models\ap\postventa\taller\ApSupplierOrder;
use App\Models\ap\postventa\taller\ApSupplierOrderDetails;
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
      // Validate supplier order exists
      $supplierOrder = ApSupplierOrder::findOrFail($data['ap_supplier_order_id']);

      if (is_null($supplierOrder->approved_by)) {
        throw new Exception('La OC debe estar aprobada para crear la recepción');
      }

      // VALIDACIÓN 1: La fecha de recepción no puede ser anterior a la fecha de la orden
      $receptionDate = Carbon::parse($data['reception_date']);
      $orderDate = Carbon::parse($supplierOrder->order_date);
      if ($receptionDate->lt($orderDate)) {
        throw new Exception('La fecha de recepción no puede ser anterior a la fecha de la orden de compra (' . $orderDate->format('Y-m-d') . ')');
      }

      //VALIDACIÓN 2: El reception_type debe ser diferente a COMPLETE
      if ($supplierOrder->reception_type === ApSupplierOrder::COMPLETE) {
        throw new Exception('No se pueden crear recepciones para una orden de proveedor que ya está completa');
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

      foreach ($details as $detail) {
        // Validate detail
        $this->validateReceptionDetail($detail, $supplierOrder);

        // Set reception id
        $detail['purchase_reception_id'] = $reception->id;

        // Create detail
        PurchaseReceptionDetail::create($detail);

        $totalItems++;
        $totalQuantity += $detail['quantity_received'];
      }

      // CÁLCULO AUTOMÁTICO DE STATUS Y RECEPTION_TYPE:

      // 1. Verificar si con esta recepción se completó la orden de proveedor
      $allItemsFullyReceived = $this->checkIfAllItemsReceived($supplierOrder);

      // 2. Calcular STATUS (estado general después de esta recepción):
      // - APPROVED: Si ya se recepcionó todo lo pedido (considerando todas las recepciones)
      // - PARTIAL: Si aún falta mercancía por recepcionar
      $status = $allItemsFullyReceived ? 'APPROVED' : 'PARTIAL';

      // 3. Calcular RECEPTION_TYPE (tipo de esta recepción específica):
      // - COMPLETE: Si en esta recepción se está recibiendo todo lo que faltaba
      // - PARTIAL: Si solo se recibe una parte (quedarán más recepciones pendientes)
      $receptionType = $allItemsFullyReceived ? 'COMPLETE' : 'PARTIAL';

      // Update reception totals, status and reception_type
      $reception->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
        'status' => $status,
        'reception_type' => $receptionType,
      ]);

      // ACTUALIZAR RECEPTION_TYPE DEL ApSupplierOrder
      $this->updateSupplierOrderReceptionType($supplierOrder);

      // OPCIONAL: Notificar usuarios si la orden tiene solicitudes vinculadas
      if ($reception->supplierOrder) {
        $this->notifyRequestUsers($reception->supplierOrder);
      }

      DB::commit();
      return new PurchaseReceptionResource($reception->load([
        'supplierOrder',
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

      // VALIDACIÓN: La fecha de recepción no puede ser anterior a la fecha de la orden
      if (isset($data['reception_date'])) {
        $receptionDate = Carbon::parse($data['reception_date']);

        // Verificar contra la orden de proveedor (siempre debe existir)
        if ($reception->supplierOrder) {
          $orderDate = Carbon::parse($reception->supplierOrder->order_date);
          if ($receptionDate->lt($orderDate)) {
            throw new Exception('La fecha de recepción no puede ser anterior a la fecha de la orden de compra (' . $orderDate->format('Y-m-d') . ')');
          }
        }

        // Si también tiene purchase order, verificar contra esa fecha
        if ($reception->purchaseOrder) {
          if ($receptionDate->lt($reception->purchaseOrder->emission_date)) {
            throw new Exception('La fecha de recepción no puede ser anterior a la fecha de emisión de la orden de compra (' . $reception->purchaseOrder->emission_date->format('Y-m-d') . ')');
          }
        }
      }

      // Update only reception header fields
      $reception->update($data);

      DB::commit();
      return new PurchaseReceptionResource($reception->fresh([
        'supplierOrder',
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

      // VALIDACIÓN: No permitir eliminar si ya tiene PurchaseOrder (factura) asociada
      if ($reception->purchase_order_id) {
        throw new Exception('No se puede eliminar una recepción que ya tiene una factura asociada. Debe eliminar primero la factura.');
      }

      // Obtener el supplier order antes de eliminar
      $supplierOrder = $reception->supplierOrder;

      // Delete reception (soft delete) - details will be deleted automatically via boot method
      $reception->delete();

      // Actualizar reception_type del supplier order si existe
      if ($supplierOrder) {
        $this->updateSupplierOrderReceptionType($supplierOrder);
      }

      DB::commit();
      return response()->json(['message' => 'Recepción eliminada correctamente.']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  protected function checkIfAllItemsReceived($supplierOrder): bool
  {
    // Obtener todos los items de la orden de proveedor
    $supplierOrderDetails = $supplierOrder->details;

    foreach ($supplierOrderDetails as $orderDetail) {
      // Calcular cuánto se ha recibido ACEPTADO de este producto (incluyendo la recepción actual)
      // Solo contamos quantity_received - observed_quantity (cantidades aceptadas)
      $receptionDetails = PurchaseReceptionDetail::whereHas('reception', function ($query) use ($supplierOrder) {
        $query->where('ap_supplier_order_id', $supplierOrder->id)
          ->whereNull('deleted_at');
      })
        ->where('product_id', $orderDetail->product_id)
        ->where('reception_type', PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED)
        ->get();

      $totalAccepted = 0;
      foreach ($receptionDetails as $detail) {
        $observedQty = $detail->observed_quantity ?? 0;
        $totalAccepted += ($detail->quantity_received - $observedQty);
      }

      // Si algún item no está completamente recibido, retornar false
      if ($totalAccepted < $orderDetail->quantity) {
        return false;
      }
    }

    return true;
  }

  protected function validateReceptionDetail($detail, $supplierOrder)
  {
    // BONUS/GIFT/SAMPLE must NOT have purchase_order_item_id
    if (in_array($detail['reception_type'], ['BONUS', 'GIFT', 'SAMPLE']) && !empty($detail['purchase_order_item_id'])) {
      throw new Exception('Los productos BONUS/GIFT/SAMPLE no deben tener purchase_order_item_id');
    }

    // observed_quantity must be less than or equal to quantity_received
    $observedQuantity = $detail['observed_quantity'] ?? 0;
    $quantityReceived = $detail['quantity_received'];

    // If observed_quantity > 0, must have reason_observation
    if ($observedQuantity > 0 && empty($detail['reason_observation'])) {
      throw new Exception('Debe indicar la razón de la observación cuando hay productos observados');
    }

    // Validate that we don't receive more than ordered (for ORDERED type)
    if ($detail['reception_type'] === PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED) {
      $productId = $detail['product_id'];

      // Buscar el producto en los detalles de la orden de proveedor
      $supplierOrderDetail = ApSupplierOrderDetails::where('ap_supplier_order_id', $supplierOrder->id)
        ->where('product_id', $productId)
        ->with('product')
        ->first();

      if (!$supplierOrderDetail) {
        $product = Products::find($productId);
        $productName = $product ? $product->name : "ID {$productId}";
        throw new Exception("El producto '{$productName}' no está en la orden de compra");
      }

      // Calcular cuánto ya se ha recibido de este producto en recepciones previas
      $alreadyReceived = PurchaseReceptionDetail::whereHas('reception', function ($query) use ($supplierOrder) {
        $query->where('ap_supplier_order_id', $supplierOrder->id)
          ->whereNull('deleted_at');
      })
        ->where('product_id', $productId)
        ->where('reception_type', PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED)
        ->sum('quantity_received');

      $quantityAccepted = $quantityReceived - $observedQuantity;
      $totalThatWillBeReceived = $alreadyReceived + $quantityAccepted;

      if ($totalThatWillBeReceived > $supplierOrderDetail->quantity) {
        $productName = $supplierOrderDetail->product->name ?? "ID {$productId}";
        throw new Exception("No puede recibir más de lo ordenado para el producto '{$productName}'. Ordenado: {$supplierOrderDetail->quantity}, Ya recibido: {$alreadyReceived}, Intenta recibir: {$quantityAccepted}");
      }
    }
  }

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

  public function getByPurchaseOrder($purchaseOrderId)
  {
    $receptions = PurchaseReception::where('purchase_order_id', $purchaseOrderId)
      ->with(['supplierOrder', 'purchaseOrder', 'warehouse', 'receivedByUser', 'reviewedByUser', 'details.product'])
      ->get();

    return PurchaseReceptionResource::collection($receptions);
  }

  protected function notifyRequestUsers(ApSupplierOrder $supplierOrder): void
  {
    $requestDetailsCount = $supplierOrder->requestDetails()->count();

    if ($requestDetailsCount === 0) {
      return;
    }

    // Obtener usuarios únicos a notificar con sus correos
    $usersToNotify = $supplierOrder->getUsersToNotify();

    if ($usersToNotify->isEmpty()) {
      return;
    }

    $supplierOrder->requestDetails()->update(['status' => 'received']);

    $emailService = new EmailService();

    // Obtener detalles de la última recepción para incluir en el correo
    $latestReception = $supplierOrder->receptions()
      ->with('details.product')
      ->latest()
      ->first();

    $receivedItems = [];
    if ($latestReception) {
      foreach ($latestReception->details as $detail) {
        $product = $detail->product;
        if ($product) {
          $receivedItems[] = [
            'code' => $product->code ?? 'N/A',
            'name' => $product->name,
            'quantity' => $detail->quantity_received - ($detail->observed_quantity ?? 0), // Cantidad aceptada
            'unit' => $product->unit_type ?? 'unid'
          ];
        }
      }
    }

    // Agrupar por email para enviar un solo correo por usuario
    $groupedByEmail = $usersToNotify->groupBy('email');

    foreach ($groupedByEmail as $email => $userRequests) {
      try {
        // Obtener el primer registro para obtener el nombre del usuario
        $firstUser = $userRequests->first();
        $userName = $firstUser['user_name'] ?? 'Usuario';

        // Recopilar todas las solicitudes de este usuario
        $requestNumbers = $userRequests->pluck('request_number')->unique()->toArray();

        // Usar número de orden de proveedor o PurchaseOrder si existe
        $orderNumber = $supplierOrder->apPurchaseOrder
          ? $supplierOrder->apPurchaseOrder->number
          : $supplierOrder->order_number;

        $emailConfig = [
          'to' => $email,
          'subject' => 'Pedido Recibido - Orden de Compra ' . $orderNumber,
          'template' => 'emails.purchase-order-received',
          'data' => [
            'badge' => 'Pedido Recibido',
            'title' => 'Pedido Recibido en Almacén',
            'subtitle' => 'Los productos de tu solicitud ya están disponibles',
            'user_name' => $userName,
            'purchase_order_number' => $orderNumber,
            'request_numbers' => $requestNumbers,
            'received_items' => $receivedItems,
            'date' => now()->format('d/m/Y H:i'),
            'company_name' => 'Grupo Pakatnamu',
            'contact_info' => 'almacen@grupopakatnamu.com'
          ]
        ];

        // Enviar correo usando cola de trabajo
        $emailService->queue($emailConfig);
      } catch (\Exception $e) {
        \Log::error('Error al enviar notificación de recepción de orden de compra a ' . $email . ': ' . $e->getMessage());
      }
    }
  }

  public function processReceptionStock(PurchaseOrder $purchaseOrder): void
  {
    $stockService = new ProductWarehouseStockService();
    $reception = $purchaseOrder->reception; // Relación ya vinculada

    // 1. Validar que la recepción exista
    if (!$reception) {
      throw new Exception("No hay recepción vinculada a esta factura");
    }

    // 2. Procesar cada detalle de la recepción
    foreach ($reception->details as $index => $receptionDetail) {
      $quantityReceived = $receptionDetail->quantity_received;
      $observedQuantity = $receptionDetail->observed_quantity ?? 0;
      $totalProcessed = $quantityReceived + $observedQuantity;

      // 3. Buscar el PurchaseOrderItem correspondiente a este producto
      $orderItem = $purchaseOrder->items()
        ->where('product_id', $receptionDetail->product_id)
        ->first();

      if (!$orderItem) {
        throw new Exception("No se encontró el item de la factura para el producto ID {$receptionDetail->product_id}");
      }

      // 4. Vincular el detalle de recepción con el item de la factura
      $receptionDetail->update(['purchase_order_item_id' => $orderItem->id]);

      // 5. Actualizar cantidades en PurchaseOrderItem (solo para items ORDERED)
      if ($receptionDetail->reception_type === PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED) {
        $orderItem->quantity_received += $quantityReceived;
        $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
        $orderItem->save();

        // 6. Actualizar quantity_pending_credit_note si hay observaciones
        if ($observedQuantity > 0) {
          $stockService->addPendingCreditNote(
            $receptionDetail->product_id,
            $reception->warehouse_id,
            $observedQuantity
          );
        }

        // 7. Remover de in-transit (total procesado: recibido + observado)
        $stockService->removeInTransitStock(
          $receptionDetail->product_id,
          $reception->warehouse_id,
          $totalProcessed
        );
      }
    }

    // 8. Crear movimiento de inventario y actualizar stock físico
    $inventoryMovementService = new InventoryMovementService();
    try {
      $inventoryMovementService->createFromPurchaseReception($reception);
    } catch (Exception $e) {
      throw new Exception('Error al crear el movimiento de inventario y actualizar stock: ' . $e->getMessage());
    }

    // 9. Marcar detalles de solicitud de compra y su cabecera como recepcionados
    $this->markRequestDetailsAsReceived($purchaseOrder);
  }

  protected function markRequestDetailsAsReceived(PurchaseOrder $purchaseOrder): void
  {
    // Obtener la recepción asociada a esta purchase order
    $reception = $purchaseOrder->reception;

    if (!$reception || !$reception->supplierOrder) {
      return;
    }

    // Obtener IDs de los detalles de solicitud vinculados a la orden de proveedor
    $detailIds = DB::table('ap_order_purchase_request_detail_supplier_order')
      ->where('ap_supplier_order_id', $reception->supplierOrder->id)
      ->pluck('ap_order_purchase_request_detail_id');

    if ($detailIds->isEmpty()) {
      return;
    }

    // Marcar los detalles como recepcionados
    ApOrderPurchaseRequestDetails::whereIn('id', $detailIds)
      ->update(['status' => ApOrderPurchaseRequestDetails::STATUS_RECEIVED]);

    // Obtener los IDs únicos de las cabeceras (solicitudes de compra)
    $requestIds = ApOrderPurchaseRequestDetails::whereIn('id', $detailIds)
      ->pluck('order_purchase_request_id')
      ->unique();

    // Para cada cabecera, si todos sus detalles están recepcionados → marcar la cabecera
    foreach ($requestIds as $requestId) {
      $hasPending = ApOrderPurchaseRequestDetails::where('order_purchase_request_id', $requestId)
        ->where('status', '!=', ApOrderPurchaseRequestDetails::STATUS_RECEIVED)
        ->whereNull('deleted_at')
        ->exists();

      if (!$hasPending) {
        ApOrderPurchaseRequests::where('id', $requestId)->update([
          'status' => ApOrderPurchaseRequests::RECEIVED,
          'received_date' => now(),
        ]);
      }
    }
  }

  /**
   * Actualizar el reception_type del ApSupplierOrder
   * COMPLETE: Si no hay productos pendientes por recepcionar
   * PARTIAL: Si todavía hay productos pendientes
   *
   * @param ApSupplierOrder $supplierOrder
   * @return void
   */
  protected function updateSupplierOrderReceptionType(ApSupplierOrder $supplierOrder): void
  {
    $supplierOrderService = new ApSupplierOrderService(new EmailService());
    $pendingProducts = $supplierOrderService->getPendingProducts($supplierOrder->id);

    // Si no hay productos pendientes → COMPLETE
    // Si hay productos pendientes → PARTIAL
    $receptionType = empty($pendingProducts) ? ApSupplierOrder::COMPLETE : ApSupplierOrder::PARTIAL;

    $supplierOrder->update(['reception_type' => $receptionType]);
  }
}
