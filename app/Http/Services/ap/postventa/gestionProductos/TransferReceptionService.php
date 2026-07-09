<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\TransferReceptionResource;
use App\Http\Services\BaseService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use App\Models\ap\postventa\gestionProductos\TransferReceptionDetail;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TransferReceptionService extends BaseService
{
  protected $stockService;
  protected $inventoryMovementService;

  public function __construct(
    ProductWarehouseStockService $stockService,
    InventoryMovementService     $inventoryMovementService
  )
  {
    $this->stockService = $stockService;
    $this->inventoryMovementService = $inventoryMovementService;
  }

  public function list(Request $request)
  {

    return $this->getFilteredResults(
      TransferReception::with('transferMovement'),
      $request,
      TransferReception::filters,
      TransferReception::sorts,
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

      // Get item_type from transfer movement
      $itemType = $transferOutMovement->item_type ?? TransferReception::ITEM_TYPE_PRODUCT;

      // Generate reception number
      $receptionNumber = TransferReception::generateReceptionNumber();

      // Estado de recepción
      if ($shippingGuide->is_accounted) {
        $statusReception = TransferReception::STATUS_APPROVED;
      } else {
        $statusReception = TransferReception::STATUS_PENDING;
      };

      // Create reception header
      $reception = TransferReception::create([
        'reception_number' => $receptionNumber,
        'transfer_movement_id' => $transferOutMovement->id,
        'shipping_guide_id' => $shippingGuide->id,
        'warehouse_id' => $data['warehouse_id'],
        'reception_date' => $data['reception_date'],
        'item_type' => $itemType,
        'status' => $statusReception,
        'notes' => $data['notes'] ?? "Recepción de transferencia {$transferOutMovement->movement_number}",
        'received_by' => Auth::id(),
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create reception details
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($data['details'] as $detail) {
        // For SERVICIO type, product_id can be null
        $productId = isset($detail['product_id']) ? $detail['product_id'] : null;

        TransferReceptionDetail::create([
          'transfer_reception_id' => $reception->id,
          'product_id' => $productId,
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

      if ($itemType === TransferReception::ITEM_TYPE_SERVICE) {
        $this->generateInventoryMovement($reception, $transferOutMovement);
      }

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

  public function generateInventoryMovement(TransferReception $reception, InventoryMovement $transferOutMovement): InventoryMovement
  {
    // Get the shipping guide associated with the TRANSFER_OUT
    $shippingGuide = $transferOutMovement->reference;

    //marcamos la $shippingGuide como recibida
    $shippingGuide->update([
      'is_received' => true,
      'received_by' => $reception->received_by,
      'received_date' => now(),
    ]);

    // Create TRANSFER_IN movement for the received quantities
    $transferInMovement = InventoryMovement::create([
      'movement_number' => InventoryMovement::generateMovementNumber(),
      'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
      'item_type' => $reception->item_type,
      'movement_date' => $reception->reception_date,
      'warehouse_id' => $transferOutMovement->warehouse_id, // Origin warehouse
      'warehouse_destination_id' => $reception->warehouse_id, // Destination warehouse
      'reason_in_out_id' => $transferOutMovement->reason_in_out_id,
      'reference_type' => TransferReception::class,
      'reference_id' => $reception->id,
      'user_id' => $reception->received_by,
      'status' => InventoryMovement::STATUS_APPROVED,
      'notes' => "Ingreso por recepción de transferencia {$transferOutMovement->movement_number}",
      'total_items' => 0,
      'total_quantity' => 0,
    ]);

    // Create TRANSFER_IN movement details (only for received quantities)
    $totalItemsIn = 0;
    $totalQuantityIn = 0;

    foreach ($reception->details as $detail) {
      if ($detail['quantity_received'] > 0) {
        // For SERVICIO type, product_id can be null
        $productId = isset($detail['product_id']) ? $detail['product_id'] : null;

        InventoryMovementDetail::create([
          'inventory_movement_id' => $transferInMovement->id,
          'product_id' => $productId,
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

    // Update stock: Move from in_transit to actual quantity (only for PRODUCTO type)
    if ($reception->item_type === TransferReception::ITEM_TYPE_PRODUCT) {
      foreach ($reception->details as $detail) {
        // Skip if product_id is null (service items)
        if (!isset($detail['product_id'])) {
          continue;
        }

        if ($detail['quantity_received'] > 0) {
          $this->stockService->moveFromInTransitToDestination(
            $detail['product_id'],
            $transferOutMovement->warehouse_id, // Origin warehouse
            $reception->warehouse_id, // Destination warehouse
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
    }

    // Update TRANSFER_OUT movement status to APPROVED (completed)
    $transferOutMovement->update([
      'status' => InventoryMovement::STATUS_APPROVED
    ]);

    // Auto-approve reception
    $reception->update([
      'status' => TransferReception::STATUS_APPROVED,
      'reviewed_by' => $reception->received_by,
      'reviewed_at' => now(),
    ]);

    return $transferInMovement;
  }

  /**
   * Genera el movimiento de inventario de entrada para un traslado de sede
   * que no pasa por recepción: toma los datos directamente del TRANSFER_OUT.
   */
  public function generateInventoryMovementFromTransferOut(InventoryMovement $transferOutMovement): InventoryMovement
  {
    $shippingGuide = $transferOutMovement->reference;

    $shippingGuide->update([
      'is_received' => true,
      'received_by' => $transferOutMovement->user_id,
      'received_date' => now(),
    ]);

    $transferInMovement = InventoryMovement::create([
      'movement_number' => InventoryMovement::generateMovementNumber(),
      'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
      'item_type' => $transferOutMovement->item_type,
      'movement_date' => now(),
      'warehouse_id' => $transferOutMovement->warehouse_id,
      'warehouse_destination_id' => $transferOutMovement->warehouse_destination_id,
      'reason_in_out_id' => $transferOutMovement->reason_in_out_id,
      'reference_type' => ShippingGuides::class,
      'reference_id' => $shippingGuide->id,
      'user_id' => $transferOutMovement->user_id,
      'status' => InventoryMovement::STATUS_APPROVED,
      'notes' => "Ingreso por traslado de sede desde {$transferOutMovement->movement_number}",
      'total_items' => 0,
      'total_quantity' => 0,
    ]);

    $totalItems = 0;
    $totalQuantity = 0;

    foreach ($transferOutMovement->details as $detail) {
      InventoryMovementDetail::create([
        'inventory_movement_id' => $transferInMovement->id,
        'product_id' => $detail->product_id,
        'quantity' => $detail->quantity,
        'unit_cost' => 0,
        'total_cost' => 0,
        'batch_number' => null,
        'expiration_date' => null,
        'notes' => "Traslado de sede {$transferOutMovement->movement_number}",
      ]);

      $totalItems++;
      $totalQuantity += $detail->quantity;
    }

    $transferInMovement->update([
      'total_items' => $totalItems,
      'total_quantity' => $totalQuantity,
    ]);

    if ($transferOutMovement->item_type === TransferReception::ITEM_TYPE_PRODUCT) {
      foreach ($transferOutMovement->details as $detail) {
        if (!$detail->product_id) continue;

        $this->stockService->moveFromInTransitToDestination(
          $detail->product_id,
          $transferOutMovement->warehouse_id,
          $transferOutMovement->warehouse_destination_id,
          $detail->quantity
        );
      }
    }

    $transferOutMovement->update([
      'status' => InventoryMovement::STATUS_APPROVED,
    ]);

    return $transferInMovement;
  }

  /**
   * Genera el movimiento de inventario inverso cuando se anula una transferencia
   * Crea un TRANSFER_IN para completar la reversión (el TRANSFER_OUT ya fue creado en cancelTransfer)
   */
  public function generateReversalInventoryMovement(TransferReception $reception, InventoryMovement $cancelledTransferOutMovement, ShippingGuides $shippingGuide): ?InventoryMovement
  {
    // Verificar si la recepción NO está aprobada (ya fue revertida o nunca se procesó)
    if ($reception->status !== TransferReception::STATUS_APPROVED) {
      Log::info('La recepción ya fue revertida o no está aprobada', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reception_id' => $reception->id,
        'reception_status' => $reception->status
      ]);
      return null;
    }

    // Verificar si ya existe un TRANSFER_IN de reversión para esta recepción
    // (evitar duplicados si el job se ejecuta múltiples veces)
    $existingReversalMovement = InventoryMovement::where('reference_type', TransferReception::class)
      ->where('reference_id', $reception->id)
      ->where('movement_type', InventoryMovement::TYPE_TRANSFER_IN)
      ->whereNotNull('cancelled_inventory_movement_id') // El de reversión SÍ tiene este campo
      ->first();

    if ($existingReversalMovement) {
      Log::info('Ya existe un movimiento de reversión TRANSFER_IN para esta recepción', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reception_id' => $reception->id,
        'existing_reversal_movement_id' => $existingReversalMovement->id
      ]);
      return $existingReversalMovement;
    }

    // IMPORTANTE: El TRANSFER_OUT de cancelación YA fue creado en InventoryMovementService->cancelTransfer()
    // con los almacenes invertidos:
    // warehouse_id = destino original (B) → saca stock de B y lo pone en in_transit
    // warehouse_destination_id = origen original (A) → devuelve a A
    // Status: IN_TRANSIT (el stock está en tránsito desde B hacia A)

    // Ahora necesitamos crear el TRANSFER_IN para completar la reversión
    // (mover stock de in_transit al almacén destino A)

    // Buscar el TRANSFER_IN original (el que se generó en la transferencia normal)
    $originalTransferIn = InventoryMovement::where('reference_type', TransferReception::class)
      ->where('reference_id', $reception->id)
      ->where('movement_type', InventoryMovement::TYPE_TRANSFER_IN)
      ->whereNull('cancelled_inventory_movement_id') // El movimiento original NO tiene este campo
      ->first();

    if (!$originalTransferIn) {
      throw new Exception("No se encontró el movimiento TRANSFER_IN original para la recepción {$reception->id}");
    }

    // Marcar la guía como recibida (reversión en proceso)
    $shippingGuide->update([
      'is_received' => true,
      'received_by' => $reception->received_by,
      'received_date' => now(),
    ]);

    // Crear movimiento TRANSFER_IN de reversión (ingreso al almacén origen original A)
    $reversalMovement = InventoryMovement::create([
      'movement_number' => InventoryMovement::generateMovementNumber(),
      'movement_type' => InventoryMovement::TYPE_TRANSFER_IN, // TRANSFER_IN para completar la reversión
      'item_type' => $reception->item_type,
      'movement_date' => now(),
      // Usamos los almacenes YA invertidos del movimiento de cancelación
      'warehouse_id' => $cancelledTransferOutMovement->warehouse_id, // Origen de la reversión (B)
      'warehouse_destination_id' => $cancelledTransferOutMovement->warehouse_destination_id, // Destino de la reversión (A)
      'reason_in_out_id' => $cancelledTransferOutMovement->reason_in_out_id,
      'reference_type' => TransferReception::class,
      'reference_id' => $reception->id,
      'user_id' => $reception->received_by,
      'status' => InventoryMovement::STATUS_APPROVED,
      'notes' => "Ingreso por reversión de transferencia {$originalTransferIn->movement_number}",
      'total_items' => 0,
      'total_quantity' => 0,
      'cancelled_inventory_movement_id' => $originalTransferIn->id, // Marca que revierte al original
    ]);

    // Crear detalles del movimiento de reversión
    $totalItems = 0;
    $totalQuantity = 0;

    foreach ($reception->details as $detail) {
      if ($detail['quantity_received'] > 0) {
        $productId = isset($detail['product_id']) ? $detail['product_id'] : null;

        InventoryMovementDetail::create([
          'inventory_movement_id' => $reversalMovement->id,
          'product_id' => $productId,
          'quantity' => $detail['quantity_received'],
          'unit_cost' => 0, // Cost is tracked at origin warehouse
          'total_cost' => 0,
          'batch_number' => null,
          'expiration_date' => null,
          'notes' => "Recibido de reversión {$cancelledTransferOutMovement->movement_number}",
        ]);

        $totalItems++;
        $totalQuantity += $detail['quantity_received'];
      }
    }

    // Actualizar totales
    $reversalMovement->update([
      'total_items' => $totalItems,
      'total_quantity' => $totalQuantity,
    ]);

    // Actualizar stock: Mover del tránsito al destino (solo para PRODUCTO type)
    // El stock ya está en tránsito desde cancelTransfer->moveStockToInTransit
    // Ahora necesitamos moverlo del tránsito al almacén de destino (origen original)
    if ($reception->item_type === TransferReception::ITEM_TYPE_PRODUCT) {
      foreach ($reception->details as $detail) {
        if (!isset($detail['product_id'])) {
          continue;
        }

        if ($detail['quantity_received'] > 0) {
          // Mover del stock en tránsito (almacén destino original) al almacén origen original
          // warehouse_id del cancelledTransferOutMovement = destino original (donde está el stock en tránsito)
          // warehouse_destination_id del cancelledTransferOutMovement = origen original (donde debe llegar)
          $this->stockService->moveFromInTransitToDestination(
            $detail['product_id'],
            $cancelledTransferOutMovement->warehouse_id, // Destino original (donde está en tránsito)
            $cancelledTransferOutMovement->warehouse_destination_id, // Origen original (destino final)
            $detail['quantity_received']
          );
        }
      }
    }

    // Marcar la recepción como revertida (cambiar status a STATUS_APPROVED)
    $reception->update([
      'status' => TransferReception::STATUS_APPROVED,
      'reviewed_by' => $reception->received_by,
      'reviewed_at' => now(),
    ]);

    // Actualizar el movimiento de cancelación a APPROVED (ya fue contabilizado en Dynamics)
    $cancelledTransferOutMovement->update([
      'status' => InventoryMovement::STATUS_APPROVED,
    ]);

    // Marcar la guía como procesada (reversión completada)
    $shippingGuide->update([
      'is_received' => true, // Marca que se procesó la reversión
    ]);

    return $reversalMovement;
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
          // Revert stock changes manually (only for PRODUCTO type)
          // 1. Remove stock from destination warehouse (that was added by TRANSFER_IN)
          // 2. Add stock back to in_transit in origin warehouse (to restore TRANSFER_OUT state)

          if ($reception->item_type === TransferReception::ITEM_TYPE_PRODUCT) {
            foreach ($reception->details as $detail) {
              // Skip if product_id is null (service items)
              if (!$detail->product_id) {
                continue;
              }

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
          }

          // Delete the TRANSFER_IN movement
          $transferInMovement->delete();
        }

        // Set TRANSFER_OUT movement back to IN_TRANSIT
        $transferOutMovement->update([
          'status' => InventoryMovement::STATUS_IN_TRANSIT
        ]);
      }

      //marcamos la $shippingGuide como no recibida
      $shippingGuide = $reception->shippingGuide;
      $shippingGuide->update([
        'is_received' => false,
        'received_by' => null,
        'received_date' => null,
      ]);

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
