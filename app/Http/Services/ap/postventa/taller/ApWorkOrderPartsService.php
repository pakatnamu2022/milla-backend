<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApWorkOrderPartsResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApPostVentaMasters;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
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
   */
  private function calculatePricesAndTotals(array &$data): void
  {
    $productId = $data['product_id'];
    $quantity = $data['quantity_used'];
    $discountPercentage = $data['discount_percentage'] ?? 0;

    // Obtener el producto
    $product = Products::find($productId);
    if (!$product) {
      throw new Exception('Producto no encontrado');
    }

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

    // Calcular subtotal (precio unitario * cantidad)
    if (!isset($data['subtotal']) || $data['subtotal'] === null) {
      $subtotalBase = $data['unit_price'] * $quantity;

      // Aplicar descuento al subtotal
      if ($discountPercentage > 0) {
        $discountAmount = $subtotalBase * ($discountPercentage / 100);
        $data['subtotal'] = $subtotalBase - $discountAmount;
      } else {
        $data['subtotal'] = $subtotalBase;
      }
    }

    // Calcular tax_amount basándose en el tax_rate del producto
    if (!isset($data['tax_amount']) || $data['tax_amount'] === null) {
      if ($product->is_taxable && $product->tax_rate) {
        $data['tax_amount'] = $data['subtotal'] * ($product->tax_rate / 100);
      } else {
        $data['tax_amount'] = 0;
      }
    }

    // Calcular total_amount
    if (!isset($data['total_amount']) || $data['total_amount'] === null) {
      $data['total_amount'] = $data['subtotal'] + $data['tax_amount'];
    }
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

      // Create work order part
      $workOrderPart = ApWorkOrderParts::create($data);

      // Crear movimiento de inventario de salida (descarga de stock directamente)
      $inventoryMovementService = new InventoryMovementService();
      $inventoryMovementService->createWorkOrderPartOutbound($workOrderPart);

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

      // Buscar el movimiento de inventario asociado a este repuesto
      $inventoryMovement = InventoryMovement::where('reference_type', get_class($workOrderPart))
        ->where('reference_id', $workOrderPart->id)
        ->first();

      // Si existe el movimiento, revertir el stock antes de eliminar
      if ($inventoryMovement) {
        $inventoryMovementService = new InventoryMovementService();
        $inventoryMovementService->reverseStockFromMovement($inventoryMovement->id);
      }

      // Eliminar el repuesto
      $workOrderPart->delete();

      return response()->json(['message' => 'Repuesto eliminado correctamente y stock devuelto al almacén']);
    });
  }

  /**
   * Obtener cotización activa por vehicle_id
   * Retorna la cotización vigente (expiration_date no vencida)
   * Solo retorna productos con status = 'pending'
   */
  public function getQuotationByVehicle($vehicleId)
  {
    $quotation = ApOrderQuotations::where('vehicle_id', $vehicleId)
      ->where('area_id', ApPostVentaMasters::AREA_TALLER_ID) // Área de Taller
      ->where('expiration_date', '>=', now())
      ->with([
        'details' => function ($query) {
          $query->where('status', 'pending');
        },
        'details.product',
        'vehicle',
        'sede',
        'createdBy'
      ])
      ->latest('created_at')
      ->first();

    if (!$quotation) {
      throw new Exception('No se encontró una cotización vigente para este vehículo');
    }

    return $quotation;
  }

  /**
   * Guardar masivamente repuestos desde una cotización
   * Valida stock de cada producto antes de guardar
   * Marca solo los detalles seleccionados como 'taken'
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

      // Validar stock para todos los productos ANTES de crear cualquier registro
      $stockService = new ProductWarehouseStockService();
      foreach ($quotationDetails as $detail) {
        if (!$detail->product_id) {
          continue; // Skip si no tiene product_id
        }

        $stock = $stockService->getStock($detail->product_id, $warehouseId);

        if (!$stock) {
          throw new Exception("No se encontró registro de stock para el producto: {$detail->product->name}");
        }

        if ($stock->available_quantity < $detail->quantity) {
          throw new Exception(
            "Stock insuficiente para: {$detail->product->name}. " .
            "Disponible: {$stock->available_quantity}, Requerido: {$detail->quantity}"
          );
        }
      }

      // Si todas las validaciones pasaron, crear los registros
      $createdParts = [];
      $inventoryMovementService = new InventoryMovementService();

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

        // Crear el repuesto
        $workOrderPart = ApWorkOrderParts::create($partData);

        // Crear movimiento de inventario de salida
        $inventoryMovementService->createWorkOrderPartOutbound($workOrderPart);

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

}
