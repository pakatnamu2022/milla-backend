<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApWorkOrderPartsResource;
use App\Http\Resources\ap\postventa\taller\ApWorkOrderPartDeliveryResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\ApWorkOrderPartDelivery;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApWorkOrderPartsService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApWorkOrderParts::class,
      $request,
      ApWorkOrderParts::filters,
      ApWorkOrderParts::sorts,
      ApWorkOrderPartsResource::class
    );
  }

  public function find($id)
  {
    $workOrderPart = ApWorkOrderParts::with([
      'workOrder',
      'product',
      'warehouse'
    ])->where('id', $id)->first();

    if (!$workOrderPart) {
      throw new Exception('Repuesto de orden de trabajo no encontrado');
    }

    return $workOrderPart;
  }

  private function calculatePricesAndTotals(array &$data): void
  {
    $productId = $data['product_id'];
    $quantity = $data['quantity_used'];
    $discountPercentage = $data['discount_percentage'] ?? 0;
    $workOrderId = $data['work_order_id'];

    // Obtener el producto
    $product = Products::find($productId);
    if (!$product) {
      throw new Exception('Producto no encontrado');
    }

    // Calcular factor de tipo de cambio basándose en la OT y cotización
    $factor = $this->calculateExchangeRateFactor($workOrderId);

    // Si no se proporciona unit_price, usar el precio de venta del producto
    if (!isset($data['unit_price']) || $data['unit_price'] === null) {
      $data['unit_price'] = $product->sale_price ?? 0;
    }

    // Aplicar factor de tipo de cambio al precio unitario
    $data['unit_price'] = floatval($data['unit_price']) * $factor;

    // Calcular total_cost (monto sin descuento)
    $data['total_cost'] = $data['unit_price'] * $quantity;

    // Calcular net_amount (monto con descuento aplicado)
    if ($discountPercentage > 0) {
      $discountAmount = $data['total_cost'] * ($discountPercentage / 100);
      $data['net_amount'] = $data['total_cost'] - $discountAmount;
    } else {
      $data['net_amount'] = $data['total_cost'];
    }

    // tax_amount en 0 por defecto
    $data['tax_amount'] = 0;
  }

  private function calculateExchangeRateFactor(int $workOrderId): float
  {
    $workOrder = ApWorkOrder::find($workOrderId);

    if (!$workOrder) {
      throw new Exception('Orden de trabajo no encontrada');
    }

    if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
      throw new Exception('No se puede agregar repuestos a una orden de trabajo cerrada');
    }

    // Si la OT tiene cotización asociada, calcular el factor
    if ($workOrder->order_quotation_id) {
      $orderQuotation = $workOrder->orderQuotation;

      if ($orderQuotation->currency_id === $workOrder->currency_id) {
        // Misma moneda, no hay conversión
        return 1;
      } else {
        if ($workOrder->currency_id === TypeCurrency::PEN_ID) {
          // Si la OT está en soles, la cotización está en dólares
          // Multiplicar por el tipo de cambio para convertir a soles
          return $orderQuotation->exchange_rate;
        } else if ($workOrder->currency_id === TypeCurrency::USD_ID) {
          // Si la OT está en dólares, la cotización está en soles
          // No hay conversión necesaria
          return 1;
        } else {
          throw new Exception('Moneda no soportada para la cotización de la orden de trabajo');
        }
      }
    }

    // Sin cotización, factor = 1
    return 1;
  }

  private function translateDiscountStatus(string $status): string
  {
    $translations = [
      DiscountRequestsWorkOrder::STATUS_PENDING => 'pendiente',
      DiscountRequestsWorkOrder::STATUS_APPROVED => 'aprobado',
      DiscountRequestsWorkOrder::STATUS_REJECTED => 'rechazado',
    ];

    return $translations[$status] ?? $status;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::find($data['work_order_id']);
      $validateReceipt = $workOrder->items->first()?->typePlanning->validate_receipt;

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede agregar repuestos a una orden de trabajo cerrada');
      }

      if ($workOrder->vehicleInspection === null && $validateReceipt) {
        throw new Exception('No se puede agregar repuestos a una orden de trabajo sin recepción vehicular');
      }

      // Validar que no existan avances de factura
      if ($workOrder->advancesWorkOrder()->exists()) {
        throw new Exception('No se puede agregar repuestos porque la orden de trabajo ya tiene avances de factura');
      }

      // Validar que no exista el mismo producto en la orden de trabajo
      $existingPart = ApWorkOrderParts::where('work_order_id', $data['work_order_id'])
        ->where('product_id', $data['product_id'])
        ->first();

      if ($existingPart) {
        throw new Exception('Este producto ya ha sido agregado a la orden de trabajo');
      }

      //validamos el precio de venta al público no este por debajo de lo establecido
      $sale_price = ProductWarehouseStock::where('product_id', $data['product_id'])
        ->where('warehouse_id', $data['warehouse_id'])
        ->value('sale_price');

      if ($sale_price && $data['unit_price'] < $sale_price) {
        throw new Exception("El precio unitario no puede ser menor al precio de venta registrado ({$sale_price}) para este producto en el almacén seleccionado");
      }

      // Set registered_by
      if (auth()->check()) {
        $data['registered_by'] = auth()->user()->id;
      }

      // Calcular precios y totales automáticamente
      $this->calculatePricesAndTotals($data);

      // Validar que exista stock disponible para reservar
      $stock = ProductWarehouseStock::where('product_id', $data['product_id'])
        ->where('warehouse_id', $data['warehouse_id'])
        ->first();

      if (!$stock) {
        throw new Exception('No se encontró registro de stock para el producto en el almacén seleccionado');
      }

      if ($stock->available_quantity < $data['quantity_used']) {
        $product = Products::find($data['product_id']);
        $productInfo = $product ? "{$product->code} - {$product->name}" : "ID {$data['product_id']}";
        throw new Exception(
          "Stock insuficiente para el producto {$productInfo}. Disponible: {$stock->available_quantity}, Requerido: {$data['quantity_used']}"
        );
      }

      // Create work order part
      $workOrderPart = ApWorkOrderParts::create($data);

      // Reservar el stock
      $reserveSuccess = $stock->reserveStock($data['quantity_used']);
      if (!$reserveSuccess) {
        throw new Exception('No se pudo reservar el stock. Stock insuficiente.');
      }

      $workOrder->calculateTotals();

      return new ApWorkOrderPartsResource($workOrderPart->load([
        'workOrder',
        'product',
        'warehouse'
      ]));
    });
  }

  public function show($id)
  {
    return new ApWorkOrderPartsResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrderPart = $this->find($data['id']);

      // Validar que no existan avances de factura
      if ($workOrderPart->workOrder->advancesWorkOrder()->exists()) {
        throw new Exception('No se puede actualizar el repuesto porque la orden de trabajo ya tiene avances de factura');
      }

      $oldProductId = $workOrderPart->product_id;
      $oldWarehouseId = $workOrderPart->warehouse_id;
      $oldQuantity = $workOrderPart->quantity_used;

      // Si se cambió el producto, validar que no esté duplicado
      if (isset($data['product_id']) && $data['product_id'] != $oldProductId) {
        $existingPart = ApWorkOrderParts::where('work_order_id', $workOrderPart->work_order_id)
          ->where('product_id', $data['product_id'])
          ->where('id', '!=', $workOrderPart->id)
          ->first();

        if ($existingPart) {
          throw new Exception('Este producto ya ha sido agregado a la orden de trabajo');
        }
      }

      //validamos el precio de venta al público no este por debajo de lo establecido
      $sale_price = ProductWarehouseStock::where('product_id', $data['product_id'] ?? $oldProductId)
        ->where('warehouse_id', $data['warehouse_id'] ?? $oldWarehouseId)
        ->value('sale_price');

      if ($sale_price && $data['unit_price'] < $sale_price) {
        throw new Exception("El precio unitario no puede ser menor al precio de venta registrado ({$sale_price}) para este producto en el almacén seleccionado");
      }

      // Determinar los valores finales
      $newProductId = $data['product_id'] ?? $oldProductId;
      $newWarehouseId = $data['warehouse_id'] ?? $oldWarehouseId;
      $newQuantity = $data['quantity_used'] ?? $oldQuantity;

      // Si cambió el producto, almacén o cantidad, ajustar el stock
      if ($newProductId != $oldProductId || $newWarehouseId != $oldWarehouseId || $newQuantity != $oldQuantity) {
        // Liberar el stock antiguo
        $oldStock = ProductWarehouseStock::where('product_id', $oldProductId)
          ->where('warehouse_id', $oldWarehouseId)
          ->first();

        if ($oldStock) {
          $oldStock->releaseReservedStock($oldQuantity);
        }

        // Reservar el nuevo stock
        $newStock = ProductWarehouseStock::where('product_id', $newProductId)
          ->where('warehouse_id', $newWarehouseId)
          ->first();

        if (!$newStock) {
          throw new Exception('No se encontró registro de stock para el nuevo producto/almacén');
        }

        if ($newStock->available_quantity < $newQuantity) {
          $product = Products::find($newProductId);
          $productInfo = $product ? "{$product->code} - {$product->name}" : "ID {$newProductId}";
          throw new Exception(
            "Stock insuficiente para el producto {$productInfo}. Disponible: {$newStock->available_quantity}, Requerido: {$newQuantity}"
          );
        }

        $reserveSuccess = $newStock->reserveStock($newQuantity);
        if (!$reserveSuccess) {
          // Revertir la liberación del stock antiguo
          if ($oldStock) {
            $oldStock->reserveStock($oldQuantity);
          }
          throw new Exception('No se pudo reservar el nuevo stock');
        }
      }

      // Recalcular totales si cambió algún campo relevante
      if (isset($data['quantity_used']) || isset($data['unit_price']) || isset($data['discount_percentage']) || isset($data['product_id'])) {
        // Preparar datos para recalcular
        $recalcData = [
          'work_order_id' => $workOrderPart->work_order_id,
          'product_id' => $newProductId,
          'quantity_used' => $newQuantity,
          'unit_price' => $data['unit_price'] ?? $workOrderPart->unit_price,
          'discount_percentage' => $data['discount_percentage'] ?? $workOrderPart->discount_percentage,
        ];

        $this->calculatePricesAndTotals($recalcData);

        // Agregar los campos calculados a $data
        $data['total_cost'] = $recalcData['total_cost'];
        $data['net_amount'] = $recalcData['net_amount'];
        $data['tax_amount'] = $recalcData['tax_amount'];
        $data['unit_price'] = $recalcData['unit_price'];
      }

      // Update work order part
      $workOrderPart->update($data);
      $workOrderPart->workOrder->calculateTotals();

      // Reload relations
      $workOrderPart->load([
        'workOrder',
        'product',
        'warehouse'
      ]);

      return new ApWorkOrderPartsResource($workOrderPart);
    });
  }

  public function destroy($id)
  {
    return DB::transaction(function () use ($id) {
      $workOrderPart = $this->find($id);

      // Validar que no existan avances de factura
      if ($workOrderPart->workOrder->advancesWorkOrder()->exists()) {
        throw new Exception('No se puede eliminar el repuesto porque la orden de trabajo ya tiene avances de factura');
      }

      //validar que si ya se asignó y técnico confirmo la recepción no permita eliminar
      if ($workOrderPart->deliveries()->where('is_received', true)->exists()) {
        throw new Exception('No se puede eliminar el repuesto porque ya ha sido asignado a un técnico y confirmado su recepción');
      }

      // Validar si existe una solicitud de descuento activa
      $discountRequest = DiscountRequestsWorkOrder::where('part_labour_id', $id)
        ->where('part_labour_model', ApWorkOrderParts::class)
        ->whereIn('status', [
          DiscountRequestsWorkOrder::STATUS_PENDING,
          DiscountRequestsWorkOrder::STATUS_APPROVED,
          DiscountRequestsWorkOrder::STATUS_REJECTED
        ])
        ->first();

      if ($discountRequest) {
        throw new Exception('No se puede eliminar el repuesto porque tiene una solicitud de descuento en estado ' . $this->translateDiscountStatus($discountRequest->status));
      }

      // Liberar el stock reservado
      $stock = ProductWarehouseStock::where('product_id', $workOrderPart->product_id)
        ->where('warehouse_id', $workOrderPart->warehouse_id)
        ->first();

      if ($stock) {
        $stock->releaseReservedStock($workOrderPart->quantity_used);
      }

      // Buscamos la OT
      $workOrder = $workOrderPart->workOrder;
      if ($workOrder->order_quotation_id) {
        // Si la OT está asociada a una cotización, revertimos el estado del detalle correspondiente
        $quotationDetail = ApOrderQuotationDetails::where('order_quotation_id', $workOrder->order_quotation_id)
          ->where('product_id', $workOrderPart->product_id)
          ->where('quantity', $workOrderPart->quantity_used)
          ->first();

        if ($quotationDetail) {
          $quotationDetail->status = 'pending';
          $quotationDetail->save();
        }
      }

      // Eliminar el repuesto
      $workOrderPart->delete();
      $workOrder->calculateTotals();

      return response()->json(['message' => 'Repuesto eliminado correctamente y stock devuelto al almacén']);
    });
  }

  public function storeBulkFromQuotation(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::find($data['work_order_id']);
      $validateReceipt = $workOrder->items->first()?->typePlanning->validate_receipt;

      if ($workOrder->vehicleInspection === null && $validateReceipt) {
        throw new Exception('No se puede agregar repuestos a una orden de trabajo sin recepción vehicular');
      }

      $quotationId = $data['quotation_id'];
      $workOrderId = $data['work_order_id'];
      $warehouseId = $data['warehouse_id'];
      $quotationDetailIds = $data['quotation_detail_ids'];

      // Validar que no existan avances de factura
      $workOrder = ApWorkOrder::find($workOrderId);
      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      if ($workOrder->advancesWorkOrder()->exists() && $workOrder->is_invoiced) {
        throw new Exception('No se puede agregar repuestos porque la orden de trabajo ya tiene avances de factura');
      }

      // Obtener solo los detalles seleccionados
      $quotationDetails = ApOrderQuotationDetails::with('product')
        ->where('order_quotation_id', $quotationId)
        ->whereIn('id', $quotationDetailIds)
        ->get();

      if ($quotationDetails->isEmpty()) {
        throw new Exception('No se encontraron productos seleccionados');
      }

      // Validar que no existan productos duplicados en la orden de trabajo
      $existingProductIds = ApWorkOrderParts::where('work_order_id', $workOrderId)
        ->pluck('product_id')
        ->toArray();

      $createdParts = [];

      foreach ($quotationDetails as $detail) {
        if (!$detail->product_id) {
          continue; // Skip si no tiene product_id
        }

        // Validar que no exista el mismo producto en la orden de trabajo
        if (in_array($detail->product_id, $existingProductIds)) {
          $productInfo = $detail->product ? "{$detail->product->code} - {$detail->product->name}" : "ID {$detail->product_id}";
          throw new Exception("El producto {$productInfo} ya ha sido agregado a la orden de trabajo");
        }

        // Preparar datos para crear el repuesto
        $partData = [
          'work_order_id' => $workOrderId,
          'product_id' => $detail->product_id,
          'warehouse_id' => $warehouseId,
          'quantity_used' => $detail->quantity,
          'unit_price' => $detail->unit_price,
          'discount_percentage' => $detail->discount_percentage ?? 0,
          'group_number' => $data['group_number'],
        ];

        // Calcular precios y totales automáticamente
        $this->calculatePricesAndTotals($partData);

        // Set registered_by
        if (auth()->check()) {
          $partData['registered_by'] = auth()->user()->id;
        }

        // Validar que exista stock disponible para reservar
        $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
          ->where('warehouse_id', $warehouseId)
          ->first();

        if (!$stock) {
          $productInfo = $detail->product ? "{$detail->product->code} - {$detail->product->name}" : "ID {$detail->product_id}";
          throw new Exception("No se encontró registro de stock para el producto {$productInfo} en el almacén seleccionado");
        }

        if ($stock->available_quantity < $detail->quantity) {
          $productInfo = $detail->product ? "{$detail->product->code} - {$detail->product->name}" : "ID {$detail->product_id}";
          throw new Exception(
            "Stock insuficiente para el producto {$productInfo}. Disponible: {$stock->available_quantity}, Requerido: {$detail->quantity}"
          );
        }

        // Crear el repuesto
        $workOrderPart = ApWorkOrderParts::create($partData);

        // Reservar el stock
        $reserveSuccess = $stock->reserveStock($detail->quantity);
        if (!$reserveSuccess) {
          $productInfo = $detail->product ? "{$detail->product->code} - {$detail->product->name}" : "ID {$detail->product_id}";
          throw new Exception("No se pudo reservar el stock para el producto {$productInfo}");
        }

        // Cargar las relaciones necesarias
        $workOrderPart->load(['workOrder', 'product', 'warehouse']);

        $createdParts[] = $workOrderPart;
        $existingProductIds[] = $detail->product_id; // Agregar a la lista para evitar duplicados en el mismo lote

        // Marcar este detalle específico como tomado
        $detail->status = 'taken';
        $detail->save();
      }

      ApWorkOrder::find($workOrderId)->calculateTotals();

      return [
        'message' => 'Repuestos agregados correctamente desde la cotización',
        'total_parts' => count($createdParts),
        'parts' => ApWorkOrderPartsResource::collection($createdParts)
      ];
    });
  }

  public function assignToTechnician(int $workOrderPartId, array $data)
  {
    return DB::transaction(function () use ($workOrderPartId, $data) {
      $workOrderPart = $this->find($workOrderPartId);

      $deliveredQuantity = $data['delivered_quantity'];
      $deliveredTo = Worker::find($data['delivered_to'])->user->id;

      // Calcular cantidad ya asignada
      $totalAssigned = $workOrderPart->assigned_quantity ?? 0;

      // Validar que no exceda la cantidad total
      $newTotalAssigned = $totalAssigned + $deliveredQuantity;
      if ($newTotalAssigned > $workOrderPart->quantity_used) {
        throw new Exception(
          "La cantidad a asignar excede la cantidad disponible. Disponible: " . ($workOrderPart->quantity_used - $totalAssigned) .
          ", Solicitado: {$deliveredQuantity}"
        );
      }

      // Crear registro de entrega
      $delivery = ApWorkOrderPartDelivery::create([
        'work_order_part_id' => $workOrderPartId,
        'delivered_to' => $deliveredTo,
        'delivered_quantity' => $deliveredQuantity,
        'delivered_date' => now(),
        'delivered_by' => auth()->check() ? auth()->user()->id : null,
        'is_received' => false,
      ]);

      // Actualizar cantidad asignada en el repuesto
      $workOrderPart->assigned_quantity = $newTotalAssigned;
      $workOrderPart->save();

      return [
        'message' => 'Repuesto asignado correctamente al técnico',
        'delivery' => $delivery->load(['deliveredToUser', 'deliveredByUser']),
        'work_order_part' => new ApWorkOrderPartsResource($workOrderPart->load(['workOrder', 'product', 'warehouse', 'deliveries']))
      ];
    });
  }

  public function confirmReceipt(array $data)
  {
    return DB::transaction(function () use ($data) {
      $deliveryIds = $data['delivery_ids'];

      $deliveries = ApWorkOrderPartDelivery::whereIn('id', $deliveryIds)->get();
      if ($deliveries->count() !== count($deliveryIds)) {
        throw new Exception('Uno o más registros de entrega no existen');
      }

      $workOrderPartIds = $deliveries->pluck('work_order_part_id')->unique()->values();
      $workOrderParts = ApWorkOrderParts::with(['workOrder', 'product', 'warehouse', 'deliveries'])
        ->whereIn('id', $workOrderPartIds)
        ->get();

      // Obtener usuario logueado
      $currentUser = auth()->user();
      if (!$currentUser) {
        throw new Exception('Usuario no autenticado');
      }

      // Obtener firma del worker del usuario logueado
      $workerSignatureUrl = null;
      if ($currentUser->person && $currentUser->person->signature) {
        $workerSignatureUrl = $currentUser->person->signature->signature_url;
      }

      $confirmedDeliveries = [];
      $errors = [];

      foreach ($deliveryIds as $deliveryId) {
        // Buscar el registro de entrega
        $delivery = $deliveries->firstWhere('id', $deliveryId);

        if (!$delivery) {
          $errors[] = "Registro de entrega ID {$deliveryId} no encontrado";
          continue;
        }

        if ($delivery->is_received) {
          $errors[] = "La entrega ID {$deliveryId} ya ha sido confirmada";
          continue;
        }

        // Actualizar datos de recepción
        $delivery->is_received = true;
        $delivery->received_date = now();
        $delivery->received_signature_url = $workerSignatureUrl;
        $delivery->received_by = $currentUser->id;
        $delivery->save();

        $confirmedDeliveries[] = $delivery->load(['deliveredToUser', 'deliveredByUser', 'receivedByUser']);
      }

      if (!empty($errors)) {
        throw new Exception('Errores al confirmar entregas: ' . implode(', ', $errors));
      }

      return [
        'message' => 'Recepciones confirmadas correctamente',
        'confirmed_count' => count($confirmedDeliveries),
        'deliveries' => $confirmedDeliveries,
        'work_order_parts' => ApWorkOrderPartsResource::collection($workOrderParts)
      ];
    });
  }

  public function getAssignmentsByWorkOrder(int $workOrderId, array $data)
  {
    // Validar que la orden de trabajo existe
    $workOrder = ApWorkOrder::find($workOrderId);
    $person = Worker::find($data['delivered_to']);

    if (!$workOrder) {
      throw new Exception('Orden de trabajo no encontrada');
    }

    if (!$person) {
      throw new Exception('Persona no encontrada');
    }

    // Obtener todos los repuestos de la orden de trabajo con sus entregas
    $technicianUserId = $person->user->id;

    $workOrderParts = ApWorkOrderParts::with([
      'product',
      'warehouse',
      'deliveries' => function ($query) use ($technicianUserId) {
        $query->where('delivered_to', $technicianUserId)
          ->with([
            'deliveredToUser.person',
            'deliveredByUser',
            'receivedByUser',
          ]);
      },
    ])
      ->where('work_order_id', $workOrderId)
      ->get();

    // Formatear los datos para mostrar las asignaciones
    $assignments = [];

    foreach ($workOrderParts as $part) {
      foreach ($part->deliveries as $delivery) {
        $assignments[] = [
          'delivery_id' => $delivery->id,
          'work_order_part_id' => $part->id,
          'product' => [
            'id' => $part->product->id,
            'code' => $part->product->code,
            'name' => $part->product->name,
          ],
          'warehouse' => [
            'id' => $part->warehouse->id,
            'name' => $part->warehouse->name,
          ],
          'technician' => [
            'id' => $delivery->deliveredToUser->id,
            'name' => $delivery->deliveredToUser->name,
            'worker_id' => $delivery->deliveredToUser->person->id ?? null,
          ],
          'delivered_quantity' => $delivery->delivered_quantity,
          'delivered_date' => $delivery->delivered_date,
          'delivered_by' => $delivery->deliveredByUser ? [
            'id' => $delivery->deliveredByUser->id,
            'name' => $delivery->deliveredByUser->name,
          ] : null,
          'is_received' => $delivery->is_received,
          'received_date' => $delivery->received_date,
          'received_by' => $delivery->receivedByUser ? [
            'id' => $delivery->receivedByUser->id,
            'name' => $delivery->receivedByUser->name,
          ] : null,
          'received_signature_url' => $delivery->received_signature_url,
        ];
      }
    }

    return [
      'work_order_id' => $workOrderId,
      'total_assignments' => count($assignments),
      'assignments' => $assignments
    ];
  }

  public function getDeliveriesByWorkOrderPart(int $workOrderPartId)
  {
    $workOrderPart = $this->find($workOrderPartId);

    if (!$workOrderPart) {
      throw new Exception('Repuesto de orden de trabajo no encontrado');
    }

    $deliveries = ApWorkOrderPartDelivery::with([
      'deliveredToUser.person',
      'deliveredByUser',
      'receivedByUser',
    ])
      ->where('work_order_part_id', $workOrderPartId)
      ->orderBy('id', 'desc')
      ->get();

    return ApWorkOrderPartDeliveryResource::collection($deliveries);
  }
}
