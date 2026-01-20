<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApWorkOrderPartsResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
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

  /**
   * Calcular precios y totales automáticamente basándose en el último movimiento del producto
   * Aplica factor de tipo de cambio si la OT tiene cotización con moneda diferente
   */
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

    // Si no se proporciona unit_cost, obtener el último costo de compra
    if (!isset($data['unit_cost']) || $data['unit_cost'] === null) {
      $lastPurchase = PurchaseReceptionDetail::where('product_id', $productId)
        ->whereHas('reception', function ($query) {
          $query->where('status', 'COMPLETED');
        })
        ->with(['purchaseOrderItem'])
        ->latest('created_at')
        ->first();

      // Si hay una compra reciente, usar ese precio, sino usar el cost_price del producto
      $data['unit_cost'] = $lastPurchase && $lastPurchase->purchaseOrderItem
        ? $lastPurchase->purchaseOrderItem->unit_price
        : ($product->cost_price ?? 0);
    }

    // Si no se proporciona unit_price, usar el precio de venta del producto
    if (!isset($data['unit_price']) || $data['unit_price'] === null) {
      $data['unit_price'] = $product->sale_price ?? 0;
    }

    // Aplicar factor de tipo de cambio a los precios unitarios
    $data['unit_cost'] = floatval($data['unit_cost']) * $factor;
    $data['unit_price'] = floatval($data['unit_price']) * $factor;

    // Calcular subtotal (precio unitario * cantidad)
    $subtotalBase = $data['unit_price'] * $quantity;

    // Aplicar descuento al subtotal
    if ($discountPercentage > 0) {
      $discountAmount = $subtotalBase * ($discountPercentage / 100);
      $data['subtotal'] = $subtotalBase - $discountAmount;
    } else {
      $data['subtotal'] = $subtotalBase;
    }

    // Calcular tax_amount basándose en el tax_rate del producto
//    if (!isset($data['tax_amount']) || $data['tax_amount'] === null) {
//      if ($product->is_taxable && $product->tax_rate) {
//        $data['tax_amount'] = $data['subtotal'] * ($product->tax_rate / 100);
//      } else {
//        $data['tax_amount'] = 0;
//      }
//    }
    $data['tax_amount'] = 0;

    // Calcular total_amount
    $data['total_amount'] = $data['subtotal'] + $data['tax_amount'];
  }

  /**
   * Calcular el factor de tipo de cambio basándose en la OT y su cotización
   * Si la OT tiene cotización con moneda diferente, aplicar el tipo de cambio
   *
   * @param int $workOrderId
   * @return float
   * @throws Exception
   */
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

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Set registered_by
      if (auth()->check()) {
        $data['registered_by'] = auth()->user()->id;
      }

      // Calcular precios y totales automáticamente
      $this->calculatePricesAndTotals($data);

      // Create work order part (sin descontar stock, eso se hace en warehouseOutput)
      $workOrderPart = ApWorkOrderParts::create($data);

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

      // Update work order part
      $workOrderPart->update($data);

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

      return response()->json(['message' => 'Repuesto eliminado correctamente y stock devuelto al almacén']);
    });
  }

  /**
   * Guardar masivamente repuestos desde una cotización
   * Marca solo los detalles seleccionados como 'taken'
   * La salida de almacén se realiza posteriormente con warehouseOutput
   */
  public function storeBulkFromQuotation(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $quotationId = $data['quotation_id'];
      $workOrderId = $data['work_order_id'];
      $warehouseId = $data['warehouse_id'];
      $quotationDetailIds = $data['quotation_detail_ids'];

      // Obtener solo los detalles seleccionados
      $quotationDetails = ApOrderQuotationDetails::with('product')
        ->where('order_quotation_id', $quotationId)
        ->whereIn('id', $quotationDetailIds)
        ->get();

      if ($quotationDetails->isEmpty()) {
        throw new Exception('No se encontraron productos seleccionados');
      }

      // Crear los registros sin validar ni descontar stock
      $createdParts = [];

      foreach ($quotationDetails as $detail) {
        if (!$detail->product_id) {
          continue; // Skip si no tiene product_id
        }

        // Preparar datos para crear el repuesto
        $partData = [
          'work_order_id' => $workOrderId,
          'product_id' => $detail->product_id,
          'warehouse_id' => $warehouseId,
          'quantity_used' => $detail->quantity,
          'unit_price' => $detail->unit_price,
          'discount_percentage' => $detail->discount ?? 0,
          'group_number' => $data['group_number'],
        ];

        // Calcular precios y totales automáticamente
        $this->calculatePricesAndTotals($partData);

        // Set registered_by
        if (auth()->check()) {
          $partData['registered_by'] = auth()->user()->id;
        }

        // Crear el repuesto (sin descontar stock, eso se hace en warehouseOutput)
        $workOrderPart = ApWorkOrderParts::create($partData);

        // Cargar las relaciones necesarias
        $workOrderPart->load(['workOrder', 'product', 'warehouse']);

        $createdParts[] = $workOrderPart;

        // Marcar este detalle específico como tomado
        $detail->status = 'taken';
        $detail->save();
      }

      return [
        'message' => 'Repuestos agregados correctamente desde la cotización',
        'total_parts' => count($createdParts),
        'parts' => ApWorkOrderPartsResource::collection($createdParts)
      ];
    });
  }

  /**
   * Realizar la salida de almacén para un repuesto específico
   * Valida stock disponible y crea el movimiento de inventario
   */
  public function warehouseOutput(int $id)
  {
    return DB::transaction(function () use ($id) {
      $workOrderPart = $this->find($id);

      // Verificar que no se haya realizado ya la salida de almacén
      $existingMovement = InventoryMovement::where('reference_type', get_class($workOrderPart))
        ->where('reference_id', $workOrderPart->id)
        ->first();

      if ($existingMovement) {
        throw new Exception('Ya se realizó la salida de almacén para este repuesto');
      }

      // Validar stock disponible
      $stockService = new ProductWarehouseStockService();
      $stock = $stockService->getStock($workOrderPart->product_id, $workOrderPart->warehouse_id);

      if (!$stock) {
        throw new Exception("No se encontró registro de stock para el producto en el almacén seleccionado");
      }

      if ($stock->available_quantity < $workOrderPart->quantity_used) {
        throw new Exception(
          "Stock insuficiente. Disponible: {$stock->available_quantity}, Requerido: {$workOrderPart->quantity_used}"
        );
      }

      // Crear movimiento de inventario de salida
      $inventoryMovementService = new InventoryMovementService();
      $inventoryMovementService->createWorkOrderPartOutbound($workOrderPart);

      return [
        'message' => 'Salida de almacén realizada correctamente',
        'work_order_part' => new ApWorkOrderPartsResource($workOrderPart->load([
          'workOrder',
          'product',
          'warehouse'
        ]))
      ];
    });
  }
}
