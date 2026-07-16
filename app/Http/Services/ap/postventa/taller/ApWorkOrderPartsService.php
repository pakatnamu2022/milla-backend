<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApWorkOrderPartsResource;
use App\Http\Resources\ap\postventa\taller\ApWorkOrderPartDeliveryResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Http\Utils\PriceRounding;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\ApWorkOrderPartDelivery;
use App\Models\gp\gestionhumana\personal\Worker;
use Barryvdh\DomPDF\Facade\Pdf;
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

    // unit_price (convertido por factor) + total_cost/net_amount/tax_amount: única
    // fuente de verdad compartida con mano de obra y detalles de cotización.
    $result = PriceRounding::calculateLine(floatval($data['unit_price']), $quantity, $discountPercentage, $factor);
    $data['unit_price'] = $result['unit_price'];
    $data['total_cost'] = $result['total_cost'];
    $data['net_amount'] = $result['net_amount'];
    $data['tax_amount'] = $result['tax_amount'];
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

  private function validateSalePrice(array $data, ApWorkOrder $workOrder): void
  {
    // Solo validar si la OT está en SOLES
    // Si la OT está en dólares, no validar porque el precio viene en dólares
    // y la conversión interna a soles ya se maneja en calculatePricesAndTotals
    if ($workOrder->currency_id !== TypeCurrency::PEN_ID) {
      return; // Salir sin validar si no es en soles
    }

    // Validamos el precio de venta al público no esté por debajo de lo establecido
    $sale_price = ProductWarehouseStock::where('product_id', $data['product_id'])
      ->where('warehouse_id', $data['warehouse_id'])
      ->value('sale_price');

    if ($sale_price && $data['unit_price'] < $sale_price) {
      throw new Exception("El precio unitario no puede ser menor al precio de venta registrado ({$sale_price}) para este producto en el almacén seleccionado");
    }
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::find($data['work_order_id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureCanBeModified();

      $validateReceipt = $workOrder->shouldValidateReceipt();

      if ($workOrder->vehicleInspection === null && $validateReceipt) {
        throw new Exception('No se puede agregar repuestos a una orden de trabajo sin recepción de vehículo');
      }

      // Validar que no exista el mismo producto en la orden de trabajo
      $existingPart = ApWorkOrderParts::where('work_order_id', $data['work_order_id'])
        ->where('product_id', $data['product_id'])
        ->first();

      if ($existingPart) {
        throw new Exception('Este producto ya ha sido agregado a la orden de trabajo');
      }

      // Set registered_by
      if (auth()->check()) {
        $data['registered_by'] = auth()->user()->id;
      }

      // Validar decimales según la unidad de medida del producto
      $product = Products::find($data['product_id']);
      $product->validateDecimals($data['quantity_used']);

      // Calcular precios y totales automáticamente
      $this->calculatePricesAndTotals($data);

      // Validar precio de venta (DESPUÉS de calculatePricesAndTotals para usar valores redondeados)
      $this->validateSalePrice($data, $workOrder);

      // Validar que exista stock disponible para reservar
      $stock = ProductWarehouseStock::where('product_id', $data['product_id'])
        ->where('warehouse_id', $data['warehouse_id'])
        ->first();

      if (!$stock) {
        throw new Exception('No se encontró registro de stock para el producto en el almacén seleccionado');
      }

      $inventoryMovementService = app(InventoryMovementService::class);

      $externalStock = $inventoryMovementService->validateStockInExternalSystem(
        $stock->product->dyn_code,
        $stock->warehouse->dyn_code,
      );

      // El SP retorna ArticuloStock como string, convertir a float para comparar
      $availableQuantityExternal = isset($externalStock['ArticuloStock'])
        ? (float)trim($externalStock['ArticuloStock'])
        : 0;

      if ($availableQuantityExternal < $data['quantity_used']) {
        throw new Exception(
          "Stock insuficiente en sistema dynamics para el repuesto: {$stock->product->description}. " .
          "Stock disponible en Dynamics: {$availableQuantityExternal}, Cantidad requerida: {$data['quantity_used']}"
        );
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

      app(WorkOrderService::class)->performWorkOrderRecalculation($workOrder);

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
      $workOrder = $workOrderPart->workOrder;

      $workOrder->ensureCanBeModified();

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

      // Determinar los valores finales
      $newProductId = $data['product_id'] ?? $oldProductId;
      $newWarehouseId = $data['warehouse_id'] ?? $oldWarehouseId;
      $newQuantity = $data['quantity_used'] ?? $oldQuantity;

      // Validar decimales si cambió la cantidad o el producto
      if (isset($data['quantity_used']) || isset($data['product_id'])) {
        $product = Products::find($newProductId);
        $product->validateDecimals($newQuantity);
      }

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

        $inventoryMovementService = app(InventoryMovementService::class);

        $externalStock = $inventoryMovementService->validateStockInExternalSystem(
          $newStock->product->dyn_code,
          $newStock->warehouse->dyn_code,
        );

        // El SP retorna ArticuloStock como string, convertir a float para comparar
        $availableQuantityExternal = isset($externalStock['ArticuloStock'])
          ? (float)trim($externalStock['ArticuloStock'])
          : 0;

        if ($availableQuantityExternal < $data['quantity_used']) {
          throw new Exception(
            "Stock insuficiente en sistema dynamics para el repuesto: {$newStock->product->description}. " .
            "Stock disponible en Dynamics: {$availableQuantityExternal}, Cantidad requerida: {$data['quantity_used']}"
          );
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

        // Validar precio de venta (DESPUÉS de calculatePricesAndTotals para usar valores redondeados y considerar moneda)
        $this->validateSalePrice($recalcData, $workOrder);

        // Validar que el nuevo monto no sea menor al monto pagado en anticipos
        $workOrder->refresh();
        $currentTotals = $workOrder->getTotalsArray();

        // Calcular el cambio en net_amount
        $oldNetAmount = $workOrderPart->net_amount;
        $newNetAmount = $recalcData['net_amount'];
        $deltaNetAmount = $newNetAmount - $oldNetAmount;

        // Aplicar IGV al delta (usar la misma lógica que ApWorkOrder::getTotalsArray)
        $deltaWithTax = $deltaNetAmount * (1 + Constants::VAT_TAX / 100);

        // Proyectar el nuevo total (final_amount incluye IGV)
        $projectedFinalAmount = $currentTotals['total_amount'] + $deltaWithTax;

        // Validar
        $workOrder->validateMinimumAmount($projectedFinalAmount);
      }

      // Update work order part
      $workOrderPart->update($data);
      app(WorkOrderService::class)->performWorkOrderRecalculation($workOrder);

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
      $workOrder = $workOrderPart->workOrder;

      $workOrder->ensureCanBeModified();

      // Validar que no existan asignaciones a técnicos (entregas)
      if ($workOrderPart->deliveries()->exists()) {
        throw new Exception('No se puede eliminar el repuesto porque tiene asignaciones a técnicos. Debe eliminar todas las asignaciones antes de eliminar el repuesto');
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

      // Validar que el nuevo monto no sea menor al monto pagado en anticipos
      $workOrder->refresh();
      $currentTotals = $workOrder->getTotalsArray();

      // Calcular el monto del item con IGV incluido (usar la misma lógica que ApWorkOrder::getTotalsArray)
      $itemNetAmount = $workOrderPart->net_amount;
      $itemWithTax = $itemNetAmount * (1 + Constants::VAT_TAX / 100);

      // Proyectar el nuevo total (final_amount incluye IGV)
      $projectedFinalAmount = $currentTotals['total_amount'] - $itemWithTax;

      $workOrder->validateMinimumAmount($projectedFinalAmount);

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
          ->first();

        if ($quotationDetail) {
          $quotationDetail->status = 'pending';
          $quotationDetail->save();
        }
      }

      // Eliminar el repuesto
      $workOrderPart->delete();
      app(WorkOrderService::class)->performWorkOrderRecalculation($workOrder);

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

      $inventoryMovementService = app(InventoryMovementService::class);

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

        // Validar decimales según la unidad de medida del producto
        $product = Products::find($detail->product_id);
        $product->validateDecimals($detail->quantity);

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

        $externalStock = $inventoryMovementService->validateStockInExternalSystem(
          $stock->product->dyn_code,
          $stock->warehouse->dyn_code
        );

        // El SP retorna ArticuloStock como string, convertir a float para comparar
        $availableQuantityExternal = isset($externalStock['ArticuloStock'])
          ? (float)trim($externalStock['ArticuloStock'])
          : 0;

        if ($availableQuantityExternal < $detail->quantity) {
          throw new Exception(
            "Stock insuficiente en sistema externo para el producto: {$detail->product->description}. " .
            "Stock disponible en Dynamics: {$availableQuantityExternal}, Cantidad requerida: {$detail->quantity}"
          );
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

      app(WorkOrderService::class)->performWorkOrderRecalculation($workOrder);

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

      // Validar decimales según la unidad de medida del producto
      $workOrderPart->product->validateDecimals($deliveredQuantity);

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
          $errors[] = "Usuario no encontrado";
          continue;
        }

        // Validar que el usuario autenticado sea el asignado al repuesto
        if ($delivery->delivered_to !== $currentUser->id) {
          $errors[] = "La entrega de repuesto debe ser confirmada por el técnico asignado.";
          continue;
        }

        if ($delivery->is_received) {
          $errors[] = "La entrega del repuesto ya ha sido confirmada";
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

  public function unassignFromTechnician(int $deliveryId)
  {
    return DB::transaction(function () use ($deliveryId) {
      // Buscar el registro de entrega
      $delivery = ApWorkOrderPartDelivery::find($deliveryId);

      if (!$delivery) {
        throw new Exception('Registro de entrega no encontrado');
      }

      // Validar que no haya sido confirmado por el técnico
//      if ($delivery->is_received) {
//        throw new Exception('No se puede desasignar el repuesto porque ya ha sido confirmado por el técnico');
//      }

      // Obtener el repuesto asociado
      $workOrderPart = $delivery->workOrderPart;

      if (!$workOrderPart) {
        throw new Exception('Repuesto de orden de trabajo no encontrado');
      }

      // Restar la cantidad asignada
      $workOrderPart->assigned_quantity = ($workOrderPart->assigned_quantity ?? 0) - $delivery->delivered_quantity;
      $workOrderPart->save();

      // Eliminar el registro de entrega
      $delivery->delete();

      return [
        'message' => 'Asignación eliminada correctamente',
        'work_order_part' => new ApWorkOrderPartsResource($workOrderPart->load(['workOrder', 'product', 'warehouse', 'deliveries']))
      ];
    });
  }

  public function assignToTechnicianBulk(array $data)
  {
    return DB::transaction(function () use ($data) {
      $deliveredTo = Worker::find($data['delivered_to']);

      if (!$deliveredTo) {
        throw new Exception('Técnico no encontrado');
      }

      $technicianUserId = $deliveredTo->user->id;
      $assignments = $data['assignments'];

      if (empty($assignments)) {
        throw new Exception('Debe proporcionar al menos un repuesto para asignar');
      }

      $createdDeliveries = [];
      $updatedParts = [];
      $errors = [];

      foreach ($assignments as $assignment) {
        try {
          $workOrderPartId = $assignment['work_order_part_id'];
          $deliveredQuantity = $assignment['delivered_quantity'];

          // Buscar el repuesto
          $workOrderPart = $this->find($workOrderPartId);

          // Validar decimales según la unidad de medida del producto
          $workOrderPart->product->validateDecimals($deliveredQuantity);

          // Calcular cantidad ya asignada
          $totalAssigned = $workOrderPart->assigned_quantity ?? 0;

          // Validar que no exceda la cantidad total
          $newTotalAssigned = $totalAssigned + $deliveredQuantity;
          if ($newTotalAssigned > $workOrderPart->quantity_used) {
            $errors[] = "Repuesto {$workOrderPart->product->code}: La cantidad a asignar excede la cantidad disponible. Disponible: " .
              ($workOrderPart->quantity_used - $totalAssigned) . ", Solicitado: {$deliveredQuantity}";
            continue;
          }

          // Crear registro de entrega
          $delivery = ApWorkOrderPartDelivery::create([
            'work_order_part_id' => $workOrderPartId,
            'delivered_to' => $technicianUserId,
            'delivered_quantity' => $deliveredQuantity,
            'delivered_date' => now(),
            'delivered_by' => auth()->check() ? auth()->user()->id : null,
            'is_received' => false,
          ]);

          // Actualizar cantidad asignada en el repuesto
          $workOrderPart->assigned_quantity = $newTotalAssigned;
          $workOrderPart->save();

          $createdDeliveries[] = $delivery->load(['deliveredToUser', 'deliveredByUser']);
          $updatedParts[] = new ApWorkOrderPartsResource($workOrderPart->load(['workOrder', 'product', 'warehouse', 'deliveries']));

        } catch (\Throwable $th) {
          $errors[] = "Error al asignar repuesto ID {$workOrderPartId}: " . $th->getMessage();
        }
      }

      if (empty($createdDeliveries)) {
        throw new Exception('No se pudo asignar ningún repuesto. Errores: ' . implode('; ', $errors));
      }

      $response = [
        'message' => 'Asignación masiva completada',
        'total_assigned' => count($createdDeliveries),
        'total_requested' => count($assignments),
        'deliveries' => $createdDeliveries,
        'work_order_parts' => $updatedParts,
      ];

      if (!empty($errors)) {
        $response['errors'] = $errors;
      }

      return $response;
    });
  }

  public function generateWorkOrderPartsReportPDF(int $workOrderId)
  {
    $workOrder = ApWorkOrder::with([
      'vehicle.model.family.brand',
      'vehicle.color',
      'invoiceTo.district',
      'parts.product',
      'parts.warehouse',
      'parts.deliveries.deliveredToUser.person',
      'parts.deliveries.deliveredByUser',
      'orderQuotation',
      'sede.company',
      'sede.district',
      'sede.province'
    ])->find($workOrderId);

    if (!$workOrder) {
      throw new Exception('Orden de trabajo no encontrada');
    }

    // Preparar datos básicos
    $data = [
      'work_order_number' => $workOrder->id,
      'work_order_date' => $workOrder->created_at->format('d/m/Y'),
      'status' => $workOrder->status ? $workOrder->status->description : 'N/A',
      'sede' => $workOrder->sede,
    ];

    // Datos del cliente
    if ($workOrder->invoiceTo) {
      $customer = $workOrder->invoiceTo;
      $data['customer_name'] = $customer->full_name ?? 'N/A';
      $data['customer_document'] = $customer->num_doc ?? 'N/A';
      $data['customer_address'] = $customer->direction ?? 'N/A';
      $data['customer_district'] = $customer->district ? $customer->district->name : 'N/A';
      $data['customer_email'] = $customer->email ?? 'N/A';
      $data['customer_phone'] = $customer->phone ?? 'N/A';
    } else {
      $data['customer_name'] = 'N/A';
      $data['customer_document'] = 'N/A';
      $data['customer_address'] = 'N/A';
      $data['customer_district'] = 'N/A';
      $data['customer_email'] = 'N/A';
      $data['customer_phone'] = 'N/A';
    }

    // Datos del vehículo
    if ($workOrder->vehicle) {
      $vehicle = $workOrder->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? 'N/A';
      $data['vehicle_vin'] = $vehicle->vin ?? 'N/A';
      $data['vehicle_engine'] = $vehicle->engine_number ?? 'N/A';
      $data['vehicle_model'] = $vehicle->model ? $vehicle->model->version : 'N/A';
      $data['vehicle_brand'] = $vehicle->model && $vehicle->model->family && $vehicle->model->family->brand
        ? $vehicle->model->family->brand->name
        : 'N/A';
      $data['vehicle_color'] = $vehicle->color ? $vehicle->color->description : 'N/A';
      $data['vehicle_km'] = $workOrder->mileage ?? 'N/A';
    } else {
      $data['vehicle_plate'] = 'N/A';
      $data['vehicle_vin'] = 'N/A';
      $data['vehicle_engine'] = 'N/A';
      $data['vehicle_model'] = 'N/A';
      $data['vehicle_brand'] = 'N/A';
      $data['vehicle_color'] = 'N/A';
      $data['vehicle_km'] = 'N/A';
    }

    // Detalles de repuestos con asignaciones a técnicos
    $data['parts'] = $workOrder->parts->map(function ($part) {
      $deliveries = $part->deliveries->map(function ($delivery) {
        return [
          'technician_name' => $delivery->deliveredToUser && $delivery->deliveredToUser->person
            ? $delivery->deliveredToUser->person->nombre_completo
            : 'N/A',
          'delivered_quantity' => $delivery->delivered_quantity,
          'delivered_date' => $delivery->delivered_date ? $delivery->delivered_date->format('d/m/Y H:i') : 'N/A',
          'delivered_by' => $delivery->deliveredByUser ? $delivery->deliveredByUser->name : 'N/A',
          'is_received' => $delivery->is_received ? 'Sí' : 'No',
          'received_date' => $delivery->received_date ? $delivery->received_date->format('d/m/Y H:i') : '-',
        ];
      });

      return [
        'code' => $part->product ? $part->product->code : 'N/A',
        'name' => $part->product ? $part->product->name : 'N/A',
        'warehouse' => $part->warehouse ? $part->warehouse->name : 'N/A',
        'quantity_used' => $part->quantity_used,
        'unit_price' => $part->unit_price,
        'discount_percentage' => $part->discount_percentage ?? 0,
        'total_cost' => $part->total_cost,
        'net_amount' => $part->net_amount,
        'tax_amount' => $part->tax_amount,
        'assigned_quantity' => $part->assigned_quantity ?? 0,
        'pending_quantity' => $part->quantity_used - ($part->assigned_quantity ?? 0),
        'deliveries' => $deliveries,
        'has_deliveries' => $deliveries->count() > 0,
      ];
    });

    // Calcular totales
    $totalParts = $workOrder->parts->sum('total_cost');
    $totalDiscounts = $workOrder->parts->sum(function ($part) {
      return $part->total_cost - $part->net_amount;
    });
    $baseImponible = $workOrder->parts->sum('net_amount');
    $igvAmount = $workOrder->parts->sum('tax_amount');
    $totalAmount = $baseImponible + $igvAmount;

    $data['total_parts'] = $totalParts;
    $data['total_discounts'] = $totalDiscounts;
    $data['base_imponible'] = $baseImponible;
    $data['tax_amount'] = $igvAmount;
    $data['total_amount'] = $totalAmount;

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.work-order-parts-report', [
      'workOrder' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Reporte_Repuestos_OT_' . $workOrder->id . '.pdf';

    return $pdf->download($fileName);
  }
}
