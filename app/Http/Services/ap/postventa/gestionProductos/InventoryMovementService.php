<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
  protected $stockService;

  public function __construct()
  {
    $this->stockService = new ProductWarehouseStockService();
  }

  /**
   * Create inventory movement from purchase reception
   *
   * @param PurchaseReception $reception
   * @return InventoryMovement
   * @throws Exception
   */
  public function createFromPurchaseReception(PurchaseReception $reception): InventoryMovement
  {
    DB::beginTransaction();
    try {
      // Create movement header
      $movement = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEPTION,
        'movement_date' => $reception->reception_date,
        'warehouse_id' => $reception->warehouse_id,
        'reference_type' => PurchaseReception::class,
        'reference_id' => $reception->id,
        'user_id' => Auth::id(),
        'status' => InventoryMovement::STATUS_APPROVED,
        'notes' => "Ingreso por recepción {$reception->reception_number} de OC {$reception->purchaseOrder->number}",
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create movement details from reception details
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($reception->details as $detail) {
        // quantity_received already represents the actual physical quantity received
        // (frontend sends only good items, observed_quantity is separate)
        $quantityReceived = $detail->quantity_received;

        if ($quantityReceived > 0) {
          InventoryMovementDetail::create([
            'inventory_movement_id' => $movement->id,
            'product_id' => $detail->product_id,
            'quantity' => $quantityReceived,
            'unit_cost' => $detail->unit_cost ?? 0,
            'total_cost' => $quantityReceived * ($detail->unit_cost ?? 0),
            'batch_number' => $detail->batch_number,
            'expiration_date' => $detail->expiration_date,
            'notes' => $detail->reception_type === 'ORDERED'
              ? "Item de OC - {$detail->product->name}"
              : "{$detail->reception_type} - {$detail->product->name}",
          ]);

          $totalItems++;
          $totalQuantity += $quantityReceived;
        }
      }

      // Update movement totals
      $movement->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
      ]);

      // Update stock automatically since movement is created as APPROVED
      $this->stockService->updateStockFromMovement($movement->fresh('details'));

      DB::commit();
      return $movement;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Create inventory adjustment (loss, damage, theft, etc.)
   *
   * @param array $data
   * @param array $details
   * @return InventoryMovement
   * @throws Exception
   */
  public function createAdjustment(array $data, array $details): InventoryMovement
  {
    DB::beginTransaction();
    try {
      // Validate movement type
      $validTypes = [
        InventoryMovement::TYPE_ADJUSTMENT_IN,
        InventoryMovement::TYPE_ADJUSTMENT_OUT,
        InventoryMovement::TYPE_LOSS,
        InventoryMovement::TYPE_DAMAGE,
      ];

      if (!in_array($data['movement_type'], $validTypes)) {
        throw new Exception('Tipo de movimiento no válido para ajustes de inventario');
      }

      // Validate warehouse exists
      if (!isset($data['warehouse_id'])) {
        throw new Exception('Debe especificar un almacén');
      }

      // Validate details
      if (empty($details)) {
        throw new Exception('Debe proporcionar al menos un producto');
      }

      // Create movement header
      $movement = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_type' => $data['movement_type'],
        'movement_date' => $data['movement_date'] ?? now(),
        'warehouse_id' => $data['warehouse_id'],
        'reference_type' => null,
        'reference_id' => null,
        'user_id' => Auth::id(),
        'status' => InventoryMovement::STATUS_APPROVED,
        'notes' => $data['notes'] ?? $this->getDefaultNotes($data['movement_type']),
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create movement details
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($details as $detail) {
        // Validate product exists in warehouse
        $stock = $this->stockService->getStock($detail['product_id'], $data['warehouse_id']);

        // For outbound movements (loss, damage, adjustment_out), validate sufficient stock
        if (in_array($data['movement_type'], [
          InventoryMovement::TYPE_ADJUSTMENT_OUT,
          InventoryMovement::TYPE_LOSS,
          InventoryMovement::TYPE_DAMAGE
        ])) {
          if ($stock < $detail['quantity']) {
            throw new Exception(
              "Stock insuficiente para producto ID {$detail['product_id']}. " .
              "Stock disponible: {$stock}, Cantidad solicitada: {$detail['quantity']}"
            );
          }
        }

        InventoryMovementDetail::create([
          'inventory_movement_id' => $movement->id,
          'product_id' => $detail['product_id'],
          'quantity' => $detail['quantity'],
          'unit_cost' => $detail['unit_cost'] ?? 0,
          'total_cost' => $detail['quantity'] * ($detail['unit_cost'] ?? 0),
          'batch_number' => $detail['batch_number'] ?? null,
          'expiration_date' => $detail['expiration_date'] ?? null,
          'notes' => $detail['notes'] ?? null,
        ]);

        $totalItems++;
        $totalQuantity += $detail['quantity'];
      }

      // Update movement totals
      $movement->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
      ]);

      // Update stock automatically since movement is created as APPROVED
      $this->stockService->updateStockFromMovement($movement->fresh('details'));

      DB::commit();
      return $movement;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get default notes based on movement type
   *
   * @param string $movementType
   * @return string
   */
  private function getDefaultNotes(string $movementType): string
  {
    $notes = [
      InventoryMovement::TYPE_LOSS => 'Regularización por pérdida de productos',
      InventoryMovement::TYPE_DAMAGE => 'Regularización por productos dañados',
      InventoryMovement::TYPE_ADJUSTMENT_OUT => 'Ajuste negativo de inventario',
      InventoryMovement::TYPE_ADJUSTMENT_IN => 'Ajuste positivo de inventario',
    ];

    return $notes[$movementType] ?? 'Ajuste de inventario';
  }

}
