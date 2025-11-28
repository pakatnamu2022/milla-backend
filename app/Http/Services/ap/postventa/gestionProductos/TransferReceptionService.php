<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\TransferReceptionResource;
use App\Http\Services\BaseService;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use App\Models\ap\postventa\gestionProductos\TransferReceptionDetail;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TransferReceptionService extends BaseService
{
  protected $stockService;
  protected $inventoryMovementService;

  public function __construct()
  {
    $this->stockService = new ProductWarehouseStockService();
    $this->inventoryMovementService = new InventoryMovementService();
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      TransferReception::class,
      $request,
      ['status', 'warehouse_id', 'reception_date'],
      ['reception_date' => 'desc', 'created_at' => 'desc'],
      TransferReceptionResource::class,
    );
  }

  public function find($id)
  {
    $reception = TransferReception::where('id', $id)->first();

    if (!$reception) {
      throw new Exception('Recepción de transferencia no encontrada');
    }

    return $reception;
  }

  public function show($id)
  {
    return new TransferReceptionResource(
      $this->find($id)->load([
        'transferMovement.details.product',
        'shippingGuide',
        'warehouse',
        'details.product'
      ])
    );
  }

  public function createReception(array $data): TransferReception
  {
    DB::beginTransaction();
    try {
      // Get the TRANSFER_OUT movement
      $transferOutMovement = $this->inventoryMovementService->find($data['transfer_movement_id']);

      // Get the shipping guide associated with the TRANSFER_OUT
      $shippingGuide = $transferOutMovement->reference;

      // Generate reception number
      $receptionNumber = TransferReception::generateReceptionNumber();

      // Create reception header
      $reception = TransferReception::create([
        'reception_number' => $receptionNumber,
        'transfer_movement_id' => $transferOutMovement->id,
        'shipping_guide_id' => $shippingGuide->id,
        'warehouse_id' => $data['warehouse_id'],
        'reception_date' => $data['reception_date'],
        'status' => TransferReception::STATUS_PENDING,
        'notes' => $data['notes'] ?? "Recepción de transferencia {$transferOutMovement->movement_number}",
        'received_by' => Auth::id(),
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create reception details
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($data['details'] as $detail) {
        TransferReceptionDetail::create([
          'transfer_reception_id' => $reception->id,
          'product_id' => $detail['product_id'],
          'quantity_sent' => $detail['quantity_sent'],
          'quantity_received' => $detail['quantity_received'],
          'observed_quantity' => $detail['observed_quantity'] ?? 0,
          'reason_observation' => $detail['reason_observation'] ?? null,
          'observation_notes' => $detail['observation_notes'] ?? null,
        ]);

        $totalItems++;
        $totalQuantity += $detail['quantity_received'];
      }

      // Update reception totals
      $reception->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
      ]);

      // Create TRANSFER_IN movement for the received quantities
      $transferInMovement = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
        'movement_date' => $data['reception_date'],
        'warehouse_id' => $data['warehouse_id'], // Destination warehouse
        'warehouse_destination_id' => null,
        'reason_in_out_id' => $transferOutMovement->reason_in_out_id,
        'reference_type' => TransferReception::class,
        'reference_id' => $reception->id,
        'user_id' => Auth::id(),
        'status' => InventoryMovement::STATUS_APPROVED,
        'notes' => "Ingreso por recepción de transferencia {$transferOutMovement->movement_number}",
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create TRANSFER_IN movement details (only for received quantities)
      $totalItemsIn = 0;
      $totalQuantityIn = 0;

      foreach ($data['details'] as $detail) {
        if ($detail['quantity_received'] > 0) {
          InventoryMovementDetail::create([
            'inventory_movement_id' => $transferInMovement->id,
            'product_id' => $detail['product_id'],
            'quantity' => $detail['quantity_received'],
            'unit_cost' => 0, // Cost is tracked at origin warehouse
            'total_cost' => 0,
            'batch_number' => null,
            'expiration_date' => null,
            'notes' => "Recibido de transferencia {$transferOutMovement->movement_number}",
          ]);

          $totalItemsIn++;
          $totalQuantityIn += $detail['quantity_received'];
        }
      }

      // Update TRANSFER_IN movement totals
      $transferInMovement->update([
        'total_items' => $totalItemsIn,
        'total_quantity' => $totalQuantityIn,
      ]);

      // Update stock: Move from in_transit to actual quantity
      foreach ($data['details'] as $detail) {
        if ($detail['quantity_received'] > 0) {
          $this->stockService->moveFromInTransitToDestination(
            $detail['product_id'],
            $transferOutMovement->warehouse_id, // Origin warehouse
            $data['warehouse_id'], // Destination warehouse
            $detail['quantity_received']
          );
        }

        // Handle observed quantities (damaged/missing items)
        // Remove from in_transit but don't add to destination quantity
        $observedQty = $detail['observed_quantity'] ?? 0;
        if ($observedQty > 0) {
          // Just remove from in_transit (stock is lost/damaged)
          $originStock = $this->stockService->getStock(
            $detail['product_id'],
            $transferOutMovement->warehouse_id
          );

          if ($originStock && $originStock->quantity_in_transit >= $observedQty) {
            $originStock->quantity_in_transit -= $observedQty;
            $originStock->save();
          }
        }
      }

      // Update TRANSFER_OUT movement status to APPROVED (completed)
      $transferOutMovement->update([
        'status' => InventoryMovement::STATUS_APPROVED
      ]);

      // Auto-approve reception
      $reception->update([
        'status' => TransferReception::STATUS_APPROVED,
        'reviewed_by' => Auth::id(),
        'reviewed_at' => now(),
      ]);

      DB::commit();
      return $reception->fresh([
        'transferMovement.details.product',
        'shippingGuide',
        'warehouse',
        'details.product'
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id)
  {
    DB::beginTransaction();
    try {
      $reception = $this->find($id)->load(['details', 'transferMovement']);

      if ($reception->status === TransferReception::STATUS_APPROVED) {
        $transferOutMovement = $reception->transferMovement;

        // Find the TRANSFER_IN movement
        $transferInMovement = InventoryMovement::where('reference_type', TransferReception::class)
          ->where('reference_id', $reception->id)
          ->first();

        if ($transferInMovement) {
          // Revert stock changes manually
          // 1. Remove stock from destination warehouse (that was added by TRANSFER_IN)
          // 2. Add stock back to in_transit in origin warehouse (to restore TRANSFER_OUT state)

          foreach ($reception->details as $detail) {
            // Remove stock from destination warehouse (where it was received)
            if ($detail->quantity_received > 0) {
              $this->stockService->removeStock(
                $detail->product_id,
                $reception->warehouse_id,
                $detail->quantity_received
              );

              // Add back to in_transit in origin warehouse
              $originStock = $this->stockService->getStock(
                $detail->product_id,
                $transferOutMovement->warehouse_id
              );

              if ($originStock) {
                $originStock->quantity_in_transit += $detail->quantity_received;
                $originStock->save();
              }
            }

            // Handle observed quantities (damaged/missing items)
            // Add them back to in_transit as they were lost during reception
            $observedQty = $detail->observed_quantity ?? 0;
            if ($observedQty > 0) {
              $originStock = $this->stockService->getStock(
                $detail->product_id,
                $transferOutMovement->warehouse_id
              );

              if ($originStock) {
                $originStock->quantity_in_transit += $observedQty;
                $originStock->save();
              }
            }
          }

          // Delete the TRANSFER_IN movement
          $transferInMovement->delete();
        }

        // Set TRANSFER_OUT movement back to IN_TRANSIT
        $transferOutMovement->update([
          'status' => InventoryMovement::STATUS_IN_TRANSIT
        ]);
      }

      // Delete reception (soft delete)
      $reception->delete();

      DB::commit();
      return [
        'message' => 'Recepción eliminada correctamente. Los movimientos de inventario han sido revertidos.'
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
