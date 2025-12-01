<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\InventoryMovementResource;
use App\Http\Services\BaseService;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class InventoryMovementService extends BaseService
{
  protected $stockService;

  public function __construct()
  {
    $this->stockService = new ProductWarehouseStockService();
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      InventoryMovement::class,
      $request,
      InventoryMovement::filters,
      InventoryMovement::sorts,
      InventoryMovementResource::class,
    );
  }

  public function find($id)
  {
    $reception = InventoryMovement::where('id', $id)->first();

    if (!$reception) {
      throw new Exception('Movimiento de Inventario no encontrada');
    }

    return $reception;
  }

  public function show($id)
  {
    return new InventoryMovementResource($this->find($id)->load(['details', 'reference']));
  }

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

  public function createAdjustment(array $data, array $details): InventoryMovement
  {
    DB::beginTransaction();
    try {
      // Validate movement type
      $validTypes = [
        InventoryMovement::TYPE_ADJUSTMENT_IN,
        InventoryMovement::TYPE_ADJUSTMENT_OUT,
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
        'reason_in_out_id' => $data['reason_in_out_id'] ?? null,
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

        // For outbound movements (adjustment_out), validate sufficient stock
        if (in_array($data['movement_type'], [
          InventoryMovement::TYPE_ADJUSTMENT_OUT,
        ])) {
          // Check if stock exists
          if (!$stock) {
            throw new Exception(
              "No se encontró registro de stock para el producto ID {$detail['product_id']} en el almacén especificado"
            );
          }

          // Validate sufficient available quantity
          if ($stock->available_quantity < $detail['quantity']) {
            throw new Exception(
              "Stock insuficiente para producto ID {$detail['product_id']}. " .
              "Stock disponible: {$stock->available_quantity}, Cantidad solicitada: {$detail['quantity']}"
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

  public function updateAdjustment(Mixed $data): InventoryMovement
  {
    DB::beginTransaction();
    try {
      $adjustmentInventory = $this->find($data['id']);

      if ($adjustmentInventory->status === InventoryMovement::STATUS_CANCELLED) {
        throw new Exception('No se puede actualizar un movimiento cancelado');
      }

      // Validate if movement has shipping guide sent to SUNAT (for transfers)
      if ($adjustmentInventory->reference_type === 'App\\Models\\ap\\comercial\\ShippingGuides' && $adjustmentInventory->reference_id) {
        $shippingGuide = $adjustmentInventory->reference;
        if ($shippingGuide && $shippingGuide->is_sunat_registered) {
          throw new Exception('No se puede editar una transferencia cuya guía de remisión ya fue enviada a SUNAT');
        }
      }

      $adjustmentInventory->update([
        'movement_date' => $data['movement_date'] ?? $adjustmentInventory->movement_date,
        'notes' => $data['notes'] ?? $adjustmentInventory->notes,
      ]);

      DB::commit();
      return $adjustmentInventory;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Create warehouse transfer with shipping guide
   * Creates TRANSFER_OUT movement and shipping guide (NOT sent to Nubefact yet)
   * Stock moves to quantity_in_transit
   * TRANSFER_IN will be created when reception is done
   *
   * @param array $transferData Transfer and shipping guide data
   * @param array $details Product details
   * @return array [movement, shipping_guide]
   * @throws Exception
   */
  public function createTransfer(array $transferData, array $details): array
  {
    DB::beginTransaction();
    try {
      // Validate warehouses
      if (!isset($transferData['warehouse_origin_id']) || !isset($transferData['warehouse_destination_id'])) {
        throw new Exception('Debe especificar almacén de origen y destino');
      }

      if ($transferData['warehouse_origin_id'] === $transferData['warehouse_destination_id']) {
        throw new Exception('El almacén de origen y destino deben ser diferentes');
      }

      // Validate details
      if (empty($details)) {
        throw new Exception('Debe proporcionar al menos un producto');
      }

      // Validate stock availability in origin warehouse
      foreach ($details as $detail) {
        $stock = $this->stockService->getStock($detail['product_id'], $transferData['warehouse_origin_id']);

        if (!$stock) {
          throw new Exception(
            "No se encontró registro de stock para el producto ID {$detail['product_id']} en el almacén de origen"
          );
        }

        if ($stock->available_quantity < $detail['quantity']) {
          throw new Exception(
            "Stock insuficiente para producto ID {$detail['product_id']} en almacén de origen. " .
            "Stock disponible: {$stock->available_quantity}, Cantidad solicitada: {$detail['quantity']}"
          );
        }
      }

      // Get info for shipping guide
      $business_partner = BusinessPartners::find($transferData['transmitter_origin_id']);
      $sede_transmitter = Warehouse::find($transferData['warehouse_origin_id'])->sede;
      $sede_receiver = Warehouse::find($transferData['warehouse_destination_id'])->sede;
      $transmitter = BusinessPartnersEstablishment::where('sede_id', $sede_transmitter->id)
        ->where('business_partner_id', $business_partner->id)
        ->first();
      $receiver = BusinessPartnersEstablishment::where('sede_id', $sede_receiver->id)
        ->where('business_partner_id', $business_partner->id)
        ->first();
      $transport_company = BusinessPartners::find($transferData['transport_company_id']);
      $assignedSeries = AssignSalesSeries::find($transferData['document_series_id']);

      // validamos que si $transmitter o $receiver no sean nulos
      if (!$transmitter || !$receiver) {
        throw new Exception('Por favor, configure la sede en el apartado de establecimientos de cliente automotores');
      }

      // Validar que se haya encontrado la serie asignada
      if (!$assignedSeries) {
        throw new Exception('Serie de documentos no encontrada');
      }

      // Generar el siguiente correlativo usando el método centralizado
      $nextCorrelative = ShippingGuides::generateNextCorrelative(
        $transferData['document_series_id'],
        $assignedSeries->correlative_start
      );

      $correlative = $nextCorrelative['correlative'];
      $documentNumber = $assignedSeries->series . '-' . $correlative;

      // Create Shipping Guide (NOT sent to Nubefact yet)
      $shippingGuide = ShippingGuides::create([
        'document_type' => $transferData['document_type'],
        'issuer_type' => ShippingGuides::ISSUER_TYPE_AUTOMOTORES,
        'document_number' => $documentNumber,
        'document_series_id' => $transferData['document_series_id'],
        'series' => $assignedSeries->series,
        'correlative' => $correlative,
        'issue_date' => $transferData['movement_date'] ?? now(),
        'requires_sunat' => true,
        'is_sunat_registered' => false, // NOT sent yet
        'sede_transmitter_id' => $sede_transmitter->id,
        'sede_receiver_id' => $sede_receiver->id,
        'transmitter_id' => $transmitter ? $transmitter->id : null,
        'receiver_id' => $receiver ? $receiver->id : null,
        'driver_name' => $transferData['driver_name'],
        'driver_doc' => $transferData['driver_doc'],
        'license' => $transferData['license'],
        'plate' => $transferData['plate'],
        'transfer_reason_id' => $transferData['transfer_reason_id'],
        'transfer_modality_id' => $transferData['transfer_modality_id'],
        'transport_company_id' => $transferData['transport_company_id'] ?? null,
        'total_packages' => $transferData['total_packages'] ?? null,
        'total_weight' => $transferData['total_weight'] ?? null,
        'origin_ubigeo' => $sede_transmitter ? $sede_transmitter->district->ubigeo : null,
        'origin_address' => $sede_transmitter ? $sede_transmitter->direccion : null,
        'destination_ubigeo' => $sede_receiver ? $sede_receiver->district->ubigeo : null,
        'destination_address' => $sede_receiver ? $sede_receiver->direccion : null,
        'ruc_transport' => $transport_company->num_doc ?? null,
        'company_name_transport' => $transport_company->full_name ?? null,
        'notes' => $transferData['notes'] ?? null,
        'status' => true,
        'created_by' => Auth::id(),
        'type_voucher_id' => SunatConcepts::TYPE_VOUCHER_REMISION_REMITENTE,
      ]);

      // Create TRANSFER_OUT movement (stock goes to in_transit)
      $movementOut = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_type' => InventoryMovement::TYPE_TRANSFER_OUT,
        'movement_date' => $transferData['movement_date'] ?? now(),
        'warehouse_id' => $transferData['warehouse_origin_id'],
        'warehouse_destination_id' => $transferData['warehouse_destination_id'],
        'reason_in_out_id' => $transferData['reason_in_out_id'] ?? null,
        'reference_type' => ShippingGuides::class,
        'reference_id' => $shippingGuide->id,
        'user_id' => Auth::id(),
        'status' => InventoryMovement::STATUS_IN_TRANSIT, // IN TRANSIT (not approved yet)
        'notes' => $transferData['notes'] ?? 'Transferencia en tránsito',
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create details for OUT movement
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($details as $detail) {
        InventoryMovementDetail::create([
          'inventory_movement_id' => $movementOut->id,
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

      // Update OUT movement totals
      $movementOut->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
      ]);

      // Update stock: quantity → quantity_in_transit (not available anymore)
      $this->stockService->moveStockToInTransit($movementOut->fresh('details'));

      DB::commit();
      return [
        'movement' => $movementOut->load(['warehouse', 'warehouseDestination', 'user', 'details.product', 'reference']),
        'shipping_guide' => $shippingGuide->fresh(['sedeTransmitter', 'sedeReceiver', 'transferReason', 'transferModality']),
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update warehouse transfer with simple fields
   * Only updates movement and shipping guide metadata (NOT products/details)
   *
   * @param array $transferData Updated transfer data
   * @param int $movementId Movement ID
   * @return array [movement, shipping_guide]
   * @throws Exception
   */
  public function updateTransfer(array $transferData, int $movementId): array
  {
    DB::beginTransaction();
    try {
      // Find movement
      $movement = $this->find($movementId);

      // Validate movement type
      if ($movement->movement_type !== InventoryMovement::TYPE_TRANSFER_OUT) {
        throw new Exception('Solo se pueden actualizar movimientos de tipo TRANSFER_OUT');
      }

      // Validate movement is not cancelled
      if ($movement->status === InventoryMovement::STATUS_CANCELLED) {
        throw new Exception('No se puede actualizar un movimiento cancelado');
      }

      // Validate shipping guide exists
      if ($movement->reference_type !== ShippingGuides::class || !$movement->reference_id) {
        throw new Exception('No se encontró la guía de remisión asociada a este movimiento');
      }

      $shippingGuide = $movement->reference;

      // Validate shipping guide has NOT been sent to SUNAT
      if ($shippingGuide && $shippingGuide->is_sunat_registered) {
        throw new Exception('No se puede editar una transferencia cuya guía de remisión ya fue enviada a SUNAT');
      }

      // Update inventory movement (only simple fields)
      $movement->update([
        'movement_date' => $transferData['movement_date'] ?? $movement->movement_date,
        'notes' => $transferData['notes'] ?? $movement->notes,
      ]);

      // Update shipping guide (only metadata fields, NOT products)
      $shippingGuideData = [];

      if (isset($transferData['movement_date'])) {
        $shippingGuideData['issue_date'] = $transferData['movement_date'];
      }

      if (isset($transferData['driver_name'])) {
        $shippingGuideData['driver_name'] = $transferData['driver_name'];
      }

      if (isset($transferData['driver_doc'])) {
        $shippingGuideData['driver_doc'] = $transferData['driver_doc'];
      }

      if (isset($transferData['license'])) {
        $shippingGuideData['license'] = $transferData['license'];
      }

      if (isset($transferData['plate'])) {
        $shippingGuideData['plate'] = $transferData['plate'];
      }

      if (isset($transferData['total_packages'])) {
        $shippingGuideData['total_packages'] = $transferData['total_packages'];
      }

      if (isset($transferData['total_weight'])) {
        $shippingGuideData['total_weight'] = $transferData['total_weight'];
      }

      if (isset($transferData['transport_company_id'])) {
        $shippingGuideData['transport_company_id'] = $transferData['transport_company_id'];

        // Update transport company info
        if ($transferData['transport_company_id']) {
          $transport_company = BusinessPartners::find($transferData['transport_company_id']);
          if ($transport_company) {
            $shippingGuideData['ruc_transport'] = $transport_company->num_doc;
            $shippingGuideData['company_name_transport'] = $transport_company->full_name;
          }
        }
      }

      if (isset($transferData['transfer_reason_id'])) {
        $shippingGuideData['transfer_reason_id'] = $transferData['transfer_reason_id'];
      }

      if (isset($transferData['transfer_modality_id'])) {
        $shippingGuideData['transfer_modality_id'] = $transferData['transfer_modality_id'];
      }

      // Update shipping guide notes if provided in transfer data
      if (isset($transferData['notes'])) {
        $shippingGuideData['notes'] = $transferData['notes'];
      }

      if (!empty($shippingGuideData)) {
        $shippingGuide->update($shippingGuideData);
      }

      DB::commit();
      return [
        'movement' => $movement->fresh(['warehouse', 'warehouseDestination', 'user', 'details.product', 'reference']),
        'shipping_guide' => $shippingGuide->fresh(['sedeTransmitter', 'sedeReceiver', 'transferReason', 'transferModality']),
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete warehouse transfer
   * Only allowed if shipping guide has NOT been sent to SUNAT
   * Reverses stock from in_transit back to available
   *
   * @param int $movementId Movement ID
   * @return void
   * @throws Exception
   */
  public function destroyTransfer(int $movementId): void
  {
    DB::beginTransaction();
    try {
      // Find movement
      $movement = $this->find($movementId);

      // Validate movement type
      if ($movement->movement_type !== InventoryMovement::TYPE_TRANSFER_OUT) {
        throw new Exception('Solo se pueden eliminar movimientos de tipo TRANSFER_OUT');
      }

      // Validate shipping guide exists
      if ($movement->reference_type !== ShippingGuides::class || !$movement->reference_id) {
        throw new Exception('No se encontró la guía de remisión asociada a este movimiento');
      }

      $shippingGuide = $movement->reference;

      // Validate shipping guide has NOT been sent to SUNAT
      if ($shippingGuide && $shippingGuide->is_sunat_registered) {
        throw new Exception('No se puede eliminar una transferencia cuya guía de remisión ya fue enviada a SUNAT');
      }

      // Load movement details
      $movement->load('details');

      // Reverse stock: move from in_transit back to available quantity
      foreach ($movement->details as $detail) {
        $stock = $this->stockService->getStock($detail->product_id, $movement->warehouse_id);

        if (!$stock) {
          throw new Exception(
            "No se encontró registro de stock para el producto ID {$detail->product_id} en el almacén de origen"
          );
        }

        // Validate that we have enough in_transit quantity
        if ($stock->quantity_in_transit < $detail->quantity) {
          throw new Exception(
            "Cantidad en tránsito insuficiente para producto ID {$detail->product_id}. " .
            "En tránsito: {$stock->quantity_in_transit}, Cantidad a revertir: {$detail->quantity}"
          );
        }

        // Move stock back from in_transit to available
        $stock->update([
          'quantity_in_transit' => $stock->quantity_in_transit - $detail->quantity,
          'quantity' => $stock->quantity + $detail->quantity,
          'available_quantity' => $stock->available_quantity + $detail->quantity,
        ]);
      }

      // Delete shipping guide
      if ($shippingGuide) {
        $shippingGuide->delete();
      }

      // Delete movement details
      $movement->details()->delete();

      // Delete movement
      $movement->delete();

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  private function getDefaultNotes(string $movementType): string
  {
    $notes = [
      InventoryMovement::TYPE_ADJUSTMENT_OUT => 'Ajuste negativo de inventario',
      InventoryMovement::TYPE_ADJUSTMENT_IN => 'Ajuste positivo de inventario',
    ];

    return $notes[$movementType] ?? 'Ajuste de inventario';
  }

  public function reverseStockFromMovement($id)
  {
    DB::beginTransaction();
    try {
      $adjustmentInventory = $this->find($id);

      if ($adjustmentInventory->status === InventoryMovement::STATUS_CANCELLED) {
        throw new Exception('No se puede revertir el stock de un movimiento cancelado');
      }

      $movement = $adjustmentInventory->load('details');
      $updatedStocks = [];

      foreach ($movement->details as $detail) {
        $productId = $detail->product_id;
        $quantity = $detail->quantity;

        // Reverse the stock change
        // If it was an INBOUND movement (added stock), we need to REMOVE it
        // If it was an OUTBOUND movement (removed stock), we need to ADD it back
        if ($movement->is_inbound) {
          // INBOUND: Remove the stock that was added
          $stock = $this->stockService->removeStock($productId, $movement->warehouse_id, abs($quantity));
          $updatedStocks[] = $stock;
        } else {
          // OUTBOUND: Add back the stock that was removed
          $stock = $this->stockService->addStock($productId, $movement->warehouse_id, abs($quantity));
          $updatedStocks[] = $stock;
        }

        // Handle transfers (TRANSFER_OUT and TRANSFER_IN)
        if ($movement->movement_type === InventoryMovement::TYPE_TRANSFER_IN && $movement->warehouse_destination_id) {
          // For transfer in, also reverse destination warehouse stock
          $destinationStock = $this->stockService->removeStock($productId, $movement->warehouse_destination_id, abs($quantity));
          $updatedStocks[] = $destinationStock;
        }
      }

      $movement->delete();

      DB::commit();
      return response()->json(['message' => 'Recepción eliminada correctamente. Se han revertido todas las cantidades y movimientos de inventario.']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get movement history for a specific product in a warehouse
   * Returns all inventory movements for a product in a specific warehouse
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param Request $request Request with filters
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function getProductMovementHistory(int $productId, int $warehouseId, Request $request)
  {
    // Base query: get all movements that have details for this product in this warehouse
    $query = InventoryMovement::query()
      ->whereHas('details', function ($q) use ($productId) {
        $q->where('product_id', $productId);
      })
      ->where(function ($q) use ($warehouseId) {
        $q->where('warehouse_id', $warehouseId)
          ->orWhere('warehouse_destination_id', $warehouseId);
      })
      ->with([
        'details' => function ($q) use ($productId) {
          $q->where('product_id', $productId)
            ->with('product');
        },
        'warehouse',
        'warehouseDestination',
        'user',
        'reasonInOut',
        'reference'
      ])
      ->orderBy('movement_date', 'desc')
      ->orderBy('created_at', 'desc');

    // Apply date range filter if provided
    if ($request->has('date_from')) {
      $query->where('movement_date', '>=', $request->date_from);
    }

    if ($request->has('date_to')) {
      $query->where('movement_date', '<=', $request->date_to);
    }

    // Apply movement type filter if provided
    if ($request->has('movement_type')) {
      $query->where('movement_type', $request->movement_type);
    }

    // Apply status filter if provided
    if ($request->has('status')) {
      $query->where('status', $request->status);
    }

    // Paginate results
    $perPage = $request->get('per_page', 15);
    $movements = $query->paginate($perPage);

    return InventoryMovementResource::collection($movements);
  }
}
