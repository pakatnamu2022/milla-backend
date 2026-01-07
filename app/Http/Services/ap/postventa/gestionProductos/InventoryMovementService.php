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
use App\Models\ap\postventa\taller\ApOrderQuotations;
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
        'notes' => "Ingreso por recepción {$reception->reception_number} de {$reception->purchaseOrder->number}",
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
              ? "Item de - {$detail->product->name}"
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
      if ($adjustmentInventory->reference_type === ShippingGuides::class && $adjustmentInventory->reference_id) {
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
      if ((int)$transferData['transfer_modality_id'] === SunatConcepts::TYPE_TRANSPORTATION_PUBLICO) {
        if ($transferData['transport_company_id'] === null) {
          throw new Exception('Modalidad de "TRASPORTE PUBLICO" el proveedor de transporte es obligatorio (RUC)');
        }
      } else {
        if ($transferData['driver_doc'] === null) {
          throw new Exception('Modalidad de "TRANSPORTE PRIVADO" el dni del conductor, licencia, placa y nombres deben ser obligatorios');
        }
      }

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

      // Validate stock availability in origin warehouse (only for PRODUCTO type)
      $itemType = $transferData['item_type'] ?? 'PRODUCTO';

      if ($itemType === 'PRODUCTO') {
        foreach ($details as $detail) {
          // Skip validation if it's a service (description instead of product_id)
          if (!isset($detail['product_id'])) {
            continue;
          }

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
        'item_type' => $itemType,
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
        // For SERVICIO type, product_id may not be set
        $productId = isset($detail['product_id']) ? $detail['product_id'] : null;
        $notes = $detail['notes'] ?? null;

        InventoryMovementDetail::create([
          'inventory_movement_id' => $movementOut->id,
          'product_id' => $productId,
          'quantity' => $detail['quantity'],
          'unit_cost' => $detail['unit_cost'] ?? 0,
          'total_cost' => $detail['quantity'] * ($detail['unit_cost'] ?? 0),
          'batch_number' => $detail['batch_number'] ?? null,
          'expiration_date' => $detail['expiration_date'] ?? null,
          'notes' => $notes,
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
      // Only update stock for PRODUCTO type, not for SERVICIO
      if ($itemType === 'PRODUCTO') {
        $this->stockService->moveStockToInTransit($movementOut->fresh('details'));
      }

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

      if ((int)$transferData['transfer_modality_id'] === SunatConcepts::TYPE_TRANSPORTATION_PUBLICO) {
        if ($transferData['transport_company_id'] === null) {
          throw new Exception('Modalidad de transporte publico el proveedor de transporte es obligatorio');
        }
      } else {
        if ($transferData['driver_doc'] === null) {
          throw new Exception('Modalidad de transporte privado el dni del conductor, licencia, placa y nombres deben ser obligatorios');
        }
      }

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
      // Only reverse stock for PRODUCTO type, not for SERVICIO
      if ($movement->item_type === 'PRODUCTO') {
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
   * Includes quantity_in, quantity_out, and balance (running balance)
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param Request $request Request with filters
   * @return \Illuminate\Http\JsonResponse
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
      ]);

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

    // Get all movements ordered chronologically (ASC) to show oldest first and balance at the end
    $allMovements = $query->orderBy('movement_date', 'asc')
      ->orderBy('created_at', 'asc')
      ->get();

    // Calculate quantity_in, quantity_out, and running balance for each movement
    $runningBalance = 0;

    // Get current stock to calculate initial balance
    $currentStock = $this->stockService->getStock($productId, $warehouseId);

    // If we have movements, we need to calculate the initial balance
    // by subtracting all movements from current stock
    if ($allMovements->isNotEmpty() && $currentStock) {
      $totalIn = 0;
      $totalOut = 0;

      foreach ($allMovements as $movement) {
        $quantity = $movement->details->first()->quantity ?? 0;

        if ($movement->is_inbound) {
          $totalIn += $quantity;
        } else {
          $totalOut += $quantity;
        }
      }

      // Initial balance = current stock - (total in - total out from filtered movements)
      $runningBalance = $currentStock->quantity - ($totalIn - $totalOut);
    }

    // Add calculated fields to each movement
    $movementsWithBalance = $allMovements->map(function ($movement) use (&$runningBalance) {
      $quantity = $movement->details->first()->quantity ?? 0;

      // Determine if this is an inbound or outbound movement for this warehouse
      $quantityIn = 0;
      $quantityOut = 0;

      if ($movement->is_inbound) {
        $quantityIn = $quantity;
        $runningBalance += $quantity;
      } else {
        $quantityOut = $quantity;
        $runningBalance -= $quantity;
      }

      // Add calculated fields to the movement object
      $movement->quantity_in = $quantityIn;
      $movement->quantity_out = $quantityOut;
      $movement->balance = $runningBalance;

      return $movement;
    });

    // Manual pagination
    $perPage = $request->get('per_page', 15);
    $page = $request->get('page', 1);
    $total = $movementsWithBalance->count();
    $lastPage = (int)ceil($total / $perPage);

    $paginatedMovements = $movementsWithBalance->slice(($page - 1) * $perPage, $perPage)->values();

    // Transform to resource
    $resourceCollection = InventoryMovementResource::collection($paginatedMovements);

    // Build pagination response
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : null;
    $to = $total > 0 ? min($from + $perPage - 1, $total) : null;

    $baseUrl = $request->url();
    $queryParams = $request->query();

    $first = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
    $last = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $lastPage]));
    $prev = $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null;
    $next = $page < $lastPage ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null;

    return response()->json([
      'data' => $resourceCollection,
      'links' => [
        'first' => $first,
        'last' => $last,
        'prev' => $prev,
        'next' => $next,
      ],
      'meta' => [
        'current_page' => $page,
        'from' => $from,
        'last_page' => $lastPage,
        'path' => $baseUrl,
        'per_page' => $perPage,
        'to' => $to,
        'total' => $total,
      ]
    ]);
  }

  /**
   * Get kardex of all inventory movements
   * Returns all inventory movements with optional warehouse filter
   *
   * @param Request $request Request with filters (warehouse_id optional)
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function getKardex(Request $request)
  {
    // Base query: get all movements
    $query = InventoryMovement::query()
      ->with([
        'details.product.category',
        'warehouse',
        'warehouseDestination',
        'user',
        'reasonInOut',
        'reference'
      ])
      ->orderBy('movement_date', 'desc')
      ->orderBy('created_at', 'desc');

    // Apply warehouse filter if provided (can be origin or destination)
    if ($request->has('warehouse_id')) {
      $warehouseId = $request->warehouse_id;
      $query->where(function ($q) use ($warehouseId) {
        $q->where('warehouse_id', $warehouseId)
          ->orWhere('warehouse_destination_id', $warehouseId);
      });
    }

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

    // Apply search filter (by movement number or notes)
    if ($request->has('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('movement_number', 'LIKE', "%{$search}%")
          ->orWhere('notes', 'LIKE', "%{$search}%");
      });
    }

    // Paginate results
    $perPage = $request->get('per_page', 15);
    $movements = $query->paginate($perPage);

    return InventoryMovementResource::collection($movements);
  }

  public function createWorkOrderPartOutbound($workOrderPart): InventoryMovement
  {
    DB::beginTransaction();
    try {
      // Validar que hay stock disponible
      $stock = $this->stockService->getStock($workOrderPart->product_id, $workOrderPart->warehouse_id);

      if (!$stock) {
        throw new Exception('No se encontró registro de stock para el producto en el almacén especificado');
      }

      if ($stock->available_quantity < $workOrderPart->quantity_used) {
        throw new Exception(
          "Stock insuficiente. Disponible: {$stock->available_quantity}, Requerido: {$workOrderPart->quantity_used}"
        );
      }

      // Crear movimiento de inventario de salida
      $movement = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_type' => InventoryMovement::TYPE_ADJUSTMENT_OUT,
        'movement_date' => now(),
        'warehouse_id' => $workOrderPart->warehouse_id,
        'reference_type' => get_class($workOrderPart),
        'reference_id' => $workOrderPart->id,
        'user_id' => Auth::id(),
        'status' => InventoryMovement::STATUS_APPROVED,
        'notes' => "Salida por uso en Orden de Trabajo #{$workOrderPart->workOrder->correlative} - {$workOrderPart->product->name}",
        'total_items' => 1,
        'total_quantity' => $workOrderPart->quantity_used,
      ]);

      // Crear detalle del movimiento
      InventoryMovementDetail::create([
        'inventory_movement_id' => $movement->id,
        'product_id' => $workOrderPart->product_id,
        'quantity' => $workOrderPart->quantity_used,
        'unit_cost' => $workOrderPart->unit_cost,
        'total_cost' => $workOrderPart->quantity_used * $workOrderPart->unit_cost,
        'notes' => "Repuesto usado en OT #{$workOrderPart->workOrder->correlative}",
      ]);

      // Actualizar el stock (restar la cantidad usada)
      $this->stockService->updateStockFromMovement($movement->fresh('details'));

      DB::commit();
      return $movement;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Create sale outbound movement from quotation
   * Creates SALE movement referencing an ApOrderQuotation
   * Updates stock automatically
   *
   * @param int $quotationId Quotation ID
   * @return InventoryMovement
   * @throws Exception
   */
  public function createSaleFromQuotation(int $quotationId): InventoryMovement
  {
    DB::beginTransaction();
    try {
      // Find quotation with details
      $quotation = ApOrderQuotations::with(['details.product', 'sede'])->find($quotationId);

      if (!$quotation) {
        throw new Exception('Cotización no encontrada');
      }

      // Validamos que la cotización no haya generado ya una salida de almacén
      if ($quotation->output_generation_warehouse) {
        throw new Exception('La cotización ya ha generado una salida de almacén previamente');
      }

      // Get warehouse from sede
      $warehouse = Warehouse::where('sede_id', $quotation->sede_id)
        ->where('is_physical_warehouse', 1)
        ->where('status', 1)
        ->first();

      if (!$warehouse) {
        throw new Exception('No se encontró almacén asociado a la sede de la cotización');
      }

      // Filter only product items (exclude labor)
      $productDetails = $quotation->details->where('item_type', '!=', 'labor')->where('product_id', '!=', null);

      if ($productDetails->isEmpty()) {
        throw new Exception('La cotización no contiene productos para generar salida de inventario');
      }

      // Validate stock availability for all products
      foreach ($productDetails as $detail) {
        $stock = $this->stockService->getStock($detail->product_id, $warehouse->id);

        if (!$stock) {
          throw new Exception(
            "No se encontró registro de stock para el producto '{$detail->product->name}' en el almacén"
          );
        }

        if ($stock->available_quantity < $detail->quantity) {
          throw new Exception(
            "Stock insuficiente para producto '{$detail->product->name}'. " .
            "Disponible: {$stock->available_quantity}, Requerido: {$detail->quantity}"
          );
        }
      }

      // Create movement header
      $movement = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_type' => InventoryMovement::TYPE_SALE,
        'movement_date' => now(),
        'warehouse_id' => $warehouse->id,
        'reference_type' => ApOrderQuotations::class,
        'reference_id' => $quotation->id,
        'user_id' => Auth::id(),
        'status' => InventoryMovement::STATUS_APPROVED,
        'notes' => "Salida por venta - Cotización {$quotation->quotation_number}",
        'total_items' => 0,
        'total_quantity' => 0,
      ]);

      // Create movement details
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($productDetails as $detail) {
        InventoryMovementDetail::create([
          'inventory_movement_id' => $movement->id,
          'product_id' => $detail->product_id,
          'quantity' => $detail->quantity,
          'unit_cost' => $detail->unit_price,
          'total_cost' => $detail->total_amount,
          'notes' => "Venta cotización {$quotation->quotation_number} - {$detail->description}",
        ]);

        $totalItems++;
        $totalQuantity += $detail->quantity;
      }

      // Update movement totals
      $movement->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
      ]);

      // Update ApOrderQuotations output_generation_warehouse
      $quotation->update(['output_generation_warehouse' => true]);

      // Update stock automatically
      $this->stockService->updateStockFromMovement($movement->fresh('details'));

      DB::commit();
      return $movement->load(['warehouse', 'user', 'details.product', 'reference']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
