<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\ProductWarehouseStockResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\GeneralMaster;
use Illuminate\Http\Request;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductWarehouseStockService extends BaseService
{
  // Método de cálculo de precio de venta:
  // 1: PVP = Costo / (1 - margen) * (1 + impuesto)
  // 2: PVP = Costo / (1 - (margen + impuesto))
  private const PRICE_CALCULATION_METHOD = 2;

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

        if (self::PRICE_CALCULATION_METHOD === 1) {
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
}
