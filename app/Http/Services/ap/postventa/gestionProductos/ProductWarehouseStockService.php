<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\ProductWarehouseStockResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Jobs\RecalculateProductCostJob;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\compras\SupplierCreditNote;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\WeightedAverageCostHistory;
use App\Models\GeneralMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // NOTA: Los precios de venta (sale_price y sale_price_min) se calculan
        // centralizadamente en rebuildWeightedAverageCostHistory()
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
      $productsToRecalculate = []; // Track products that need price recalculation

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

          // Mark this product-warehouse for price recalculation
          $productsToRecalculate[] = [
            'product_id' => $productId,
            'warehouse_id' => $movement->warehouse_id,
          ];
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

          // Mark destination warehouse for price recalculation too
          $productsToRecalculate[] = [
            'product_id' => $productId,
            'warehouse_id' => $movement->warehouse_destination_id,
          ];
        }
      }

      DB::commit();

      // AFTER successful stock update, recalculate prices for all affected products
      // This is done OUTSIDE the transaction to avoid blocking
      $this->recalculatePricesAfterMovement($productsToRecalculate, $movement);

      return $updatedStocks;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Recalcula los precios de venta después de un movimiento de inventario
   * Usa estrategia sync/async según la fecha del movimiento
   *
   * @param array $productsToRecalculate Array de ['product_id' => X, 'warehouse_id' => Y]
   * @param InventoryMovement $movement Movimiento que originó el cambio
   * @return void
   */
  protected function recalculatePricesAfterMovement(array $productsToRecalculate, InventoryMovement $movement): void
  {
    // Remove duplicates (same product-warehouse combination)
    $uniqueProducts = collect($productsToRecalculate)->unique(function ($item) {
      return $item['product_id'] . '-' . $item['warehouse_id'];
    });

    foreach ($uniqueProducts as $item) {
      $productId = $item['product_id'];
      $warehouseId = $item['warehouse_id'];

      // Estrategia: Si el movimiento es reciente (últimos 7 días), usar sync
      // Si es antiguo (> 7 días), usar Job asíncrono
      $movementDate = Carbon::parse($movement->movement_date);
      $daysDifference = now()->diffInDays($movementDate, true); // absolute = true para valor positivo
      $isRecent = $daysDifference <= 7;

      if ($isRecent) {
        // SYNC: Movimiento reciente, recalcular de inmediato
        try {
          $this->rebuildWeightedAverageCostHistory($productId, $warehouseId, $movementDate);
        } catch (\Exception $e) {
          // Log error but don't fail the main operation
          Log::error('Error al recalcular precios sincronicamente', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_id' => $movement->id,
            'error' => $e->getMessage(),
          ]);
        }
      } else {
        // ASYNC: Movimiento antiguo (retroactivo), usar Job
        RecalculateProductCostJob::dispatch($productId, $warehouseId, $movementDate);
      }
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

      // NOTA: Los precios de venta (sale_price y sale_price_min) se recalculan
      // después mediante rebuildWeightedAverageCostHistory() en el Job correspondiente

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
   * Shows all movements that affect stock:
   *
   * INBOUND (Entradas) - Afectan costo promedio si unit_cost > 0:
   * - PURCHASE_RECEPTION: Recepciones de compra
   * - ADJUSTMENT_IN: Ajustes de entrada (con o sin costo)
   * - TRANSFER_IN: Transferencias de entrada (con o sin costo)
   *
   * OUTBOUND (Salidas) - NO afectan costo promedio, solo reducen stock:
   * - SALE: Ventas
   * - ADJUSTMENT_OUT: Ajustes de salida
   * - TRANSFER_OUT: Transferencias de salida
   * - RETURN_OUT: Devoluciones a proveedor (solo si INCLUDE_RETURN_OUT_IN_HISTORY = 1)
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
      $movementTypes = [
        // INBOUND (Entradas) - Pueden afectar costo promedio si unit_cost > 0
        InventoryMovement::TYPE_PURCHASE_RECEPTION,
        InventoryMovement::TYPE_ADJUSTMENT_IN,
        InventoryMovement::TYPE_TRANSFER_IN,

        // OUTBOUND (Salidas) - NO afectan costo promedio, solo reducen stock
        InventoryMovement::TYPE_SALE,
        InventoryMovement::TYPE_ADJUSTMENT_OUT,
        InventoryMovement::TYPE_TRANSFER_OUT,
      ];

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

        // SALE: No additional filter needed, already filtered by status APPROVED above
        // Reference can be ApOrderQuotations or ApWorkOrder
        $q->orWhere('movement_type', InventoryMovement::TYPE_SALE);

        // ADJUSTMENT_IN: No additional filter needed, already filtered by status APPROVED above
        $q->orWhere('movement_type', InventoryMovement::TYPE_ADJUSTMENT_IN);

        // ADJUSTMENT_OUT: No additional filter needed, already filtered by status APPROVED above
        $q->orWhere('movement_type', InventoryMovement::TYPE_ADJUSTMENT_OUT);

        // TRANSFER_IN: No additional filter needed, already filtered by status APPROVED above
        $q->orWhere('movement_type', InventoryMovement::TYPE_TRANSFER_IN);

        // TRANSFER_OUT: No additional filter needed, already filtered by status APPROVED above
        $q->orWhere('movement_type', InventoryMovement::TYPE_TRANSFER_OUT);

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

        // CRITICAL: Separate quantity for stock vs average cost calculation
        // - $quantityForStock: Physical quantity that enters/exits warehouse (from InventoryMovement - source of truth)
        // - $quantityForAverageCost: Quantity used ONLY for weighted average cost calculation
        $quantity = abs((float)$detail->quantity);
        $quantityForStock = $quantity; // Always use movement quantity for stock tracking
        $quantityForAverageCost = $quantity; // Default: use same quantity

        // For PURCHASE_RECEPTION: check if we should use quantity_received for average cost
        if ($movement->movement_type === InventoryMovement::TYPE_PURCHASE_RECEPTION &&
          self::USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST === true &&
          $movement->reference instanceof PurchaseReception) {

          // Get the PurchaseReceptionDetail for this product
          $receptionDetail = PurchaseReceptionDetail::where('purchase_reception_id', $movement->reference->id)
            ->where('product_id', $productId)
            ->first();

          if ($receptionDetail) {
            // Use quantity_received (physical received in good condition) ONLY for average cost calculation
            // Example: Ordered 10, received 8 good + 2 observed with NC
            // - quantityForStock = 10 (all physically present in warehouse)
            // - quantityForAverageCost = 8 (only good units for cost calculation)
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

          // CRITICAL FIX: Always use quantityForStock (from movement) to update physical stock
          // This ensures that when is_credit_note = true, all units (good + observed) are counted
          // Example: 8 good + 2 observed = 10 total in warehouse
          $runningStock += $quantityForStock;

          // Calculate average cost ONLY if unit cost is provided (> 0)
          // Use quantityForAverageCost (could be quantity_received for better cost accuracy)
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
   * SIMPLIFICADO: Usa la tabla materializada weighted_average_cost_history en lugar de reconstruir todo
   *
   * @param int $productId
   * @param int $warehouseId
   * @return array
   * @throws Exception
   */
  public function getPriceCalculationDetails(int $productId, int $warehouseId): array
  {
    try {
      // Get stock record
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->with(['product', 'warehouse', 'currency'])
        ->firstOrFail();

      // Get configuration values
      $profitMargin = $this->getProfitMargin();
      $freightCommission = $this->getFreightCommission();
      $minimumDiscount = $this->getMinimunDiscount();
      $calculationMethod = ProductWarehouseStock::PRICE_CALCULATION_METHOD;

      // Get current prices from stock
      $currentStock = (float)$stock->quantity;
      $averageCost = (float)$stock->average_cost;
      $lastPurchasePrice = (float)$stock->cost_price;
      $publicSalePrice = (float)$stock->sale_price;
      $minimumSalePrice = $this->calculateMinimumSalePrice($publicSalePrice);

      // Get last TWO purchase movements from weighted_average_cost_history
      $purchaseHistory = WeightedAverageCostHistory::forProductWarehouse($productId, $warehouseId)
        ->byType(InventoryMovement::TYPE_PURCHASE_RECEPTION)
        ->reverseChronological()
        ->limit(2)
        ->get();

      $lastPurchase = $purchaseHistory->first();
      $previousPurchase = $purchaseHistory->count() > 1 ? $purchaseHistory->get(1) : null;

      // Build calculation steps
      $calculationSteps = [];

      // ===========================
      // STEP 1: Last Purchase Info
      // ===========================
      if ($lastPurchase) {
        $calculationSteps[] = [
          'step' => 1,
          'title' => 'Información de la Última Compra',
          'description' => 'Datos de la última recepción de compra registrada.',
          'data' => [
            'movement_number' => $lastPurchase->movement_number,
            'movement_date' => $lastPurchase->movement_date->format('Y-m-d'),
            'quantity_purchased' => (float)$lastPurchase->quantity_in,
            'unit_cost_in_pen' => (float)$lastPurchase->unit_cost_pen,
          ],
          'development' => [
            'unit_cost_in_pen' => (float)$lastPurchase->unit_cost_pen,
            'quantity_purchased' => (float)$lastPurchase->quantity_in,
            'movement_date' => $lastPurchase->movement_date->format('Y-m-d'),
          ],
          'message' => "Se compró una cantidad de {$lastPurchase->quantity_in} unidades a un costo unitario de PEN {$lastPurchase->unit_cost_pen}."
        ];
      } else {
        $calculationSteps[] = [
          'step' => 1,
          'title' => 'Información de la Última Compra',
          'description' => 'No se encontró historial de compras.',
          'data' => [
            'last_purchase_price' => $lastPurchasePrice > 0 ? $lastPurchasePrice : null,
          ],
          'development' => [
            'has_purchase_history' => false,
          ],
          'message' => 'No hay historial de compras registradas para este producto en este almacén.'
        ];
      }

      // =============================================
      // STEP 2: Weighted Average Cost Calculation
      // =============================================
      $step2Data = [
        'current_stock' => $currentStock,
        'average_cost' => $averageCost,
        'last_purchase_price' => $lastPurchasePrice,
      ];

      $step2Development = [
        'current_stock' => $currentStock,
        'current_average_cost' => $averageCost,
        'explanation' => 'El costo promedio se actualiza con cada compra usando promedio ponderado',
      ];

      $step2Message = $averageCost > 0
        ? "El costo promedio ponderado actual es PEN $averageCost."
        : "No hay costo promedio calculado.";

      $step2CalculationDetails = '';

      // If we have at least 2 purchases, we can show detailed calculation
      if ($lastPurchase && $previousPurchase) {
        // Stock ANTES de la última compra = stock del snapshot anterior
        $stockBeforeLastPurchase = (float)$previousPurchase->stock_after_movement;
        $previousAverageCost = (float)$previousPurchase->average_cost_after_movement;
        $lastPurchaseQuantity = (float)$lastPurchase->quantity_in;
        $lastPurchaseUnitCost = (float)$lastPurchase->unit_cost_pen;
        $stockAfterLastPurchase = (float)$lastPurchase->stock_after_movement;

        $step2Data['stock_before_last_purchase'] = $stockBeforeLastPurchase;
        $step2Data['previous_average_cost'] = $previousAverageCost;
        $step2Data['last_purchase_quantity'] = $lastPurchaseQuantity;

        $step2Development['stock_before_last_purchase'] = $stockBeforeLastPurchase;
        $step2Development['previous_average_cost'] = $previousAverageCost;
        $step2Development['last_purchase_quantity'] = $lastPurchaseQuantity;
        $step2Development['last_purchase_unit_cost'] = $lastPurchaseUnitCost;
        $step2Development['stock_after_purchase'] = $stockAfterLastPurchase;

        // Calculate
        $numeratorPart1 = $stockBeforeLastPurchase * $previousAverageCost;
        $numeratorPart2 = $lastPurchaseQuantity * $lastPurchaseUnitCost;
        $numeratorTotal = $numeratorPart1 + $numeratorPart2;
        $denominatorTotal = $stockBeforeLastPurchase + $lastPurchaseQuantity;

        $calculatedAverage = $denominatorTotal > 0 ? round($numeratorTotal / $denominatorTotal, 2) : 0;

        $step2CalculationDetails = "Costo_Promedio = ($stockBeforeLastPurchase × $previousAverageCost + $lastPurchaseQuantity × $lastPurchaseUnitCost) / ($stockBeforeLastPurchase + $lastPurchaseQuantity)\n" .
          "Costo_Promedio = ($numeratorPart1 + $numeratorPart2) / $denominatorTotal\n" .
          "Costo_Promedio = $numeratorTotal / $denominatorTotal\n" .
          "Costo_Promedio = $calculatedAverage";

        $step2Development['calculated_average'] = $calculatedAverage;
        $step2Development['matches_stored'] = abs($calculatedAverage - $averageCost) < 0.01;

        $step2Message = "Antes de la última compra había $stockBeforeLastPurchase unidades con costo promedio PEN $previousAverageCost. Se compraron $lastPurchaseQuantity unidades a PEN $lastPurchaseUnitCost. El nuevo costo promedio ponderado es PEN $averageCost.";
      } elseif ($lastPurchase) {
        // Solo hay UNA compra (es la primera)
        $step2Message = "Esta es la primera compra. El costo promedio es igual al costo de compra: PEN $averageCost.";
        $step2Development['is_first_purchase'] = true;
      }

      $calculationSteps[] = [
        'step' => 2,
        'title' => 'Cálculo del Costo Promedio Ponderado',
        'description' => 'Fórmula: (Stock_Antes × Costo_Promedio_Anterior + Cantidad_Comprada × Costo_Unitario) / (Stock_Antes + Cantidad_Comprada)',
        'data' => $step2Data,
        'development' => $step2Development,
        'formula' => 'Costo_Promedio = (Stock_Antes × Costo_Promedio_Anterior + Cantidad_Comprada × Costo_Unitario) / (Stock_Antes + Cantidad_Comprada)',
        'calculation_details' => $step2CalculationDetails,
        'message' => $step2Message
      ];

      // ===========================
      // STEP 3: Configuration Values
      // ===========================
      $profitMarginPercent = $profitMargin * 100;
      $freightCommissionPercent = $freightCommission * 100;

      $calculationSteps[] = [
        'step' => 3,
        'title' => 'Valores de Configuración',
        'description' => 'Porcentajes configurados para el cálculo del precio de venta.',
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
        'message' => "Margen de ganancia: $profitMarginPercent%. Comisión de flete: $freightCommissionPercent%. Método: $calculationMethod."
      ];

      // =======================
      // STEP 4: PVP Calculation
      // =======================
      $calculatedPVP = 0;
      $formulaExplanation = '';
      $calculationDetails = '';
      $step4Development = [];

      if ($averageCost > 0) {
        if ($calculationMethod === 1) {
          // Method 1: PVP = Costo / (1 - margen) * (1 + flete)
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
            'base_price' => round($basePrice, 2),
            'calculated_pvp' => $calculatedPVP,
            'method' => 1,
          ];
        } else {
          // Method 2: PVP = Costo / (1 - (margen + flete))
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
            'divisor' => $divisor,
            'calculated_pvp' => $calculatedPVP,
            'method' => 2,
          ];
        }
      } else {
        $step4Development = [
          'average_cost' => 0,
          'can_calculate' => false,
        ];
      }

      $calculationSteps[] = [
        'step' => 4,
        'title' => 'Cálculo del Precio de Venta al Público (PVP)',
        'description' => "Aplicación del método $calculationMethod.",
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
          ? "PVP calculado: PEN $calculatedPVP. PVP almacenado: PEN $publicSalePrice."
          : "No se puede calcular PVP porque el costo promedio es 0."
      ];

      // =============================
      // STEP 5: Minimum Sale Price
      // =============================
      $minimumDiscountPercent = $minimumDiscount * 100;
      $oneMinusDiscount = 1 - $minimumDiscount;

      $calculationSteps[] = [
        'step' => 5,
        'title' => 'Precio de Venta Mínimo',
        'description' => 'Precio mínimo aplicando el descuento máximo permitido.',
        'data' => [
          'public_sale_price' => $publicSalePrice,
          'minimum_discount' => $minimumDiscount,
          'minimum_discount_percent' => "$minimumDiscountPercent%",
          'minimum_sale_price' => $minimumSalePrice,
        ],
        'development' => [
          'public_sale_price' => $publicSalePrice,
          'minimum_discount' => $minimumDiscount,
          'minimum_sale_price' => $minimumSalePrice,
        ],
        'formula' => 'Precio_Mínimo = PVP × (1 - Descuento_Mínimo)',
        'calculation_details' => "Precio_Mínimo = $publicSalePrice × (1 - $minimumDiscount)\n" .
          "Precio_Mínimo = $publicSalePrice × $oneMinusDiscount\n" .
          "Precio_Mínimo = $minimumSalePrice",
        'message' => $publicSalePrice > 0
          ? "Precio mínimo: PEN $minimumSalePrice (descuento máximo $minimumDiscountPercent%)."
          : "No se puede calcular precio mínimo."
      ];

      // Build summary
      $summary = [
        'product_id' => $productId,
        'product_code' => $stock->product?->code,
        'product_name' => $stock->product?->name,
        'warehouse_id' => $warehouseId,
        'warehouse_name' => $stock->warehouse?->description,
        'currency' => $stock->currency?->code ?? 'PEN',
        'current_stock' => $currentStock,
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

  /**
   * MÉTODO CENTRALIZADO: Reconstruye el historial de costo promedio ponderado
   *
   * Este método materializa (guarda en tabla) el historial completo de movimientos
   * con sus respectivos snapshots de stock y costo promedio después de cada movimiento.
   *
   * CUÁNDO USARLO:
   * 1. Después de una COMPRA (addStock) → recalcular desde el día de la compra
   * 2. Después de una NOTA DE CRÉDITO retroactiva → recalcular TODA la historia
   * 3. Después de una VENTA (removeStock) → recalcular desde el día de la venta
   *
   * PROPÓSITO:
   * - Evitar cálculos complejos "on the fly" en getPriceCalculationDetails
   * - Permitir consultas rápidas del historial de costos
   * - Soportar correcciones retroactivas (ej: NC de hace 3 meses)
   * - Facilitar auditoría y trazabilidad
   *
   * FUNCIONAMIENTO:
   * 1. Si $fromDate es NULL → borra TODO el historial y lo reconstruye desde cero
   * 2. Si $fromDate tiene valor → borra solo desde esa fecha y reconstruye desde ahí
   * 3. Usa getStockMovementHistory() como fuente de verdad
   * 4. Guarda cada movimiento como un snapshot en weighted_average_cost_history
   *
   * @param int $productId ID del producto
   * @param int $warehouseId ID del almacén
   * @param \DateTime|string|null $fromDate Fecha desde la cual recalcular (NULL = todo)
   * @return array Resumen del proceso con estadísticas
   * @throws Exception
   */
  public function rebuildWeightedAverageCostHistory(
    int                   $productId,
    int                   $warehouseId,
    \DateTime|string|null $fromDate = null
  ): array
  {
    DB::beginTransaction();
    try {
      // Convertir $fromDate a Carbon si viene como string
      if ($fromDate && is_string($fromDate)) {
        $fromDate = \Carbon\Carbon::parse($fromDate);
      }

      // PASO 1: Determinar el alcance del recálculo
      $fullRebuild = ($fromDate === null);
      $fromDateStr = $fromDate ? $fromDate->format('Y-m-d') : null;

      // PASO 2: Obtener estado base ANTES de $fromDate (si aplica)
      $baseStock = 0;
      $baseAverageCost = 0;

      if (!$fullRebuild) {
        // Buscar el último snapshot ANTES de $fromDate
        $lastSnapshotBeforeDate = WeightedAverageCostHistory::getSnapshotBeforeDate(
          $productId,
          $warehouseId,
          $fromDateStr
        );

        if ($lastSnapshotBeforeDate) {
          $baseStock = (float)$lastSnapshotBeforeDate->stock_after_movement;
          $baseAverageCost = (float)$lastSnapshotBeforeDate->average_cost_after_movement;
        }
      }

      // PASO 3: Eliminar snapshots antiguos (desde $fromDate o todos)
      if ($fullRebuild) {
        // Borrar TODO el historial
        $deletedCount = WeightedAverageCostHistory::where('product_id', $productId)
          ->where('warehouse_id', $warehouseId)
          ->delete();
      } else {
        // Borrar solo desde $fromDate
        $deletedCount = WeightedAverageCostHistory::deleteFromDate(
          $productId,
          $warehouseId,
          $fromDateStr
        );
      }

      // PASO 4: Obtener el historial completo desde getStockMovementHistory()
      // Este método ya tiene toda la lógica correcta de cálculo de costo promedio
      $historyData = $this->getStockMovementHistory($productId, $warehouseId);

      if (!$historyData['success']) {
        throw new Exception("Error al obtener historial de movimientos para producto $productId en almacén $warehouseId");
      }

      // PASO 5: Insertar snapshots en la tabla materializada
      $insertedCount = 0;
      $skippedCount = 0;

      foreach ($historyData['history'] as $item) {
        // Si es recálculo parcial, solo insertar movimientos >= $fromDate
        if (!$fullRebuild && $fromDateStr) {
          $itemDate = \Carbon\Carbon::parse($item['movement_date']);
          if ($itemDate->lt($fromDate)) {
            $skippedCount++;
            continue; // Saltar movimientos anteriores a $fromDate
          }
        }

        // Crear snapshot
        WeightedAverageCostHistory::create([
          'product_id' => $productId,
          'warehouse_id' => $warehouseId,
          'movement_id' => $item['movement_id'],
          'movement_date' => $item['movement_date'],
          'movement_type' => $item['movement_type'],
          'movement_number' => $item['movement_number'] ?? null,
          'quantity_in' => $item['is_inbound'] ? $item['quantity'] : 0,
          'quantity_out' => !$item['is_inbound'] ? $item['quantity'] : 0,
          'unit_cost_pen' => $item['unit_cost_in_pen'] ?? 0,
          'stock_after_movement' => $item['stock_after_movement'],
          'average_cost_after_movement' => $item['average_cost_after_movement'],
          'recalculated_at' => now(),
        ]);

        $insertedCount++;
      }

      // PASO 6: Actualizar ProductWarehouseStock con los valores finales calculados
      $finalStock = $historyData['calculated_final_stock'] ?? 0;
      $finalAverageCost = $historyData['calculated_final_average_cost'] ?? 0;

      // Obtener el último costo unitario de entrada (cost_price)
      // Buscar el último movimiento de entrada con unit_cost > 0
      $lastCostPrice = 0;
      $lastMovementDate = null;

      // Recorrer el historial en orden inverso para obtener el último costo y fecha
      for ($i = count($historyData['history']) - 1; $i >= 0; $i--) {
        $item = $historyData['history'][$i];

        // Guardar la fecha del último movimiento
        if ($lastMovementDate === null) {
          $lastMovementDate = $item['movement_date'];
        }

        // Buscar el último movimiento de entrada con costo > 0
        if ($lastCostPrice === 0 && $item['is_inbound'] && isset($item['unit_cost_in_pen']) && $item['unit_cost_in_pen'] > 0) {
          $lastCostPrice = $item['unit_cost_in_pen'];
        }

        // Si ya tenemos ambos valores, podemos salir del loop
        if ($lastCostPrice > 0 && $lastMovementDate !== null) {
          break;
        }
      }

      // Calcular el sale_price basado en el nuevo average_cost
      $profitMargin = $this->getProfitMargin();
      $freightCommission = $this->getFreightCommission();
      $minimumDiscount = $this->getMinimunDiscount();

      if (ProductWarehouseStock::PRICE_CALCULATION_METHOD === 1) {
        // Método 1: PVP = Costo / (1 - margen) * (1 + impuesto)
        $salePrice = round(
          ($finalAverageCost / (1 - $profitMargin)) * (1 + $freightCommission),
          2
        );
      } else {
        // Método 2 (por defecto): PVP = Costo / (1 - (margen + impuesto))
        $salePrice = round(
          $finalAverageCost / (1 - ($profitMargin + $freightCommission)),
          2
        );
      }

      // Calcular el sale_price_min basado en el PVP
      // Fórmula: Precio_Mínimo = PVP × (1 - Descuento_Mínimo)
      $salePriceMin = $salePrice > 0 ? round($salePrice * (1 - $minimumDiscount), 2) : 0;

      // Actualizar el registro de ProductWarehouseStock
      $stock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->first();

      if ($stock) {
        $stock->quantity = $finalStock;
        $stock->average_cost = $finalAverageCost;
        $stock->cost_price = $lastCostPrice;
        $stock->sale_price = $salePrice;
        $stock->sale_price_min = $salePriceMin;

        if ($lastMovementDate) {
          $stock->last_movement_date = $lastMovementDate;
        }

        // Update available quantity based on new stock
        $stock->updateAvailableQuantity();

        $stock->save();
      }

      DB::commit();

      // Retornar resumen del proceso
      return [
        'success' => true,
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'rebuild_type' => $fullRebuild ? 'FULL' : 'PARTIAL',
        'from_date' => $fromDateStr,
        'base_stock_before_rebuild' => $baseStock,
        'base_average_cost_before_rebuild' => $baseAverageCost,
        'snapshots_deleted' => $deletedCount,
        'snapshots_inserted' => $insertedCount,
        'snapshots_skipped' => $skippedCount,
        'final_stock' => $historyData['calculated_final_stock'] ?? 0,
        'final_average_cost' => $historyData['calculated_final_average_cost'] ?? 0,
        'stock_matches_database' => $historyData['stock_matches'] ?? false,
        'average_cost_matches_database' => $historyData['average_cost_matches'] ?? false,
        'rebuilt_at' => now()->format('Y-m-d H:i:s'),
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
