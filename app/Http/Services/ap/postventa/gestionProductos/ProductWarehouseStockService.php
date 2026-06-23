<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\ProductWarehouseStockResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\compras\SupplierCreditNote;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\GeneralMaster;
use Illuminate\Http\Request;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductWarehouseStockService extends BaseService
{
  /**
   * Configuración: Incluir movimientos RETURN_OUT en el historial de existencias.
   * 0 = No incluyas los movimientos RETURN_OUT.
   * 1 = Incluir los movimientos RETURN_OUT como reducción de existencias
   */
  const INCLUDE_RETURN_OUT_IN_HISTORY = 0;

  /**
   * Configuración: Cantidad a utilizar para el cálculo del costo promedio en PURCHASE_RECEPTION
   * true = Utilice quantity_received (cantidad física recibida en buen estado)
   * false = Utilice la cantidad facturada (quantity_received + observed_quantity)
   */
  const USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true;

  private ?float $freightCommission = null;
  private ?float $profitMargin = null;
  private ?float $minimunDiscount = null;

  protected ExportService $exportService;

  public function __construct(ExportService $exportService)
  {
    $this->exportService = $exportService;
  }

  /**
   * Get freight commission percentage from GeneralMaster (cached)
   *
   * @return float
   */
  private function getFreightCommission(): float
  {
    if ($this->freightCommission === null) {
      $this->freightCommission = GeneralMaster::find(GeneralMaster::FREIGHT_COMMISSION_ID)->value ?? 0.05;
    }
    return $this->freightCommission;
  }

  /**
   * Get profit margin percentage from GeneralMaster (cached)
   *
   * @return float
   */
  private function getProfitMargin(): float
  {
    if ($this->profitMargin === null) {
      $this->profitMargin = GeneralMaster::find(GeneralMaster::PROFIT_MARGIN_ID)->value ?? 0.30;
    }
    return $this->profitMargin;
  }

  private function getMinimunDiscount(): float
  {
    if ($this->minimunDiscount === null) {
      $this->minimunDiscount = GeneralMaster::find(GeneralMaster::ADVISOR_DISCOUNT_PERCENTAGE_PV_ID)->value ?? 0.05;
    }
    return $this->minimunDiscount;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ProductWarehouseStock::class,
      $request,
      ProductWarehouseStock::filters,
      ProductWarehouseStock::sorts,
      ProductWarehouseStockResource::class,
    );
  }

  public function find($id)
  {
    $productWarehouseStock = ProductWarehouseStock::where('id', $id)->first();
    if (!$productWarehouseStock) {
      throw new Exception('Registro de stock no encontrado');
    }
    return $productWarehouseStock;
  }

  public function update(mixed $data)
  {
    $productWarehouseStock = $this->find($data['id']);
    $productWarehouseStock->update($data);
    return new ProductWarehouseStockResource($productWarehouseStock);
  }

  /**
   * Add stock to warehouse with automatic currency conversion to PEN (base currency)
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param float $quantity Quantity to add
   * @param float $unitCost Unit cost in original currency
   * @param int|null $currencyId Currency ID of the unit cost (default: PEN = 3)
   * @param float|null $exchangeRate Exchange rate to convert to PEN (only for non-PEN currencies)
   * @return ProductWarehouseStock Updated stock record
   * @throws Exception
   */
  public function addStock(
    int    $productId,
    int    $warehouseId,
    float  $quantity,
    float  $unitCost = 0,
    ?int   $currencyId = null,
    ?float $exchangeRate = null
  ): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      // Find or create stock record
      $stock = ProductWarehouseStock::firstOrCreate(
        [
          'product_id' => $productId,
          'warehouse_id' => $warehouseId,
        ],
        [
          'quantity' => 0,
          'quantity_in_transit' => 0,
          'quantity_pending_credit_note' => 0,
          'reserved_quantity' => 0,
          'available_quantity' => 0,
          'minimum_stock' => 0,
          'maximum_stock' => 0,
          'average_cost' => 0,
          'currency_id' => TypeCurrency::PEN_ID, // Always PEN (base currency)
        ]
      );

      // Calcule el costo promedio ponderado si se proporciona el costo unitario.
      if ($unitCost > 0) {
        // Convertir el costo unitario a PEN (moneda base) si es necesario.
        $unitCostInPEN = $this->convertToBaseCurrency($unitCost, $currencyId, $exchangeRate);

        $currentStock = $stock->quantity;
        $currentAverageCost = $stock->average_cost ?? 0;

        // Fórmula del costo promedio ponderado (todo en PEN):
        // nuevo_costo_promedio = (current_stock × current_average_cost + new_quantity × unit_cost_in_PEN) / (current_stock + new_quantity)
        if ($currentStock + $quantity > 0) {
          $newAverageCost = (($currentStock * $currentAverageCost) + ($quantity * $unitCostInPEN)) / ($currentStock + $quantity);
          $stock->average_cost = round($newAverageCost, 2);
        } else {
          $stock->average_cost = $unitCostInPEN;
        }

        // Actualizar cost_price al último costo unitario de compra (en PEN)
        $stock->cost_price = $unitCostInPEN;

        // Update sale_price based on average cost with freight commission and profit margin
        $profitMargin = $this->getProfitMargin();
        $freightCommission = $this->getFreightCommission();

        if (ProductWarehouseStock::PRICE_CALCULATION_METHOD === 1) {
          // Método 1: PVP = Costo / (1 - margen) * (1 + impuesto)
          $stock->sale_price = round(
            ($stock->average_cost / (1 - $profitMargin)) * (1 + $freightCommission),
            2
          );
        } else {
          // Método 2 (por defecto): PVP = Costo / (1 - (margen + impuesto))
          $stock->sale_price = round(
            $stock->average_cost / (1 - ($profitMargin + $freightCommission)),
            2
          );
        }
      }

      // Add quantity (physical stock that actually arrived in good condition)
      $stock->quantity += $quantity;
      $stock->last_movement_date = now();

      // Update available quantity
      $stock->updateAvailableQuantity();

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Convertir importe de la moneda original a la moneda base (PEN)
   *
   * @param float $amount Importe en moneda original
   * @param int|null $currencyId Currency ID (null or PEN_ID means already in PEN)
   * @param float|null $exchangeRate ID de la moneda (nulo o PEN_ID significa que ya está en PEN)
   * @return float Cantidad convertida a PEN
   */
  private function convertToBaseCurrency(float $amount, ?int $currencyId, ?float $exchangeRate): float
  {
    // If no currency specified or already in PEN, return as is
    if ($currencyId === null || $currencyId === TypeCurrency::PEN_ID) {
      return $amount;
    }

    // If currency is USD and exchange rate is provided, convert to PEN
    if ($currencyId === TypeCurrency::USD_ID && $exchangeRate && $exchangeRate > 0) {
      return round($amount * $exchangeRate, 2);
    }

    // For other currencies with exchange rate, apply conversion
    if ($exchangeRate && $exchangeRate > 0) {
      return round($amount * $exchangeRate, 2);
    }

    // If no valid exchange rate, return amount as is (assume already in PEN)
    return $amount;
  }

  /**
   * Add quantity to in-transit stock (when purchase order is created)
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function addInTransitStock(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      // Find or create stock record
      $stock = ProductWarehouseStock::firstOrCreate(
        [
          'product_id' => $productId,
          'warehouse_id' => $warehouseId,
        ],
        [
          'quantity' => 0,
          'quantity_in_transit' => 0,
          'quantity_pending_credit_note' => 0,
          'reserved_quantity' => 0,
          'available_quantity' => 0,
          'minimum_stock' => 0,
          'maximum_stock' => 0,
        ]
      );

      // Add to in-transit quantity
      $stock->quantity_in_transit += $quantity;
      $stock->save();

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Remove quantity from in-transit stock (when purchase order is cancelled or received)
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function removeInTransitStock(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->firstOrFail();

      // Remove from in-transit quantity
      $stock->quantity_in_transit -= $quantity;
      if ($stock->quantity_in_transit < 0) {
        $stock->quantity_in_transit = 0;
      }
      $stock->save();

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception("No se encontró registro de stock para el producto ID {$productId} en el almacén ID {$warehouseId}. El producto debe estar registrado en el almacén antes de crear la orden de compra.");
    }
  }

  /**
   * Update in-transit stock (when purchase order is edited)
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $oldQuantity
   * @param float $newQuantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function updateInTransitStock(int $productId, int $warehouseId, float $oldQuantity, float $newQuantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->first();

      if (!$stock) {
        // If doesn't exist, create with new quantity
        return $this->addInTransitStock($productId, $warehouseId, $newQuantity);
      }

      // Calculate difference
      $difference = $newQuantity - $oldQuantity;
      $stock->quantity_in_transit += $difference;

      if ($stock->quantity_in_transit < 0) {
        $stock->quantity_in_transit = 0;
      }

      $stock->save();

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Add quantity to pending credit note (when received with observation)
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function addPendingCreditNote(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      // Find or create stock record
      $stock = ProductWarehouseStock::firstOrCreate(
        [
          'product_id' => $productId,
          'warehouse_id' => $warehouseId,
        ],
        [
          'quantity' => 0,
          'quantity_in_transit' => 0,
          'quantity_pending_credit_note' => 0,
          'reserved_quantity' => 0,
          'available_quantity' => 0,
          'minimum_stock' => 0,
          'maximum_stock' => 0,
        ]
      );

      // Add to pending credit note
      $stock->quantity_pending_credit_note += $quantity;
      $stock->save();

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Remove quantity from pending credit note (when reception is deleted)
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function removePendingCreditNote(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->firstOrFail();

      // Remove from pending credit note
      $stock->quantity_pending_credit_note -= $quantity;
      if ($stock->quantity_pending_credit_note < 0) {
        $stock->quantity_pending_credit_note = 0;
      }
      $stock->save();

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception("No se encontró registro de stock para el producto ID {$productId} en el almacén ID {$warehouseId}");
    }
  }

  /**
   * Remove stock from warehouse
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function removeStock(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->firstOrFail();

      // Check if there's enough stock
      if (!$stock->removeStock($quantity)) {
        throw new Exception("No hay suficiente stock disponible para el producto ID {$productId} en el almacén ID {$warehouseId}. Disponible: {$stock->available_quantity}, Solicitado: {$quantity}");
      }

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update stock from inventory movement
   * This is the main method that will be called when a movement is approved
   *
   * @param InventoryMovement $movement
   * @return array Array of updated stock records
   * @throws Exception
   */
  public function updateStockFromMovement(InventoryMovement $movement): array
  {
    DB::beginTransaction();
    try {
      $updatedStocks = [];

      foreach ($movement->details as $detail) {
        $productId = $detail->product_id;
        $quantity = $detail->quantity;

        // Determine if this is an inbound or outbound movement
        if ($movement->is_inbound) {
          // INBOUND: Add stock to warehouse
          // Pass unit_cost for PURCHASE_RECEPTION and ADJUSTMENT_IN movements to calculate weighted average cost
          // - PURCHASE_RECEPTION: unit_cost comes from adjusted purchase price (total invoiced / qty received)
          // - ADJUSTMENT_IN: unit_cost comes from user input for initial stock or manual adjustments
          $unitCost = 0;
          if (in_array($movement->movement_type, [
            InventoryMovement::TYPE_PURCHASE_RECEPTION,
            InventoryMovement::TYPE_ADJUSTMENT_IN
          ])) {
            $unitCost = $detail->unit_cost ?? 0;
          }

          // Pass currency and exchange rate from movement for proper conversion to PEN
          $stock = $this->addStock(
            $productId,
            $movement->warehouse_id,
            abs($quantity),
            $unitCost,
            $movement->currency_id,
            $movement->exchange_rate
          );
          $updatedStocks[] = $stock;
        } else {
          // OUTBOUND: Remove stock from warehouse
          $stock = $this->removeStock($productId, $movement->warehouse_id, abs($quantity));
          $updatedStocks[] = $stock;
        }

        // Handle transfers (TRANSFER_OUT and TRANSFER_IN)
        if ($movement->movement_type === InventoryMovement::TYPE_TRANSFER_IN && $movement->warehouse_destination_id) {
          // For transfers, also update destination warehouse
          $destinationStock = $this->addStock($productId, $movement->warehouse_destination_id, abs($quantity));
          $updatedStocks[] = $destinationStock;
        }
      }

      DB::commit();
      return $updatedStocks;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Reserve stock for a product
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function reserveStock(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->firstOrFail();

      if (!$stock->reserveStock($quantity)) {
        throw new Exception("No hay suficiente stock disponible para reservar. Producto ID {$productId}, Almacén ID {$warehouseId}. Disponible: {$stock->available_quantity}, Solicitado: {$quantity}");
      }

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Release reserved stock
   *
   * @param int $productId
   * @param int $warehouseId
   * @param float $quantity
   * @return ProductWarehouseStock
   * @throws Exception
   */
  public function releaseReservedStock(int $productId, int $warehouseId, float $quantity): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->firstOrFail();

      $stock->releaseReservedStock($quantity);

      DB::commit();
      return $stock;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get stock by product and warehouse
   *
   * @param int $productId
   * @param int $warehouseId
   * @return ProductWarehouseStock|null
   */
  public function getStock(int $productId, int $warehouseId): ?ProductWarehouseStock
  {
    return ProductWarehouseStock::where('product_id', $productId)
      ->where('warehouse_id', $warehouseId)
      ->first();
  }

  /**
   * Get all stock for a product across all warehouses
   *
   * @param int $productId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getStockByProduct(int $productId)
  {
    return ProductWarehouseStock::where('product_id', $productId)
      ->with('warehouse')
      ->get();
  }

  /**
   * Get all stock in a warehouse
   *
   * @param int $warehouseId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getStockByWarehouse(int $warehouseId)
  {
    return ProductWarehouseStock::where('warehouse_id', $warehouseId)
      ->with('product')
      ->get();
  }

  /**
   * Get products with low stock
   *
   * @param int|null $warehouseId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getLowStockProducts(?int $warehouseId = null)
  {
    $query = ProductWarehouseStock::lowStock()
      ->with(['product', 'warehouse']);

    if ($warehouseId) {
      $query->where('warehouse_id', $warehouseId);
    }

    return $query->get();
  }

  /**
   * Get products out of stock
   *
   * @param int|null $warehouseId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getOutOfStockProducts(?int $warehouseId = null)
  {
    $query = ProductWarehouseStock::outOfStock()
      ->with(['product', 'warehouse']);

    if ($warehouseId) {
      $query->where('warehouse_id', $warehouseId);
    }

    return $query->get();
  }

  /**
   * Move stock from quantity to quantity_in_transit (for transfers)
   * Used when creating TRANSFER_OUT movement
   *
   * @param InventoryMovement $movement
   * @return array Updated stock records
   * @throws Exception
   */
  public function moveStockToInTransit(InventoryMovement $movement): array
  {
    DB::beginTransaction();
    try {
      $updatedStocks = [];

      foreach ($movement->details as $detail) {
        // Get stock record
        $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
          ->where('warehouse_id', $movement->warehouse_id)
          ->first();

        if (!$stock) {
          throw new Exception(
            "No se encontró stock para producto ID {$detail->product_id} en almacén {$movement->warehouse_id}"
          );
        }

        // Validate sufficient quantity
        if ($stock->quantity < $detail->quantity) {
          throw new Exception(
            "Stock insuficiente para producto ID {$detail->product_id}. " .
            "Stock: {$stock->quantity}, Solicitado: {$detail->quantity}"
          );
        }

        // Move from quantity to quantity_in_transit
        $stock->quantity -= $detail->quantity;
        $stock->quantity_in_transit += $detail->quantity;
        $stock->last_movement_date = now();

        // Update available quantity (quantity - reserved)
        $stock->updateAvailableQuantity();

        $updatedStocks[] = $stock;
      }

      DB::commit();
      return $updatedStocks;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Move stock from quantity_in_transit to quantity (when reception is done)
   * Used when creating TRANSFER_IN movement after reception
   *
   * @param int $productId
   * @param int $warehouseOriginId
   * @param int $warehouseDestinationId
   * @param float $quantityReceived
   * @return array [origin_stock, destination_stock]
   * @throws Exception
   */
  public function moveFromInTransitToDestination(
    int   $productId,
    int   $warehouseOriginId,
    int   $warehouseDestinationId,
    float $quantityReceived
  ): array
  {
    DB::beginTransaction();
    try {
      // Remove from in_transit in origin warehouse
      $originStock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseOriginId)
        ->first();

      if (!$originStock) {
        throw new Exception(
          "No se encontró stock en tránsito para producto ID {$productId} en almacén origen {$warehouseOriginId}"
        );
      }

      if ($originStock->quantity_in_transit < $quantityReceived) {
        throw new Exception(
          "Stock en tránsito insuficiente para producto ID {$productId}. " .
          "En tránsito: {$originStock->quantity_in_transit}, Recibido: {$quantityReceived}"
        );
      }

      // Remove from in_transit
      $originStock->quantity_in_transit -= $quantityReceived;
      $originStock->last_movement_date = now();
      $originStock->save();

      // Add to quantity in destination warehouse
      $destinationStock = ProductWarehouseStock::firstOrCreate(
        [
          'product_id' => $productId,
          'warehouse_id' => $warehouseDestinationId,
        ],
        [
          'quantity' => 0,
          'quantity_in_transit' => 0,
          'quantity_pending_credit_note' => 0,
          'reserved_quantity' => 0,
          'available_quantity' => 0,
          'minimum_stock' => 0,
          'maximum_stock' => 0,
        ]
      );

      $destinationStock->quantity += $quantityReceived;
      $destinationStock->last_movement_date = now();
      $destinationStock->updateAvailableQuantity();

      DB::commit();
      return [
        'origin' => $originStock,
        'destination' => $destinationStock,
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get stock by multiple product IDs across all warehouses
   * Returns stock information grouped by product
   * Includes pricing information per warehouse from ProductWarehouseStock table
   *
   * @param array $productIds Array of product IDs
   * @return array
   */
  public function getStockByProductIds(array $productIds): array
  {
    // Get all stocks for the given product IDs
    $stocks = ProductWarehouseStock::whereIn('product_id', $productIds)
      ->with(['product', 'warehouse', 'currency'])
      ->get();

    // Group by product_id
    $result = [];
    foreach ($productIds as $productId) {
      $productStocks = $stocks->where('product_id', $productId);

      if ($productStocks->isEmpty()) {
        // Product has no stock in any warehouse
        $result[] = [
          'product_id' => $productId,
          'product_name' => null,
          'warehouses' => [],
          'total_quantity' => 0,
          'total_quantity_in_transit' => 0,
          'total_available_quantity' => 0,
        ];
        continue;
      }

      // Get product name from first stock record
      $firstStock = $productStocks->first();

      // Calculate totals
      $totalQuantity = $productStocks->sum('quantity');
      $totalInTransit = $productStocks->sum('quantity_in_transit');
      $totalAvailable = $productStocks->sum('available_quantity');

      // Build warehouses array with pricing per warehouse
      // Uses values already calculated and stored in ProductWarehouseStock table
      $warehouses = [];
      foreach ($productStocks as $stock) {
        // Get pricing from ProductWarehouseStock table (already calculated in addStock method)
        $lastPurchasePrice = (float)($stock->cost_price ?? 0);      // Last unit cost from purchase/adjustment
        $averageCost = (float)($stock->average_cost ?? 0);          // Weighted average cost
        $publicSalePrice = (float)($stock->sale_price ?? 0);        // Public sale price (already calculated)
        $minimumSalePrice = $this->calculateMinimumSalePrice($publicSalePrice);

        // Calculate days without movement
        $daysWithoutMovement = null;
        if ($stock->last_movement_date) {
          $daysWithoutMovement = (int)now()->diffInDays($stock->last_movement_date, true);
        }

        $warehouses[] = [
          'warehouse_id' => $stock->warehouse_id,
          'warehouse_name' => $stock->warehouse?->description,
          'quantity' => (float)$stock->quantity,
          'quantity_in_transit' => (float)$stock->quantity_in_transit,
          'reserved_quantity' => (float)$stock->reserved_quantity,
          'available_quantity' => (float)$stock->available_quantity,
          'minimum_stock' => (float)$stock->minimum_stock,
          'maximum_stock' => (float)$stock->maximum_stock,
          'stock_status' => $stock->stock_status,
          'is_low_stock' => $stock->is_low_stock,
          'is_out_of_stock' => $stock->is_out_of_stock,
          'last_movement_date' => $stock->last_movement_date?->format('Y-m-d H:i:s'),
          'days_without_movement' => $daysWithoutMovement,
          'last_purchase_price' => $lastPurchasePrice,
          'average_cost' => $averageCost,
          'public_sale_price' => $publicSalePrice,
          'minimum_sale_price' => $minimumSalePrice,
          'currency' => $stock->currency,
        ];
      }

      $result[] = [
        'product_id' => $productId,
        'product_name' => $firstStock->product?->name,
        'product_code' => $firstStock->product?->code,
        'warehouses' => $warehouses,
        'total_quantity' => (float)$totalQuantity,
        'total_quantity_in_transit' => (float)$totalInTransit,
        'total_available_quantity' => (float)$totalAvailable,
      ];
    }

    return $result;
  }

  /**
   * Calculate minimum sale price (public sale price - 5%)
   *
   * @param float $publicSalePrice
   * @return float
   */
  private function calculateMinimumSalePrice(float $publicSalePrice): float
  {
    if ($publicSalePrice <= 0) {
      return 0;
    }

    return round($publicSalePrice * (1 - $this->getMinimunDiscount()), 2);
  }

  /**
   * Export inventory to Excel
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportInventory(Request $request)
  {
    $filters = [];

    // Filter by warehouse
    if ($request->filled('warehouse_id')) {
      $filters[] = [
        'column' => 'warehouse_id',
        'operator' => '=',
        'value' => $request->warehouse_id
      ];
    }

    // Filter by stock status
    if ($request->filled('stock_type')) {
      if ($request->stock_type === 'with_stock') {
        $filters[] = [
          'column' => 'with_stock',
          'operator' => '=',
          'value' => true
        ];
      } elseif ($request->stock_type === 'without_stock') {
        $filters[] = [
          'column' => 'without_stock',
          'operator' => '=',
          'value' => true
        ];
      }
    }

    $title = $request->get('title', 'Reporte de Inventario');

    $options = [
      'title' => $title,
      'filters' => $filters,
      'format' => $request->get('format', 'excel'),
    ];

    return $this->exportService->exportToExcel(ProductWarehouseStock::class, $options);
  }

  /**
   * Compare stock between local system and Dynamics
   * Makes a FULL OUTER JOIN logic to show all products from both systems
   *
   * @param int $warehouseId
   * @return array
   * @throws Exception
   */
  public function compareStockWithDynamics(int $warehouseId): array
  {
    try {
      // 1. Get warehouse with dyn_code
      $warehouse = Warehouse::findOrFail($warehouseId);

      if (!$warehouse->dyn_code) {
        throw new Exception("El almacén no tiene código de Dynamics configurado.");
      }

      // 2. Get stock from Dynamics using stored procedure
      $dynamicsStocks = DB::connection('dbtest')->select("EXEC PaStockArticuloAlmacen '{$warehouse->dyn_code}'");

      // 3. Get local stocks with product relation
      $localStocks = ProductWarehouseStock::where('warehouse_id', $warehouseId)
        ->with('product')
        ->get();

      // 4. Make comparison (FULL OUTER JOIN logic)
      $comparison = [];
      $processedDynCodes = [];

      // Process Dynamics stocks first
      foreach ($dynamicsStocks as $dynStock) {
        // Trim the dyn_code from Dynamics to avoid whitespace issues
        $dynCode = trim($dynStock->ArticuloCodigo);

        // Find matching local stock by dyn_code (trimmed)
        $localStock = $localStocks->first(function ($stock) use ($dynCode) {
          return $stock->product && trim($stock->product->dyn_code) === $dynCode;
        });

        $localQuantity = $localStock?->quantity ?? null;
        $dynamicsStock = (float)$dynStock->ArticuloStock;

        // Determine where the product was found
        $foundIn = $localQuantity !== null ? 'AMBOS' : 'SOLO_DYNAMICS';

        // Calculate difference: local_quantity - dynamics_stock
        // Works correctly even with negative dynamics stock: 10 - (-15) = 25
        $difference = null;
        if ($localQuantity !== null) {
          $difference = $localQuantity - $dynamicsStock;
        }

        $comparison[] = [
          'product_dyn_code' => $dynCode,
          'product_code' => $localStock?->product?->code,
          'product_name' => $localStock?->product?->name,
          'warehouse_dynamics' => $dynStock->ArticuloAlmacen,
          // Local system data
          'local_quantity' => $localQuantity,
          'local_available' => $localStock?->available_quantity ?? null,
          'local_in_transit' => $localStock?->quantity_in_transit ?? null,
          'local_reserved' => $localStock?->reserved_quantity ?? null,
          'local_pending_credit_note' => $localStock?->quantity_pending_credit_note ?? null,
          // Dynamics data
          'dynamics_stock' => $dynamicsStock,
          // Comparison
          'difference' => $difference,
          'match' => $difference === 0.0,
          'found_in' => $foundIn,
        ];

        if ($localStock?->product?->dyn_code) {
          $processedDynCodes[] = trim($localStock->product->dyn_code);
        }
      }

      // Process local stocks that are not in Dynamics
      foreach ($localStocks as $localStock) {
        if (!$localStock->product) {
          continue;
        }

        $dynCode = trim($localStock->product->dyn_code);

        // Skip if already processed (compare trimmed)
        if (in_array($dynCode, $processedDynCodes)) {
          continue;
        }

        $comparison[] = [
          'product_dyn_code' => $dynCode,
          'product_code' => $localStock->product->code,
          'product_name' => $localStock->product->name,
          'warehouse_dynamics' => null,
          // Local system data
          'local_quantity' => $localStock->quantity,
          'local_available' => $localStock->available_quantity,
          'local_in_transit' => $localStock->quantity_in_transit,
          'local_reserved' => $localStock->reserved_quantity,
          'local_pending_credit_note' => $localStock->quantity_pending_credit_note,
          // Dynamics data
          'dynamics_stock' => null,
          // Comparison
          'difference' => $localStock->quantity,
          'match' => false,
          'found_in' => 'SOLO_LOCAL',
        ];
      }

      // 5. Sort by product dyn_code
      usort($comparison, function ($a, $b) {
        return strcmp($a['product_dyn_code'] ?? '', $b['product_dyn_code'] ?? '');
      });

      return [
        'warehouse_id' => $warehouseId,
        'warehouse_code' => $warehouse->dyn_code,
        'warehouse_description' => $warehouse->description,
        'comparison_date' => now()->format('Y-m-d H:i:s'),
        'total_products' => count($comparison),
        'matching_products' => count(array_filter($comparison, fn($item) => $item['match'])),
        'products' => $comparison,
      ];
    } catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * Remove stock from credit note and reverse weighted average cost calculation
   * This method "undoes" a purchase as if it never existed
   *
   * IMPORTANT: This method is called when processing credit notes from Dynamics
   * It reverses the cost calculation and recalculates PVP based on remaining purchases
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param float $quantity Quantity to remove (must be positive)
   * @param float $unitCostToReverse Original unit cost of the purchase being reversed (from NC)
   * @param bool $fromPendingCreditNote True: remove from quantity_pending_credit_note, False: remove from quantity directly
   * @return ProductWarehouseStock Updated stock record
   * @throws Exception
   */
  public function removeStockFromCreditNote(
    int   $productId,
    int   $warehouseId,
    float $quantity,
    float $unitCostToReverse,
    bool  $fromPendingCreditNote = true
  ): ProductWarehouseStock
  {
    DB::beginTransaction();
    try {
      // Step 1: Find stock record
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->firstOrFail();

      // Step 2: Validate sufficient quantity to remove
      if ($fromPendingCreditNote) {
        // Validate quantity_pending_credit_note has enough stock
        if ($stock->quantity_pending_credit_note < $quantity) {
          throw new Exception(
            "Cantidad pendiente de nota de crédito insuficiente. " .
            "Producto ID: {$productId}, Almacén ID: {$warehouseId}. " .
            "Pendiente NC: {$stock->quantity_pending_credit_note}, Cantidad NC: {$quantity}. " .
            "ACCIÓN REQUERIDA: Verificar sincronización con Dynamics o ajustar manualmente el stock."
          );
        }
      } else {
        // Validate quantity has enough stock
        if ($stock->quantity < $quantity) {
          throw new Exception(
            "Stock insuficiente para procesar nota de crédito. " .
            "Producto ID: {$productId}, Almacén ID: {$warehouseId}. " .
            "Stock actual: {$stock->quantity}, Cantidad NC: {$quantity}. " .
            "ACCIÓN REQUERIDA: Verificar sincronización con Dynamics."
          );
        }
      }

      // Step 3: Calculate current stock BEFORE removing
      $currentStock = $stock->quantity;
      $currentAverageCost = $stock->average_cost ?? 0;

      // Step 4: Reverse weighted average cost calculation
      // Formula: New_Average_Cost = (Current_Stock × Current_Avg_Cost - Quantity_Returned × Unit_Cost_NC) / (Current_Stock - Quantity_Returned)
      // This effectively removes the contribution of the returned items from the average cost
      if ($currentStock > $quantity && $currentAverageCost > 0) {
        $numerator = ($currentStock * $currentAverageCost) - ($quantity * $unitCostToReverse);
        $denominator = $currentStock - $quantity;

        if ($denominator > 0) {
          $newAverageCost = round($numerator / $denominator, 2);
          $stock->average_cost = $newAverageCost;
        } else {
          // If we're removing all stock, set average cost to 0
          $stock->average_cost = 0;
        }
      } else {
        // If removing all stock or average cost is 0, reset to 0
        $stock->average_cost = 0;
      }

      // Step 5: Update cost_price to the previous purchase (not the one being reversed)
      // Find the most recent purchase BEFORE the credit note that is NOT being reversed
      $previousPurchaseMovement = InventoryMovement::whereHas('details', function ($q) use ($productId) {
        $q->where('product_id', $productId)
          ->where('unit_cost', '>', 0);
      })
        ->where('warehouse_id', $warehouseId)
        ->where('movement_type', InventoryMovement::TYPE_PURCHASE_RECEPTION)
        ->where('status', InventoryMovement::STATUS_APPROVED)
        ->with(['details' => function ($q) use ($productId) {
          $q->where('product_id', $productId);
        }])
        ->orderBy('movement_date', 'desc')
        ->orderBy('id', 'desc')
        ->skip(1) // Skip the most recent (which is the one being reversed)
        ->first();

      if ($previousPurchaseMovement && $previousPurchaseMovement->details->isNotEmpty()) {
        $previousDetail = $previousPurchaseMovement->details->first();
        $stock->cost_price = $previousDetail->unit_cost ?? 0;
      } else {
        // No previous purchase found, set to 0
        $stock->cost_price = 0;
      }

      // Step 6: Recalculate sale_price (PVP) based on new average cost
      if ($stock->average_cost > 0) {
        $profitMargin = $this->getProfitMargin();
        $freightCommission = $this->getFreightCommission();

        if (ProductWarehouseStock::PRICE_CALCULATION_METHOD === 1) {
          // Method 1: PVP = Cost / (1 - margin) * (1 + tax)
          $stock->sale_price = round(
            ($stock->average_cost / (1 - $profitMargin)) * (1 + $freightCommission),
            2
          );
        } else {
          // Method 2 (default): PVP = Cost / (1 - (margin + tax))
          $stock->sale_price = round(
            $stock->average_cost / (1 - ($profitMargin + $freightCommission)),
            2
          );
        }
      } else {
        // If average cost is 0, set sale price to 0
        $stock->sale_price = 0;
      }

      // Step 7: Remove quantity from appropriate field
      if ($fromPendingCreditNote) {
        // Subtract from quantity_pending_credit_note (items awaiting NC)
        $stock->quantity_pending_credit_note -= $quantity;
        if ($stock->quantity_pending_credit_note < 0) {
          $stock->quantity_pending_credit_note = 0;
        }
      }

      // Always subtract from physical quantity
      $stock->quantity -= $quantity;
      if ($stock->quantity < 0) {
        $stock->quantity = 0;
      }

      // Step 8: Update last movement date
      $stock->last_movement_date = now();

      // Step 9: Recalculate available quantity
      // Formula: available_quantity = quantity - reserved_quantity
      $stock->updateAvailableQuantity();

      // Step 10: Save changes
      $stock->save();

      DB::commit();
      return $stock->fresh();
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get stock movement history for a product in a warehouse
   * Reconstructs the entire history showing how stock and average cost evolved
   *
   * Shows movements relevant for average cost analysis:
   * - PURCHASE_RECEPTION: Purchases that affect average cost
   * - RETURN_OUT: Returns to supplier (if enabled via INCLUDE_RETURN_OUT_IN_HISTORY)
   *
   * Configuration constants:
   * - INCLUDE_RETURN_OUT_IN_HISTORY: Include supplier returns in history
   * - USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST: Use physical received qty vs invoiced qty
   *
   * @param int $productId
   * @param int $warehouseId
   * @return array
   * @throws Exception
   */
  public function getStockMovementHistory(int $productId, int $warehouseId): array
  {
    try {
      // Get the stock record
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->with(['product', 'warehouse'])
        ->firstOrFail();

      // Build query for PURCHASE_RECEPTION movements
      $query = InventoryMovement::whereHas('details', function ($q) use ($productId) {
        $q->where('product_id', $productId);
      })
        ->where('warehouse_id', $warehouseId)
        ->where('status', InventoryMovement::STATUS_APPROVED);

      // Build movement type filter
      $movementTypes = [InventoryMovement::TYPE_PURCHASE_RECEPTION];

      // Include RETURN_OUT if configuration is enabled
      if (self::INCLUDE_RETURN_OUT_IN_HISTORY === 1) {
        $movementTypes[] = InventoryMovement::TYPE_RETURN_OUT;
      }

      $query->whereIn('movement_type', $movementTypes);

      // Apply specific filters per movement type
      $query->where(function ($q) use ($productId) {
        // PURCHASE_RECEPTION: must have APPROVED PurchaseReception
        $q->where(function ($subQuery) {
          $subQuery->where('movement_type', InventoryMovement::TYPE_PURCHASE_RECEPTION)
            ->whereHasMorph('reference', [PurchaseReception::class], function ($morphQuery) {
              $morphQuery->where('status', 'APPROVED');
            });
        });

        // RETURN_OUT: must have APPROVED SupplierCreditNote with active PurchaseOrder
        if (self::INCLUDE_RETURN_OUT_IN_HISTORY === 1) {
          $q->orWhere(function ($subQuery) {
            $subQuery->where('movement_type', InventoryMovement::TYPE_RETURN_OUT)
              ->whereHasMorph('reference', [SupplierCreditNote::class], function ($morphQuery) {
                $morphQuery->where('status', SupplierCreditNote::STATUS_APPROVED)
                  ->whereNotNull('purchase_order_id')
                  ->whereHas('purchaseOrder', function ($poQuery) {
                    $poQuery->where('status', true); // status = 1 (active)
                  });
              });
          });
        }
      });

      $movements = $query
        ->with([
          'details' => function ($q) use ($productId) {
            $q->where('product_id', $productId);
          },
          'currency',
          'reference' // Load the reference (PurchaseReception or SupplierCreditNote)
        ])
        ->orderBy('movement_date', 'asc')
        ->orderBy('id', 'asc')
        ->get();

      // Reconstruct history step by step
      $history = [];
      $runningStock = 0;
      $runningAverageCost = 0;

      // Process each movement in registration order (no initial state)
      foreach ($movements as $movement) {
        if ($movement->details->isEmpty()) continue;

        $detail = $movement->details->first();
        $unitCostOriginal = (float)($detail->unit_cost ?? 0);
        $isInbound = $movement->is_inbound;

        // Determine quantity to use based on movement type and configuration
        $quantity = abs((float)$detail->quantity);
        $quantityForAverageCost = $quantity; // Default: use same quantity

        // For PURCHASE_RECEPTION: check if we should use quantity_received instead
        if ($movement->movement_type === InventoryMovement::TYPE_PURCHASE_RECEPTION &&
          self::USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST === true &&
          $movement->reference instanceof PurchaseReception) {

          // Get the PurchaseReceptionDetail for this product
          $receptionDetail = PurchaseReceptionDetail::where('purchase_reception_id', $movement->reference->id)
            ->where('product_id', $productId)
            ->first();

          if ($receptionDetail) {
            // Use quantity_received (physical received in good condition) for average cost calculation
            $quantityForAverageCost = (float)$receptionDetail->quantity_received;
          }
        }

        // Convert unit cost to PEN (base currency) just like addStock() does
        $unitCostInPEN = $this->convertToBaseCurrency(
          $unitCostOriginal,
          $movement->currency_id,
          $movement->exchange_rate
        );

        // Calculate stock after this movement
        if ($isInbound) {
          // INBOUND: Add to stock
          $stockBeforeMovement = $runningStock;

          // For PURCHASE_RECEPTION with USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true:
          // - Use quantity_received (8) for both stock and average cost calculation
          // For all other cases:
          // - Use invoiced quantity (10) for both stock and average cost calculation
          $runningStock += $quantityForAverageCost;

          // Calculate average cost ONLY if unit cost is provided (> 0)
          // Use quantityForAverageCost (could be quantity_received or full quantity)
          if ($unitCostInPEN > 0) {
            // Apply weighted average formula using cost in PEN
            if ($stockBeforeMovement + $quantityForAverageCost > 0) {
              $runningAverageCost = (($stockBeforeMovement * $runningAverageCost) + ($quantityForAverageCost * $unitCostInPEN)) / ($stockBeforeMovement + $quantityForAverageCost);
              $runningAverageCost = round($runningAverageCost, 2);
            } else {
              $runningAverageCost = $unitCostInPEN;
            }
          }
          // If unitCostInPEN = 0, stock increases but average cost stays the same
        } else {
          // OUTBOUND: Remove from stock (doesn't affect average cost)
          $runningStock -= $quantity;
          if ($runningStock < 0) {
            $runningStock = 0; // Safety check
          }
        }

        // Determine movement type label
        $movementTypeLabel = match ($movement->movement_type) {
          InventoryMovement::TYPE_PURCHASE_RECEPTION => 'Recepción de Compra',
          InventoryMovement::TYPE_RETURN_OUT => 'Devolución a Proveedor',
          InventoryMovement::TYPE_ADJUSTMENT_IN => 'Ajuste de Entrada',
          InventoryMovement::TYPE_ADJUSTMENT_OUT => 'Ajuste de Salida',
          InventoryMovement::TYPE_TRANSFER_IN => 'Transferencia Entrada',
          InventoryMovement::TYPE_TRANSFER_OUT => 'Transferencia Salida',
          InventoryMovement::TYPE_SALE => 'Venta',
          default => $movement->movement_type,
        };

        $history[] = [
          'movement_id' => $movement->id,
          'movement_date' => $movement->movement_date->format('Y-m-d'),
          'movement_number' => $movement->movement_number,
          'movement_type' => $movement->movement_type,
          'movement_type_label' => $movementTypeLabel,
          'is_inbound' => $isInbound,
          'quantity' => $quantityForAverageCost, // Muestra la cantidad usada para cálculo (8 o 10 según configuración)
          'unit_cost' => $unitCostOriginal, // Costo en moneda original
          'unit_cost_in_pen' => $unitCostInPEN, // Costo convertido a PEN
          'total_cost' => $isInbound ? round($quantityForAverageCost * $unitCostInPEN, 2) : 0,
          'stock_after_movement' => $runningStock,
          'average_cost_after_movement' => $runningAverageCost,
          'currency' => $movement->currency?->code ?? 'PEN',
          'exchange_rate' => (float)($movement->exchange_rate ?? 0),
          'created_at' => $movement->created_at->format('Y-m-d H:i:s'),
        ];
      }

      // Verify final values match database
      $finalStockMatches = abs($runningStock - (float)$stock->quantity) < 0.01;
      $finalAverageCostMatches = abs($runningAverageCost - (float)$stock->average_cost) < 0.01;

      return [
        'success' => true,
        'product_id' => $productId,
        'product_code' => $stock->product?->code,
        'product_name' => $stock->product?->name,
        'warehouse_id' => $warehouseId,
        'warehouse_name' => $stock->warehouse?->description,
        'current_stock_database' => (float)$stock->quantity,
        'current_average_cost_database' => (float)$stock->average_cost,
        'calculated_final_stock' => $runningStock,
        'calculated_final_average_cost' => $runningAverageCost,
        'stock_matches' => $finalStockMatches,
        'average_cost_matches' => $finalAverageCostMatches,
        'total_movements' => count($history),
        'history' => $history,
        'generated_at' => now()->format('Y-m-d H:i:s'),
      ];
    } catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * Get detailed price calculation explanation for a product in a warehouse
   * Shows step-by-step how the PVP (public sale price) is calculated
   *
   * @param int $productId
   * @param int $warehouseId
   * @return array
   * @throws Exception
   */
  public function getPriceCalculationDetails(int $productId, int $warehouseId): array
  {
    try {
      // Get the stock record
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->with(['product', 'warehouse', 'currency'])
        ->firstOrFail();

      // Get configuration values
      $profitMargin = $this->getProfitMargin();
      $freightCommission = $this->getFreightCommission();
      $minimumDiscount = $this->getMinimunDiscount();
      $calculationMethod = ProductWarehouseStock::PRICE_CALCULATION_METHOD;

      // Get last purchase movement for this product and warehouse
      // IMPORTANT: Order by created_at (not movement_date) to match the cost_price stored
      // cost_price is updated with each addStock() call in registration order, not chronological order
      $lastPurchaseMovement = InventoryMovement::whereHas('details', function ($q) use ($productId) {
        $q->where('product_id', $productId);
      })
        ->where('warehouse_id', $warehouseId)
        ->where('movement_type', InventoryMovement::TYPE_PURCHASE_RECEPTION)
        ->where('status', InventoryMovement::STATUS_APPROVED)
        ->with(['details' => function ($q) use ($productId) {
          $q->where('product_id', $productId);
        }, 'currency'])
        ->orderBy('created_at', 'desc')
        ->orderBy('id', 'desc')
        ->first();

      // Calculate prices
      $averageCost = (float)$stock->average_cost;
      $lastPurchasePrice = (float)$stock->cost_price;
      $publicSalePrice = (float)$stock->sale_price;
      $minimumSalePrice = $this->calculateMinimumSalePrice($publicSalePrice);

      // Build detailed calculation steps
      $calculationSteps = [];

      // Step 1: Last Purchase Information
      if ($lastPurchaseMovement && $lastPurchaseMovement->details->isNotEmpty()) {
        $purchaseDetail = $lastPurchaseMovement->details->first();
        $purchaseUnitCost = (float)$purchaseDetail->unit_cost;
        $purchaseQuantity = (float)$purchaseDetail->quantity;
        $purchaseCurrency = $lastPurchaseMovement->currency?->code ?? 'PEN';
        $exchangeRate = (float)$lastPurchaseMovement->exchange_rate;

        // Calculate conversion if needed
        $unitCostInPenCalculated = $exchangeRate > 0 && $purchaseCurrency !== 'PEN'
          ? round($purchaseUnitCost * $exchangeRate, 2)
          : $purchaseUnitCost;

        $calculationSteps[] = [
          'step' => 1,
          'title' => 'Información de la Última Compra',
          'description' => 'Datos de la última recepción de compra registrada para este producto en este almacén.',
          'data' => [
            'movement_number' => $lastPurchaseMovement->movement_number,
            'movement_date' => $lastPurchaseMovement->movement_date->format('Y-m-d H:i:s'),
            'quantity_purchased' => $purchaseQuantity,
            'unit_cost_original' => $purchaseUnitCost,
            'original_currency' => $purchaseCurrency,
            'exchange_rate' => $exchangeRate > 0 ? $exchangeRate : null,
            'unit_cost_in_pen' => $lastPurchasePrice,
          ],
          'development' => [
            'unit_cost_original' => $purchaseUnitCost,
            'exchange_rate' => $exchangeRate,
            'currency' => $purchaseCurrency,
            'conversion_needed' => $exchangeRate > 0 && $purchaseCurrency !== 'PEN',
            'calculation' => $exchangeRate > 0 && $purchaseCurrency !== 'PEN'
              ? "$purchaseUnitCost × $exchangeRate = $unitCostInPenCalculated"
              : "No se requiere conversión, ya está en PEN",
            'unit_cost_in_pen_calculated' => $unitCostInPenCalculated,
            'unit_cost_in_pen_stored' => $lastPurchasePrice,
            'matches' => abs($unitCostInPenCalculated - $lastPurchasePrice) < 0.01,
          ],
          'message' => $exchangeRate > 0 && $purchaseCurrency !== 'PEN'
            ? "Se compró una cantidad de $purchaseQuantity unidades a un costo unitario de $purchaseCurrency $purchaseUnitCost, que convertido a PEN con tipo de cambio $exchangeRate resulta en PEN $lastPurchasePrice."
            : "Se compró una cantidad de $purchaseQuantity unidades a un costo unitario de PEN $lastPurchasePrice."
        ];
      } else {
        $calculationSteps[] = [
          'step' => 1,
          'title' => 'Información de la Última Compra',
          'description' => 'No se encontró historial de compras para este producto en este almacén.',
          'data' => [
            'last_purchase_price' => $lastPurchasePrice > 0 ? $lastPurchasePrice : null,
          ],
          'development' => [
            'last_purchase_price' => $lastPurchasePrice,
            'has_purchase_history' => false,
          ],
          'message' => $lastPurchasePrice > 0
            ? "El último precio de compra registrado es PEN $lastPurchasePrice, pero no se encontró el movimiento de inventario asociado."
            : "No hay historial de compras registradas para este producto en este almacén."
        ];
      }

      // Step 2: Weighted Average Cost Calculation
      $currentStock = (float)$stock->quantity;

      // Calculate stock before last purchase (if exists)
      $stockBeforeLastPurchase = null;
      $lastPurchaseQuantity = null;
      $previousAverageCost = null;

      if ($lastPurchaseMovement && $lastPurchaseMovement->details->isNotEmpty()) {
        $purchaseDetail = $lastPurchaseMovement->details->first();
        $lastPurchaseQuantity = (float)$purchaseDetail->quantity;

        // Calculate the stock at the time IMMEDIATELY AFTER the last purchase
        // by reversing all movements that occurred AFTER the last purchase (by registration order)
        $stockAfterLastPurchase = $currentStock;

        // Get all approved movements for this product/warehouse that were REGISTERED AFTER the last purchase
        // IMPORTANT: Use created_at to match registration order (same as addStock() logic)
        $movementsAfterPurchase = InventoryMovement::whereHas('details', function ($q) use ($productId) {
          $q->where('product_id', $productId);
        })
          ->where('warehouse_id', $warehouseId)
          ->where('status', InventoryMovement::STATUS_APPROVED)
          ->where(function ($q) use ($lastPurchaseMovement) {
            $q->where('created_at', '>', $lastPurchaseMovement->created_at)
              ->orWhere(function ($q2) use ($lastPurchaseMovement) {
                $q2->where('created_at', '=', $lastPurchaseMovement->created_at)
                  ->where('id', '>', $lastPurchaseMovement->id);
              });
          })
          ->with(['details' => function ($q) use ($productId) {
            $q->where('product_id', $productId);
          }])
          ->orderBy('created_at', 'desc')
          ->orderBy('id', 'desc')
          ->get();

        // Reverse each movement to get stock at time of purchase
        foreach ($movementsAfterPurchase as $movement) {
          if ($movement->details->isEmpty()) continue;

          $detail = $movement->details->first();
          $quantity = abs((float)$detail->quantity);

          // Reverse the movement:
          // - If it was inbound (added stock), subtract it
          // - If it was outbound (removed stock), add it back
          if ($movement->is_inbound) {
            $stockAfterLastPurchase -= $quantity;
          } else {
            $stockAfterLastPurchase += $quantity;
          }
        }

        // Now calculate stock BEFORE the last purchase
        $stockBeforeLastPurchase = $stockAfterLastPurchase - $lastPurchaseQuantity;

        // Calculate what the previous average cost was (before last purchase)
        // From formula: newAverage = (oldStock × oldAvg + newQty × newCost) / (oldStock + newQty)
        // Solve for oldAvg: oldAvg = (newAverage × (oldStock + newQty) - newQty × newCost) / oldStock
        // We have: averageCost (new), stockBeforeLastPurchase (old), lastPurchaseQuantity (new), lastPurchasePrice (new cost)
        if ($stockBeforeLastPurchase > 0) {
          $previousAverageCost = round(
            ($averageCost * $stockAfterLastPurchase - $lastPurchaseQuantity * $lastPurchasePrice) / $stockBeforeLastPurchase,
            2
          );
        } else {
          // If stock before was 0, this was the first purchase, so no previous average cost
          $previousAverageCost = 0;
        }
      }

      $step2Data = [
        'current_stock' => $currentStock,
        'average_cost' => $averageCost,
        'last_purchase_price' => $lastPurchasePrice,
      ];

      $step2Development = [
        'current_stock' => $currentStock,
        'current_average_cost' => $averageCost,
        'last_purchase_price' => $lastPurchasePrice,
        'explanation' => 'El costo promedio se actualiza con cada compra utilizando el método de promedio ponderado',
        'note' => 'Este valor ya está almacenado en la base de datos. Se calcula automáticamente al registrar una compra en el método addStock()',
      ];

      $step2Message = $averageCost > 0
        ? "El costo promedio ponderado actual es PEN $averageCost. Este valor se actualiza cada vez que se recibe una nueva compra y se utiliza como base para calcular el precio de venta."
        : "No hay costo promedio calculado. Esto ocurre cuando no se han registrado compras con costo unitario para este producto.";

      // Add detailed calculation if last purchase exists
      $step2CalculationDetails = '';
      if ($stockBeforeLastPurchase !== null && $lastPurchaseQuantity !== null && $previousAverageCost !== null) {
        $step2Data['stock_before_last_purchase'] = $stockBeforeLastPurchase;
        $step2Data['last_purchase_quantity'] = $lastPurchaseQuantity;
        $step2Data['previous_average_cost'] = $previousAverageCost;

        $step2Development['stock_before_last_purchase'] = $stockBeforeLastPurchase;
        $step2Development['previous_average_cost'] = $previousAverageCost;
        $step2Development['last_purchase_quantity'] = $lastPurchaseQuantity;
        $step2Development['last_purchase_unit_cost'] = $lastPurchasePrice;
        $step2Development['stock_after_purchase'] = $stockAfterLastPurchase;
        $step2Development['average_cost_after_purchase'] = $averageCost;
        $step2Development['calculation_explanation'] = "En el método addStock(), se usa el stock ANTES de la compra ($stockBeforeLastPurchase unidades) y el costo promedio ANTERIOR ($previousAverageCost) para calcular el nuevo promedio ponderado.";
        $step2Development['formula_applied'] = "($stockBeforeLastPurchase × $previousAverageCost + $lastPurchaseQuantity × $lastPurchasePrice) / ($stockBeforeLastPurchase + $lastPurchaseQuantity) = $averageCost";

        // Build detailed step-by-step calculation
        $numeratorPart1 = $stockBeforeLastPurchase * $previousAverageCost;
        $numeratorPart2 = $lastPurchaseQuantity * $lastPurchasePrice;
        $numeratorTotal = $numeratorPart1 + $numeratorPart2;
        $denominatorTotal = $stockBeforeLastPurchase + $lastPurchaseQuantity;

        $step2CalculationDetails = "Costo_Promedio = ($stockBeforeLastPurchase × $previousAverageCost + $lastPurchaseQuantity × $lastPurchasePrice) / ($stockBeforeLastPurchase + $lastPurchaseQuantity)\n" .
          "Costo_Promedio = ($numeratorPart1 + $numeratorPart2) / $denominatorTotal\n" .
          "Costo_Promedio = $numeratorTotal / $denominatorTotal\n" .
          "Costo_Promedio = $averageCost";

        // Verify calculation
        if ($stockBeforeLastPurchase > 0 || $lastPurchaseQuantity > 0) {
          $calculatedAverage = round(
            ($stockBeforeLastPurchase * $previousAverageCost + $lastPurchaseQuantity * $lastPurchasePrice) / ($stockBeforeLastPurchase + $lastPurchaseQuantity),
            2
          );
          $step2Development['calculated_average_verification'] = $calculatedAverage;
          $step2Development['matches_stored_average'] = abs($calculatedAverage - $averageCost) < 0.01;
        }

        $step2Message = "Antes de la última compra había $stockBeforeLastPurchase unidades con costo promedio de PEN $previousAverageCost. Se compraron $lastPurchaseQuantity unidades a PEN $lastPurchasePrice, llegando al stock después de la compra de $stockAfterLastPurchase unidades. El nuevo costo promedio ponderado es PEN $averageCost.";
      }

      $calculationSteps[] = [
        'step' => 2,
        'title' => 'Cálculo del Costo Promedio Ponderado',
        'description' => 'El costo promedio se calcula con la fórmula: (Stock_Antes_Compra × Costo_Promedio_Anterior + Cantidad_Comprada × Costo_Unitario_Compra) / (Stock_Antes_Compra + Cantidad_Comprada)',
        'data' => $step2Data,
        'development' => $step2Development,
        'formula' => 'Costo_Promedio = (Stock_Antes_Compra × Costo_Promedio_Anterior + Cantidad_Comprada × Costo_Unitario_Compra) / (Stock_Antes_Compra + Cantidad_Comprada)',
        'calculation_details' => $step2CalculationDetails,
        'message' => $step2Message
      ];

      // Step 3: Configuration Values
      $profitMarginPercent = $profitMargin * 100;
      $freightCommissionPercent = $freightCommission * 100;

      $calculationSteps[] = [
        'step' => 3,
        'title' => 'Valores de Configuración',
        'description' => 'Porcentajes configurados en el sistema para el cálculo del precio de venta.',
        'data' => [
          'profit_margin' => $profitMargin,
          'profit_margin_percent' => "$profitMarginPercent%",
          'freight_commission' => $freightCommission,
          'freight_commission_percent' => "$freightCommissionPercent%",
          'calculation_method' => $calculationMethod,
        ],
        'development' => [
          'profit_margin' => $profitMargin,
          'freight_commission' => $freightCommission,
          'calculation_method' => $calculationMethod,
        ],
        'message' => "Margen de ganancia configurado: $profitMarginPercent%. Comisión de flete configurado: $freightCommissionPercent%. Método de cálculo: $calculationMethod."
      ];

      // Step 4: PVP Calculation
      $calculatedPVP = 0;
      $formulaExplanation = '';
      $calculationDetails = '';
      $basePrice = 0;
      $divisor = 0;
      $step4Development = [];

      if ($averageCost > 0) {
        if ($calculationMethod === 1) {
          // Method 1: PVP = Costo / (1 - margen) * (1 + impuesto)
          $basePrice = $averageCost / (1 - $profitMargin);
          $calculatedPVP = round($basePrice * (1 + $freightCommission), 2);
          $formulaExplanation = 'PVP = Costo_Promedio / (1 - Margen_Ganancia) × (1 + Comisión_Flete)';
          $calculationDetails = "PVP = $averageCost / (1 - $profitMargin) × (1 + $freightCommission)\n" .
            "PVP = $averageCost / " . (1 - $profitMargin) . " × " . (1 + $freightCommission) . "\n" .
            "PVP = " . round($basePrice, 2) . " × " . (1 + $freightCommission) . "\n" .
            "PVP = $calculatedPVP";

          $step4Development = [
            'average_cost' => $averageCost,
            'profit_margin' => $profitMargin,
            'freight_commission' => $freightCommission,
            'one_minus_profit_margin' => 1 - $profitMargin,
            'base_price' => round($basePrice, 2),
            'one_plus_freight_commission' => 1 + $freightCommission,
            'calculated_pvp' => $calculatedPVP,
            'stored_sale_price' => $publicSalePrice,
            'method' => 1,
          ];
        } else {
          // Method 2: PVP = Costo / (1 - (margen + impuesto))
          $divisor = 1 - ($profitMargin + $freightCommission);
          $calculatedPVP = round($averageCost / $divisor, 2);
          $formulaExplanation = 'PVP = Costo_Promedio / (1 - (Margen_Ganancia + Comisión_Flete))';
          $calculationDetails = "PVP = $averageCost / (1 - ($profitMargin + $freightCommission))\n" .
            "PVP = $averageCost / (1 - " . ($profitMargin + $freightCommission) . ")\n" .
            "PVP = $averageCost / $divisor\n" .
            "PVP = $calculatedPVP";

          $step4Development = [
            'average_cost' => $averageCost,
            'profit_margin' => $profitMargin,
            'freight_commission' => $freightCommission,
            'sum_profit_and_freight' => $profitMargin + $freightCommission,
            'one_minus_sum' => $divisor,
            'calculated_pvp' => $calculatedPVP,
            'stored_sale_price' => $publicSalePrice,
            'method' => 2,
          ];
        }
      } else {
        $step4Development = [
          'average_cost' => $averageCost,
          'can_calculate' => false,
          'reason' => 'Costo promedio es 0',
        ];
      }

      $calculationSteps[] = [
        'step' => 4,
        'title' => 'Cálculo del Precio de Venta al Público (PVP)',
        'description' => "Aplicación de la fórmula del método $calculationMethod para calcular el precio de venta.",
        'data' => [
          'average_cost' => $averageCost,
          'calculated_pvp' => $calculatedPVP,
          'stored_sale_price' => $publicSalePrice,
          'matches' => abs($calculatedPVP - $publicSalePrice) < 0.01,
        ],
        'development' => $step4Development,
        'formula' => $formulaExplanation,
        'calculation_details' => $calculationDetails,
        'message' => $averageCost > 0
          ? "Aplicando la fórmula del método $calculationMethod, el PVP calculado es PEN $calculatedPVP. El PVP almacenado actualmente en la base de datos es PEN $publicSalePrice."
          : "No se puede calcular el PVP porque el costo promedio es 0. Esto ocurre cuando no se han registrado compras con costo unitario."
      ];

      // Step 5: Minimum Sale Price
      $minimumDiscountPercent = $minimumDiscount * 100;
      $oneMinusDiscount = 1 - $minimumDiscount;

      $calculationSteps[] = [
        'step' => 5,
        'title' => 'Precio de Venta Mínimo',
        'description' => 'El precio mínimo se calcula aplicando el descuento mínimo permitido al PVP.',
        'data' => [
          'public_sale_price' => $publicSalePrice,
          'minimum_discount' => $minimumDiscount,
          'minimum_discount_percent' => "$minimumDiscountPercent%",
          'minimum_sale_price' => $minimumSalePrice,
        ],
        'development' => [
          'public_sale_price' => $publicSalePrice,
          'minimum_discount' => $minimumDiscount,
          'one_minus_discount' => $oneMinusDiscount,
          'minimum_sale_price' => $minimumSalePrice,
        ],
        'formula' => 'Precio_Mínimo = PVP × (1 - Descuento_Mínimo)',
        'calculation_details' => "Precio_Mínimo = $publicSalePrice × (1 - $minimumDiscount)\n" .
          "Precio_Mínimo = $publicSalePrice × $oneMinusDiscount\n" .
          "Precio_Mínimo = $minimumSalePrice",
        'message' => $publicSalePrice > 0
          ? "El precio de venta mínimo permitido es PEN $minimumSalePrice, que corresponde al PVP con un descuento máximo del $minimumDiscountPercent%."
          : "No se puede calcular el precio mínimo porque no hay un precio de venta público establecido."
      ];

      // Build summary
      $summary = [
        'product_id' => $productId,
        'product_code' => $stock->product?->code,
        'product_name' => $stock->product?->name,
        'warehouse_id' => $warehouseId,
        'warehouse_name' => $stock->warehouse?->description,
        'currency' => $stock->currency?->code ?? 'PEN',
        'current_stock' => (float)$stock->quantity,
        'prices' => [
          'last_purchase_price' => $lastPurchasePrice,
          'average_cost' => $averageCost,
          'public_sale_price' => $publicSalePrice,
          'calculated_pvp' => $calculatedPVP,
          'minimum_sale_price' => $minimumSalePrice,
          'price_matches' => abs($calculatedPVP - $publicSalePrice) < 0.01,
        ],
        'configuration' => [
          'profit_margin' => $profitMargin,
          'profit_margin_percent' => "{$profitMarginPercent}%",
          'freight_commission' => $freightCommission,
          'freight_commission_percent' => "{$freightCommissionPercent}%",
          'minimum_discount' => $minimumDiscount,
          'minimum_discount_percent' => "{$minimumDiscountPercent}%",
          'calculation_method' => $calculationMethod,
        ],
      ];

      return [
        'success' => true,
        'summary' => $summary,
        'calculation_steps' => $calculationSteps,
        'generated_at' => now()->format('Y-m-d H:i:s'),
      ];
    } catch (Exception $e) {
      throw $e;
    }
  }
}
