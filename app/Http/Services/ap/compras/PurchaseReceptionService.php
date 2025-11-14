<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseReceptionResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReceptionService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        $query = PurchaseReception::query();

        // Eager loading
        $query->with([
            'purchaseOrder.supplier',
            'warehouse',
            'receivedByUser',
            'reviewedByUser',
            'details.product'
        ]);

        return $this->getFilteredResults(
            PurchaseReception::class,
            $request,
            PurchaseReception::filters,
            PurchaseReception::sorts,
            PurchaseReceptionResource::class,
            $query
        );
    }

    public function find($id)
    {
        $reception = PurchaseReception::with([
            'purchaseOrder.supplier',
            'purchaseOrder.items.product',
            'warehouse',
            'receivedByUser',
            'reviewedByUser',
            'details.product',
            'details.purchaseOrderItem'
        ])->where('id', $id)->first();

        if (!$reception) {
            throw new Exception('Recepción no encontrada');
        }

        return $reception;
    }

    public function store(Mixed $data)
    {
        DB::beginTransaction();
        try {
            // Validate purchase order exists
            $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);

            // Generate reception number
            $data['reception_number'] = $this->generateReceptionNumber();

            // Set received by if not provided
            if (!isset($data['received_by'])) {
                $data['received_by'] = Auth::id();
            }

            // Set initial status
            $data['status'] = 'PENDING_REVIEW';

            // Create reception header
            $details = $data['details'];
            unset($data['details']);

            $reception = PurchaseReception::create($data);

            // Process details
            $totalItems = 0;
            $totalQuantity = 0;
            $hasAllItemsFullyReceived = true;

            foreach ($details as $detail) {
                // Validate detail
                $this->validateReceptionDetail($detail, $purchaseOrder);

                // Calculate total cost
                $detail['total_cost'] = $detail['quantity_accepted'] * ($detail['unit_cost'] ?? 0);

                // Set reception id
                $detail['purchase_reception_id'] = $reception->id;

                // Create detail
                PurchaseReceptionDetail::create($detail);

                $totalItems++;
                $totalQuantity += $detail['quantity_accepted'];

                // Check if OC item is fully received (only for ORDERED items)
                if ($detail['reception_type'] === 'ORDERED' && $detail['purchase_order_item_id']) {
                    $orderItem = PurchaseOrderItem::find($detail['purchase_order_item_id']);
                    if ($orderItem) {
                        $newQuantityReceived = $orderItem->quantity_received + $detail['quantity_accepted'];
                        if ($newQuantityReceived < $orderItem->quantity) {
                            $hasAllItemsFullyReceived = false;
                        }
                    }
                }
            }

            // Update reception totals
            $reception->update([
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'reception_type' => $hasAllItemsFullyReceived ? 'COMPLETE' : 'PARTIAL'
            ]);

            DB::commit();
            return new PurchaseReceptionResource($reception->load([
                'purchaseOrder',
                'warehouse',
                'receivedByUser',
                'details.product'
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show($id)
    {
        return new PurchaseReceptionResource($this->find($id));
    }

    public function update(Mixed $data)
    {
        DB::beginTransaction();
        try {
            $reception = $this->find($data['id']);

            // Only allow update if status is PENDING_REVIEW
            if ($reception->status !== 'PENDING_REVIEW') {
                throw new Exception('Solo se pueden modificar recepciones pendientes de revisión');
            }

            $reception->update($data);

            DB::commit();
            return new PurchaseReceptionResource($reception->load([
                'purchaseOrder',
                'warehouse',
                'receivedByUser',
                'details.product'
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $reception = $this->find($id);

            // Only allow delete if status is PENDING_REVIEW
            if ($reception->status !== 'PENDING_REVIEW') {
                throw new Exception('Solo se pueden eliminar recepciones pendientes de revisión');
            }

            $reception->delete();

            DB::commit();
            return response()->json(['message' => 'Recepción eliminada correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve or reject a reception
     */
    public function approveReception($id, $approved, $notes = null)
    {
        DB::beginTransaction();
        try {
            $reception = $this->find($id);

            // Only allow approve if status is PENDING_REVIEW
            if ($reception->status !== 'PENDING_REVIEW') {
                throw new Exception('Esta recepción ya fue revisada anteriormente');
            }

            if ($approved) {
                // Approve reception
                $this->processApprovedReception($reception);
                $reception->status = 'APPROVED';
            } else {
                // Reject reception
                $reception->status = 'REJECTED';
            }

            $reception->reviewed_by = Auth::id();
            $reception->reviewed_at = now();
            if ($notes) {
                $reception->notes = ($reception->notes ?? '') . "\n\nRevisión: " . $notes;
            }
            $reception->save();

            DB::commit();
            return new PurchaseReceptionResource($reception->load([
                'purchaseOrder',
                'warehouse',
                'receivedByUser',
                'reviewedByUser',
                'details.product'
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process approved reception (update OC items quantities)
     */
    protected function processApprovedReception($reception)
    {
        foreach ($reception->details as $detail) {
            // Only update OC items for ORDERED type
            if ($detail->reception_type === 'ORDERED' && $detail->purchase_order_item_id) {
                $orderItem = PurchaseOrderItem::find($detail->purchase_order_item_id);
                if ($orderItem) {
                    $orderItem->quantity_received += $detail->quantity_accepted;
                    $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_received;
                    $orderItem->save();
                }
            }

            // TODO: Create inventory movement
            // TODO: Update product_warehouse_stock
        }
    }

    /**
     * Validate reception detail
     */
    protected function validateReceptionDetail($detail, $purchaseOrder)
    {
        // ORDERED type must have purchase_order_item_id
        if ($detail['reception_type'] === 'ORDERED' && empty($detail['purchase_order_item_id'])) {
            throw new Exception('Los productos ORDERED deben tener purchase_order_item_id');
        }

        // BONUS/GIFT/SAMPLE must NOT have purchase_order_item_id
        if (in_array($detail['reception_type'], ['BONUS', 'GIFT', 'SAMPLE']) && !empty($detail['purchase_order_item_id'])) {
            throw new Exception('Los productos BONUS/GIFT/SAMPLE no deben tener purchase_order_item_id');
        }

        // BONUS/GIFT/SAMPLE must have unit_cost = 0 and is_charged = false
        if (in_array($detail['reception_type'], ['BONUS', 'GIFT', 'SAMPLE'])) {
            if (($detail['unit_cost'] ?? 0) != 0) {
                throw new Exception('Los productos BONUS/GIFT/SAMPLE deben tener unit_cost = 0');
            }
            if (($detail['is_charged'] ?? true) != false) {
                throw new Exception('Los productos BONUS/GIFT/SAMPLE deben tener is_charged = false');
            }
        }

        // quantity_accepted + quantity_rejected must equal quantity_received
        $quantityAccepted = $detail['quantity_accepted'];
        $quantityRejected = $detail['quantity_rejected'] ?? 0;
        $quantityReceived = $detail['quantity_received'];

        if (($quantityAccepted + $quantityRejected) != $quantityReceived) {
            throw new Exception('La suma de cantidad aceptada y rechazada debe ser igual a la cantidad recibida');
        }

        // If quantity_rejected > 0, must have rejection_reason
        if ($quantityRejected > 0 && empty($detail['rejection_reason'])) {
            throw new Exception('Debe indicar la razón del rechazo cuando hay productos rechazados');
        }

        // Validate that we don't receive more than ordered (for ORDERED type)
        if ($detail['reception_type'] === 'ORDERED' && !empty($detail['purchase_order_item_id'])) {
            $orderItem = PurchaseOrderItem::find($detail['purchase_order_item_id']);
            if ($orderItem) {
                $totalThatWillBeReceived = $orderItem->quantity_received + $quantityAccepted;
                if ($totalThatWillBeReceived > $orderItem->quantity) {
                    throw new Exception("No puede recibir más de lo ordenado para el producto ID {$detail['product_id']}. Ordenado: {$orderItem->quantity}, Ya recibido: {$orderItem->quantity_received}, Intenta recibir: {$quantityAccepted}");
                }
            }
        }
    }

    /**
     * Generate unique reception number
     */
    protected function generateReceptionNumber()
    {
        $year = date('Y');
        $lastReception = PurchaseReception::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $correlative = 1;
        if ($lastReception) {
            // Extract number from REC-2025-0001
            $parts = explode('-', $lastReception->reception_number);
            if (count($parts) === 3) {
                $correlative = intval($parts[2]) + 1;
            }
        }

        return sprintf('REC-%s-%04d', $year, $correlative);
    }

    /**
     * Get pending review receptions
     */
    public function getPendingReview()
    {
        $receptions = PurchaseReception::pendingReview()
            ->with(['purchaseOrder.supplier', 'warehouse', 'receivedByUser', 'details.product'])
            ->get();

        return PurchaseReceptionResource::collection($receptions);
    }

    /**
     * Get receptions by purchase order
     */
    public function getByPurchaseOrder($purchaseOrderId)
    {
        $receptions = PurchaseReception::byPurchaseOrder($purchaseOrderId)
            ->with(['warehouse', 'receivedByUser', 'reviewedByUser', 'details.product'])
            ->get();

        return PurchaseReceptionResource::collection($receptions);
    }
}