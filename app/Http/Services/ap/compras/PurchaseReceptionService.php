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
use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
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
use Illuminate\Support\Facades\Log;

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

      if (
        is_null($supplierOrder->approved_by)
        && is_null($supplierOrder->order_number_external)
        && !$this->hasLinkedRequestWithQuotation($supplierOrder)
      ) {
        throw new Exception('El pedido a proveedor debe estar aprobada para generar una recepción');
      }

      if (!$supplierOrder->status) {
        throw new Exception('No se puede generar un registro de recepción a un pedido que ha sido anulado');
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
      $data['reception_number'] = PurchaseReception::generateNextReceptionNumber();

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
      // $status = $allItemsFullyReceived ? 'APPROVED' : 'PARTIAL'; (solo se quedara APPROVED o ANNULLED)

      // 3. Calcular RECEPTION_TYPE (tipo de esta recepción específica):
      // - COMPLETE: Si en esta recepción se está recibiendo todo lo que faltaba
      // - PARTIAL: Si solo se recibe una parte (quedarán más recepciones pendientes)
      $receptionType = $allItemsFullyReceived ? 'COMPLETE' : 'PARTIAL';

      // Update reception totals, status and reception_type
      $reception->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
        'status' => 'APPROVED',
        'reception_type' => $receptionType,
      ]);

      // ACTUALIZAR RECEPTION_TYPE DEL ApSupplierOrder
      $this->updateSupplierOrderReceptionType($supplierOrder);

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

  public function show($id, $includeAllOrders = false)
  {
    $resource = new PurchaseReceptionResource($this->find($id));

    if ($includeAllOrders) {
      $resource->additional(['include_all_orders' => true]);
    }

    return $resource;
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
        throw new Exception('No se puede eliminar una recepción que ya tiene una factura asociada ya sea activa o anulada.');
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

  /**
   * Verifica si el pedido a proveedor tiene al menos una solicitud de compra
   * asociada (a través de request_detail_ids) que ya cuenta con una cotización
   * vinculada (ap_order_quotation_id). De ser así, no se exige la aprobación
   * manual del pedido para poder generar la recepción.
   */
  protected function hasLinkedRequestWithQuotation(ApSupplierOrder $supplierOrder): bool
  {
    return $supplierOrder->requestDetails()
      ->whereHas('orderPurchaseRequest', function ($query) {
        $query->whereNotNull('ap_order_quotation_id');
      })
      ->exists();
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
          ->where('status', '!=', 'ANNULLED')
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
          ->where('status', 1)
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

  public function getByPurchaseOrder($purchaseOrderId)
  {
    $receptions = PurchaseReception::where('purchase_order_id', $purchaseOrderId)
      ->with(['supplierOrder', 'purchaseOrder', 'warehouse', 'receivedByUser', 'reviewedByUser', 'details.product'])
      ->get();

    return PurchaseReceptionResource::collection($receptions);
  }

  public function notifyRequestUsers(ApSupplierOrder $supplierOrder): void
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

    // Obtener el purchase order asociado para datos del PDF
    $purchaseOrder = $supplierOrder->receptions->first()?->purchaseOrder;
    if (!$purchaseOrder) {
      \Log::warning("Supplier Order #{$supplierOrder->id}: No tiene purchase order asociada.");
      return;
    }

    // Cargar relaciones necesarias para el PDF
    $purchaseOrder->load([
      'sede.province',
      'sede.district',
      'sede.company',
      'supplier',
      'creator.person',
      'currency',
      'reception.warehouse',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.requestedBy.person',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.apOrderQuotation.vehicle.model',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.apOrderQuotation.client',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.apOrderQuotation.createdBy.person',
      'reception.details.product.brand',
      'reception.details.purchaseOrderItem',
    ]);

    // Preparar datos para el email y el PDF
    $sedeAbbreviation = $purchaseOrder->sede?->abreviatura ?? 'N/A';
    $subject = 'Informe de llegada de repuestos en Almacén PAKATNAMU ' . $sedeAbbreviation;

    $emailData = [
      'purchase_order_number' => $purchaseOrder->number,
      'sede_name' => $purchaseOrder->sede?->abreviatura ?? 'N/A',
      'sede_abbreviation' => $sedeAbbreviation,
      'supplier_name' => $purchaseOrder->supplier?->full_name ?? 'N/A',
      'responsible_name' => $purchaseOrder->creator?->person?->nombre_completo ?? 'N/A',
    ];

    // Preparar datos para el PDF
    $notificationService = app(\App\Http\Services\ap\compras\InvoiceAccountedNotificationService::class);
    $pdfData = $notificationService->preparePdfData($purchaseOrder);

    // Generar el PDF y guardarlo en archivo temporal
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.ap.postventa.taller.purchase-reception-detail', $pdfData);
    $pdf->setPaper('a4', 'landscape');

    $pdfPath = storage_path('app/temp/purchase_reception_' . $purchaseOrder->number . '_' . now()->format('YmdHis') . '.pdf');

    // Crear directorio si no existe
    $dir = dirname($pdfPath);
    if (!file_exists($dir)) {
      mkdir($dir, 0755, true);
    }

    file_put_contents($pdfPath, $pdf->output());
    $pdfFileName = 'Recepcion_OC_' . $purchaseOrder->number . '_' . now()->format('Ymd') . '.pdf';

    // Agrupar por email para enviar un solo correo por usuario
    $groupedByEmail = $usersToNotify->groupBy('email');

    foreach ($groupedByEmail as $email => $userRequests) {
      try {
        // Obtener el primer registro para obtener el nombre del usuario
        $firstUser = $userRequests->first();
        $userName = $firstUser['user_name'] ?? 'Usuario';

        $emailConfig = [
          'to' => $email,
          'subject' => $subject,
          'template' => 'emails.purchase-order-warehouse-notification',
          'data' => array_merge($emailData, [
            'recipient_name' => $userName,
          ]),
          'attachments' => [
            ['path' => $pdfPath, 'name' => $pdfFileName, 'mime' => 'application/pdf']
          ],
        ];

        // Enviar correo usando cola de trabajo
        $emailService->queue($emailConfig);
      } catch (\Exception $e) {
        \Log::error('Error al enviar notificación de recepción de orden de compra a ' . $email . ': ' . $e->getMessage());
      }
    }

    // NO eliminamos el archivo aquí porque el email está en cola
    // El archivo se limpiará automáticamente del directorio temp más tarde
  }

  public function processReceptionStock(PurchaseOrder $purchaseOrder): void
  {
    $stockService = app(ProductWarehouseStockService::class);
    $reception = $purchaseOrder->reception; // Relación ya vinculada

    // 1. Validar que la recepción exista
    if (!$reception) {
      throw new Exception("No hay recepción vinculada a esta factura");
    }

    // 2. LOCK PESSIMISTIC: Bloquear la recepción para evitar procesamiento simultáneo
    // Esto previene race conditions cuando múltiples usuarios o procesos ejecutan el job al mismo tiempo
    DB::transaction(function () use ($reception, $purchaseOrder, $stockService) {
      // Bloquear el registro de recepción (lockForUpdate espera si otro proceso lo está usando)
      $lockedReception = PurchaseReception::where('id', $reception->id)
        ->lockForUpdate()
        ->first();

      if (!$lockedReception) {
        throw new Exception("No se pudo obtener el lock de la recepción");
      }

      // 3. Verificar si ya existe un movimiento de inventario para esta recepción (dentro del lock)
      $existingMovement = InventoryMovement::where('reference_type', PurchaseReception::class)
        ->where('reference_id', $reception->id)
        ->exists();

      if ($existingMovement) {
        // Ya existe un movimiento de inventario para esta recepción, no crear duplicado
        Log::info("Procesamiento de stock omitido: ya existe movimiento de inventario para recepción #{$reception->id}");
        return;
      }

      // 4. Procesar dentro del lock para garantizar atomicidad
      $this->processReceptionStockInternal($lockedReception, $purchaseOrder, $stockService);
    });
  }

  /**
   * Lógica interna de procesamiento de stock (ejecutada dentro del lock)
   */
  protected function processReceptionStockInternal(PurchaseReception $reception, PurchaseOrder $purchaseOrder, $stockService): void
  {

    // 3. Procesar cada detalle de la recepción
    foreach ($reception->details as $index => $receptionDetail) {
      $quantityReceived = $receptionDetail->quantity_received;
      $observedQuantity = $receptionDetail->observed_quantity ?? 0;
      $totalProcessed = $quantityReceived + $observedQuantity;

      // 4. Buscar el PurchaseOrderItem correspondiente a este producto
      $orderItem = $purchaseOrder->items()
        ->where('product_id', $receptionDetail->product_id)
        ->first();

      if (!$orderItem) {
        throw new Exception("No se encontró el item de la factura para el producto ID {$receptionDetail->product_id}");
      }

      // 5. Vincular el detalle de recepción con el item de la factura
      $receptionDetail->update(['purchase_order_item_id' => $orderItem->id]);

      // 6. Actualizar cantidades en PurchaseOrderItem (solo para items ORDERED)
      if ($receptionDetail->reception_type === PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED) {
        // Cuando is_credit_note = true, TODAS las unidades se reciben físicamente
        // (aunque algunas estén defectuosas y vayan a NC después)
        // Esto mantiene consistencia con removeInTransitStock y el stock físico
        $quantityToRecord = $receptionDetail->is_credit_note
          ? $quantityReceived + $observedQuantity
          : $quantityReceived;

        $orderItem->quantity_received += $quantityToRecord;
        $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
        $orderItem->save();

        // 7. Actualizar quantity_pending_credit_note si hay observaciones Y is_credit_note = true
        // Solo se usa como campo de monitoreo cuando se espera una NC futura
        if ($observedQuantity > 0 && $receptionDetail->is_credit_note) {
          $stockService->addPendingCreditNote(
            $receptionDetail->product_id,
            $reception->warehouse_id,
            $observedQuantity
          );
        }

        // 8. Remover de in-transit (total procesado: recibido + observado)
        $stockService->removeInTransitStock(
          $receptionDetail->product_id,
          $reception->warehouse_id,
          $totalProcessed
        );
      }
    }

    // 9. Crear movimiento de inventario y actualizar stock físico
    $inventoryMovementService = app(InventoryMovementService::class);
    try {
      $inventoryMovementService->createFromPurchaseReception($reception);
    } catch (Exception $e) {
      throw new Exception('Error al crear el movimiento de inventario y actualizar stock: ' . $e->getMessage());
    }

    // 10. Marcar detalles de solicitud de compra y su cabecera como recepcionados
    $this->markRequestDetailsAsReceived($purchaseOrder);

    // 11. Notificar usuarios si es POSTVENTA y tiene solicitudes vinculadas
//    if ($purchaseOrder->type_operation_id === ApMasters::TIPO_OPERACION_POSTVENTA) {
//      $reception = $purchaseOrder->reception;
//      if ($reception->supplierOrder) {
//        $this->notifyRequestUsers($reception->supplierOrder);
//      }
//    }
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
   * Delega la lógica al servicio centralizado ApSupplierOrderService
   *
   * @param ApSupplierOrder $supplierOrder
   * @return void
   */
  protected function updateSupplierOrderReceptionType(ApSupplierOrder $supplierOrder): void
  {
    $supplierOrderService = new ApSupplierOrderService();
    $supplierOrderService->updateReceptionType($supplierOrder);
  }

  /**
   * Marca productos como defectuosos después de la facturación
   * Permite actualizar una recepción ya facturada cuando se detectan productos defectuosos
   * que generarán nota de crédito
   *
   * @param array $data Datos con los items a marcar como defectuosos
   * @return array Resultado de la operación
   * @throws Exception
   */
  public function markDefectiveProducts(array $data): array
  {
    DB::beginTransaction();
    try {
      $updatedItems = [];
      $stockService = app(ProductWarehouseStockService::class);

      foreach ($data['items'] as $item) {
        $receptionDetail = PurchaseReceptionDetail::with([
          'reception.purchaseOrder.items',
          'product'
        ])->findOrFail($item['reception_detail_id']);

        $reception = $receptionDetail->reception;
        $purchaseOrder = $reception->purchaseOrder;

        // VALIDACIÓN 1: La recepción debe estar facturada (contabilizada)
        if (!$purchaseOrder || !$purchaseOrder->invoice_dynamics) {
          throw new Exception(
            "El producto '{$receptionDetail->product->name}' no puede ser marcado como defectuoso " .
            "porque la recepción aún no está facturada/contabilizada."
          );
        }

        // CAPTURAR VALOR ANTERIOR (el que está en BD ahora) ANTES de cualquier modificación
        // Este es el valor que usaremos para calcular el DELTA
        $previousObservedQuantity = $receptionDetail->observed_quantity;

        // VALIDACIÓN 2: La cantidad defectuosa total no puede ser mayor a la cantidad total disponible
        $defectiveQuantity = (float)$item['defective_quantity'];
        $totalAvailable = $receptionDetail->quantity_received + $receptionDetail->observed_quantity;

        if ($defectiveQuantity > $totalAvailable) {
          throw new Exception(
            "La cantidad defectuosa total ({$defectiveQuantity}) no puede ser mayor a la cantidad total disponible " .
            "({$totalAvailable}) para el producto '{$receptionDetail->product->name}'."
          );
        }

        // Calcular nueva distribución
        // newObservedQuantity será igual a defectiveQuantity (lo que el usuario quiere marcar como defectuoso)
        // newQuantityReceived será el resto
        $newQuantityReceived = $totalAvailable - $defectiveQuantity;
        $newObservedQuantity = $defectiveQuantity;

        // VALIDACIÓN 3: La suma total debe mantenerse igual (no cambiar stock físico ya contabilizado)
        $originalTotal = $receptionDetail->quantity_received + $receptionDetail->observed_quantity;
        $newTotal = $newQuantityReceived + $newObservedQuantity;

        if ($originalTotal != $newTotal) {
          throw new Exception(
            "Error de cálculo: La suma total ha cambiado. Original: {$originalTotal}, Nuevo: {$newTotal}. " .
            "Esto no debería ocurrir."
          );
        }

        // Guardar valores originales para auditoría
        $originalData = [
          'quantity_received' => $receptionDetail->quantity_received,
          'observed_quantity' => $receptionDetail->observed_quantity,
          'is_credit_note' => $receptionDetail->is_credit_note,
        ];

        // ACTUALIZACIÓN 1: Actualizar purchase_reception_details
        $receptionDetail->update([
          'quantity_received' => $newQuantityReceived,
          'observed_quantity' => $newObservedQuantity,
          'is_credit_note' => true,
          'reason_observation' => $item['reason_observation'],
        ]);

        // ACTUALIZACIÓN 2: Actualizar product_warehouse_stock.quantity_pending_credit_note
        // Calcular DELTA (diferencia entre nuevo valor y anterior)
        $deltaObserved = $newObservedQuantity - $previousObservedQuantity;

        // Aplicar solo el DELTA al stock
        if ($deltaObserved > 0) {
          // Si aumentó la cantidad observada, sumar la diferencia
          $stockService->addPendingCreditNote(
            $receptionDetail->product_id,
            $reception->warehouse_id,
            $deltaObserved
          );
        } elseif ($deltaObserved < 0) {
          // Si disminuyó la cantidad observada, restar la diferencia (valor absoluto)
          $stockService->removePendingCreditNote(
            $receptionDetail->product_id,
            $reception->warehouse_id,
            abs($deltaObserved)
          );
        }
        // Si deltaObserved == 0, no hacer nada (no cambió)

        // ACTUALIZACIÓN 3: Actualizar purchase_order_items para mantener consistencia
        // Buscar el purchase_order_item correspondiente
        $orderItem = $purchaseOrder->items()
          ->where('product_id', $receptionDetail->product_id)
          ->first();

        if ($orderItem) {
          // Como ahora is_credit_note = true, quantity_received debe incluir las defectuosas
          // Pero como ya estaba contabilizado, solo necesitamos ajustar si es necesario
          // En realidad, con la corrección del bug, esto ya debería estar bien
          // Pero para casos antiguos, podemos recalcular:

          // Recalcular quantity_received basado en TODAS las recepciones de este producto
          $totalReceived = PurchaseReceptionDetail::whereHas('reception', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id)
              ->where('status', 1)
              ->whereNull('deleted_at');
          })
            ->where('product_id', $receptionDetail->product_id)
            ->where('reception_type', PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED)
            ->get()
            ->sum(function ($detail) {
              // Sumar correctamente según is_credit_note
              return $detail->is_credit_note
                ? $detail->quantity_received + $detail->observed_quantity
                : $detail->quantity_received;
            });

          $orderItem->quantity_received = $totalReceived;
          $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
          $orderItem->save();
        }

        // Registrar el cambio en el array de respuesta
        $updatedItems[] = [
          'product_id' => $receptionDetail->product_id,
          'product_name' => $receptionDetail->product->name,
          'original' => $originalData,
          'updated' => [
            'quantity_received' => $newQuantityReceived,
            'observed_quantity' => $newObservedQuantity,
            'is_credit_note' => true,
          ],
          'defective_quantity' => $defectiveQuantity,
        ];

        // Crear log de auditoría
        \Log::info('Producto marcado como defectuoso post-facturación', [
          'reception_id' => $reception->id,
          'reception_number' => $reception->reception_number,
          'purchase_order_id' => $purchaseOrder->id,
          'purchase_order_number' => $purchaseOrder->number,
          'product_id' => $receptionDetail->product_id,
          'product_name' => $receptionDetail->product->name,
          'defective_quantity' => $defectiveQuantity,
          'original_data' => $originalData,
          'user_id' => Auth::id(),
          'reason' => $item['reason_observation'],
        ]);
      }

      DB::commit();

      return [
        'message' => 'Productos marcados como defectuosos correctamente',
        'items' => $updatedItems,
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Revierte el marcado de defectuoso de un producto
   * Pasa toda la cantidad (received + observed) a quantity_received
   * y pone observed_quantity = 0, is_credit_note = false
   *
   * @param int $receptionDetailId ID del detalle de recepción
   * @return array Resultado de la operación
   * @throws Exception
   */
  public function unmarkDefectiveProduct(int $receptionDetailId): array
  {
    DB::beginTransaction();
    try {
      $stockService = app(ProductWarehouseStockService::class);

      // Obtener el detalle de recepción con sus relaciones
      $receptionDetail = PurchaseReceptionDetail::with([
        'reception.purchaseOrder.items',
        'product'
      ])->findOrFail($receptionDetailId);

      $reception = $receptionDetail->reception;
      $purchaseOrder = $reception->purchaseOrder;

      // VALIDACIÓN 1: El producto debe estar marcado como is_credit_note = true
      if (!$receptionDetail->is_credit_note) {
        throw new Exception(
          "El producto '{$receptionDetail->product->name}' no está marcado como defectuoso. " .
          "No hay nada que revertir."
        );
      }

      // VALIDACIÓN 2: La recepción debe estar facturada
      if (!$purchaseOrder || !$purchaseOrder->invoice_dynamics) {
        throw new Exception(
          "El producto no puede ser revertido porque la recepción no está facturada."
        );
      }

      // VALIDACIÓN 3: No debe existir una NC ya procesada en Dynamics
      if (!empty($purchaseOrder->credit_note_dynamics)) {
        throw new Exception(
          "No se puede revertir porque ya existe una nota de crédito procesada en Dynamics " .
          "({$purchaseOrder->credit_note_dynamics}). Esta operación no se puede deshacer."
        );
      }

      // Capturar valores originales para auditoría
      $originalData = [
        'quantity_received' => $receptionDetail->quantity_received,
        'observed_quantity' => $receptionDetail->observed_quantity,
        'is_credit_note' => $receptionDetail->is_credit_note,
      ];

      // Capturar el valor de observed_quantity ANTES de modificar
      $previousObservedQuantity = $receptionDetail->observed_quantity;

      // Calcular nueva distribución: TODO a quantity_received
      $newQuantityReceived = $receptionDetail->quantity_received + $receptionDetail->observed_quantity;
      $newObservedQuantity = 0;

      // ACTUALIZACIÓN 1: Actualizar purchase_reception_details
      $receptionDetail->update([
        'quantity_received' => $newQuantityReceived,
        'observed_quantity' => $newObservedQuantity,
        'is_credit_note' => false,
        'reason_observation' => null, // Limpiar la razón
      ]);

      // ACTUALIZACIÓN 2: Restar de product_warehouse_stock.quantity_pending_credit_note
      // Como pasamos de observed_quantity > 0 a 0, debemos restar la cantidad anterior
      if ($previousObservedQuantity > 0) {
        $stockService->removePendingCreditNote(
          $receptionDetail->product_id,
          $reception->warehouse_id,
          $previousObservedQuantity
        );
      }

      // ACTUALIZACIÓN 3: Recalcular purchase_order_items
      $orderItem = $purchaseOrder->items()
        ->where('product_id', $receptionDetail->product_id)
        ->first();

      if ($orderItem) {
        // Recalcular quantity_received basado en TODAS las recepciones de este producto
        $totalReceived = PurchaseReceptionDetail::whereHas('reception', function ($query) use ($purchaseOrder) {
          $query->where('purchase_order_id', $purchaseOrder->id)
            ->where('status', 1)
            ->whereNull('deleted_at');
        })
          ->where('product_id', $receptionDetail->product_id)
          ->where('reception_type', PurchaseReceptionDetail::RECEPTION_TYPE_ORDERED)
          ->get()
          ->sum(function ($detail) {
            // Sumar correctamente según is_credit_note
            return $detail->is_credit_note
              ? $detail->quantity_received + $detail->observed_quantity
              : $detail->quantity_received;
          });

        $orderItem->quantity_received = $totalReceived;
        $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
        $orderItem->save();
      }

      // Crear log de auditoría
      \Log::info('Marcado de defectuoso revertido', [
        'reception_id' => $reception->id,
        'reception_number' => $reception->reception_number,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_number' => $purchaseOrder->number,
        'product_id' => $receptionDetail->product_id,
        'product_name' => $receptionDetail->product->name,
        'original_data' => $originalData,
        'new_data' => [
          'quantity_received' => $newQuantityReceived,
          'observed_quantity' => $newObservedQuantity,
          'is_credit_note' => false,
        ],
        'user_id' => Auth::id(),
      ]);

      DB::commit();

      return [
        'message' => 'Marcado de defectuoso revertido correctamente',
        'data' => [
          'product_id' => $receptionDetail->product_id,
          'product_name' => $receptionDetail->product->name,
          'original' => $originalData,
          'updated' => [
            'quantity_received' => $newQuantityReceived,
            'observed_quantity' => $newObservedQuantity,
            'is_credit_note' => false,
          ],
        ],
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
