<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseReceptionResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\postventa\taller\ApSupplierOrder;
use App\Models\ap\postventa\taller\ApSupplierOrderDetails;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReceptionService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseReception::class,
      $request,
      PurchaseReception::filters,
      PurchaseReception::sorts,
      PurchaseReceptionResource::class,
    );
  }

  public function find($id)
  {
    $reception = PurchaseReception::where('id', $id)->first();

    if (!$reception) {
      throw new Exception('Recepción no encontrada');
    }

    return $reception;
  }

  public function store(Mixed $data)
  {
    DB::beginTransaction();
    try {
      // Validate supplier order exists
      $supplierOrder = ApSupplierOrder::findOrFail($data['ap_supplier_order_id']);

      // VALIDACIÓN 1: Verificar que la orden no tenga recepciones activas
      if ($supplierOrder->hasActiveReceptions()) {
        throw new Exception('Esta orden de compra ya tiene una recepción activa. No se permite recepcionar nuevamente.');
      }

      // VALIDACIÓN 2: La fecha de recepción no puede ser anterior a la fecha de la orden
      $receptionDate = Carbon::parse($data['reception_date']);
      $orderDate = Carbon::parse($supplierOrder->order_date);
      if ($receptionDate->lt($orderDate)) {
        throw new Exception('La fecha de recepción no puede ser anterior a la fecha de la orden de compra (' . $orderDate->format('Y-m-d') . ')');
      }

      // Generate reception number
      $data['reception_number'] = $this->generateReceptionNumber();

      // Set received by if not provided
      $data['received_by'] = Auth::id();

      // Create reception header
      $details = $data['details'];
      unset($data['details']);

      $reception = PurchaseReception::create($data);

      // Process details
      $totalItems = 0;
      $totalQuantity = 0;

      foreach ($details as $detail) {
        // Validate detail
        $this->validateReceptionDetail($detail, $supplierOrder);

        // Set reception id
        $detail['purchase_reception_id'] = $reception->id;

        // Create detail
        PurchaseReceptionDetail::create($detail);

        $totalItems++;
        $totalQuantity += $detail['quantity_received'];
      }

      // CÁLCULO AUTOMÁTICO DE STATUS:
      // Verificar si todos los items de la orden de proveedor están completamente recibidos
      $allItemsFullyReceived = $this->checkIfAllItemsReceived($supplierOrder, $reception->id);

      // - APPROVED: Si se recepcionó todo lo pedido
      // - INCOMPLETE: Si falta mercancía
      $status = $allItemsFullyReceived ? 'APPROVED' : 'INCOMPLETE';

      // Update reception totals and status
      $reception->update([
        'total_items' => $totalItems,
        'total_quantity' => $totalQuantity,
        'status' => $status,
      ]);

      // OPCIONAL: Notificar usuarios si la orden tiene solicitudes vinculadas
      if ($reception->supplierOrder) {
        $this->notifyRequestUsers($reception->supplierOrder);
      }

      DB::commit();
      return new PurchaseReceptionResource($reception->load([
        'supplierOrder',
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

      // VALIDACIÓN: La fecha de recepción no puede ser anterior a la fecha de la orden
      if (isset($data['reception_date'])) {
        $receptionDate = Carbon::parse($data['reception_date']);

        // Verificar contra la orden de proveedor (siempre debe existir)
        if ($reception->supplierOrder) {
          $orderDate = Carbon::parse($reception->supplierOrder->order_date);
          if ($receptionDate->lt($orderDate)) {
            throw new Exception('La fecha de recepción no puede ser anterior a la fecha de la orden de compra (' . $orderDate->format('Y-m-d') . ')');
          }
        }

        // Si también tiene purchase order, verificar contra esa fecha
        if ($reception->purchaseOrder) {
          if ($receptionDate->lt($reception->purchaseOrder->emission_date)) {
            throw new Exception('La fecha de recepción no puede ser anterior a la fecha de emisión de la orden de compra (' . $reception->purchaseOrder->emission_date->format('Y-m-d') . ')');
          }
        }
      }

      // Update only reception header fields
      $reception->update($data);

      DB::commit();
      return new PurchaseReceptionResource($reception->fresh([
        'supplierOrder',
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

      // VALIDACIÓN: No permitir eliminar si ya tiene PurchaseOrder (factura) asociada
      if ($reception->purchase_order_id) {
        throw new Exception('No se puede eliminar una recepción que ya tiene una factura asociada. Debe eliminar primero la factura.');
      }

      // Delete reception (soft delete) - details will be deleted automatically via boot method
      $reception->delete();

      DB::commit();
      return response()->json(['message' => 'Recepción eliminada correctamente.']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }


  /**
   * Check if all items from supplier order are fully received
   */
  protected function checkIfAllItemsReceived($supplierOrder, $currentReceptionId)
  {
    // Obtener todos los items de la orden de proveedor
    $supplierOrderDetails = $supplierOrder->details;

    foreach ($supplierOrderDetails as $orderDetail) {
      // Calcular cuánto se ha recibido de este producto (incluyendo la recepción actual)
      $totalReceived = PurchaseReceptionDetail::whereHas('reception', function ($query) use ($supplierOrder) {
        $query->where('ap_supplier_order_id', $supplierOrder->id)
          ->whereNull('deleted_at');
      })
        ->where('product_id', $orderDetail->product_id)
        ->where('reception_type', 'ORDERED')
        ->sum('quantity_received');

      // Si algún item no está completamente recibido, retornar false
      if ($totalReceived < $orderDetail->quantity) {
        return false;
      }
    }

    return true;
  }

  /**
   * Validate reception detail
   */
  protected function validateReceptionDetail($detail, $supplierOrder)
  {
    // BONUS/GIFT/SAMPLE must NOT have purchase_order_item_id
    if (in_array($detail['reception_type'], ['BONUS', 'GIFT', 'SAMPLE']) && !empty($detail['purchase_order_item_id'])) {
      throw new Exception('Los productos BONUS/GIFT/SAMPLE no deben tener purchase_order_item_id');
    }

    // observed_quantity must be less than or equal to quantity_received
    $observedQuantity = $detail['observed_quantity'] ?? 0;
    $quantityReceived = $detail['quantity_received'];

    if ($observedQuantity > $quantityReceived) {
      throw new Exception('La cantidad observada no puede ser mayor a la cantidad recibida');
    }

    // If observed_quantity > 0, must have reason_observation
    if ($observedQuantity > 0 && empty($detail['reason_observation'])) {
      throw new Exception('Debe indicar la razón de la observación cuando hay productos observados');
    }

    // Validate that we don't receive more than ordered (for ORDERED type)
    if ($detail['reception_type'] === 'ORDERED') {
      $productId = $detail['product_id'];

      // Buscar el producto en los detalles de la orden de proveedor
      $supplierOrderDetail = ApSupplierOrderDetails::where('ap_supplier_order_id', $supplierOrder->id)
        ->where('product_id', $productId)
        ->first();

      if (!$supplierOrderDetail) {
        throw new Exception("El producto ID {$productId} no está en la orden de compra");
      }

      // Calcular cuánto ya se ha recibido de este producto
      $alreadyReceived = PurchaseReceptionDetail::whereHas('reception', function ($query) use ($supplierOrder) {
        $query->where('ap_supplier_order_id', $supplierOrder->id)
          ->whereNull('deleted_at');
      })
        ->where('product_id', $productId)
        ->where('reception_type', 'ORDERED')
        ->sum('quantity_received');

      $quantityAccepted = $quantityReceived - $observedQuantity;
      $totalThatWillBeReceived = $alreadyReceived + $quantityAccepted;

      if ($totalThatWillBeReceived > $supplierOrderDetail->quantity) {
        throw new Exception("No puede recibir más de lo ordenado para el producto ID {$productId}. Ordenado: {$supplierOrderDetail->quantity}, Ya recibido: {$alreadyReceived}, Intenta recibir: {$quantityAccepted}");
      }
    }
  }

  /**
   * Generate unique reception number
   */
  protected function generateReceptionNumber()
  {
    $year = date('Y');
    $lastReception = PurchaseReception::withTrashed()
      ->whereYear('created_at', $year)
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
   * Get receptions by supplier order
   */
  public function getBySupplierOrder($supplierOrderId)
  {
    $receptions = PurchaseReception::where('ap_supplier_order_id', $supplierOrderId)
      ->with(['supplierOrder', 'purchaseOrder', 'warehouse', 'receivedByUser', 'reviewedByUser', 'details.product'])
      ->get();

    return PurchaseReceptionResource::collection($receptions);
  }

  /**
   * Get receptions by purchase order (factura)
   */
  public function getByPurchaseOrder($purchaseOrderId)
  {
    $receptions = PurchaseReception::where('purchase_order_id', $purchaseOrderId)
      ->with(['supplierOrder', 'purchaseOrder', 'warehouse', 'receivedByUser', 'reviewedByUser', 'details.product'])
      ->get();

    return PurchaseReceptionResource::collection($receptions);
  }

  /**
   * OPCIONAL: Notificar a los usuarios que solicitaron productos cuando llega la orden
   * Solo notifica si la orden tiene solicitudes vinculadas
   * @param ApSupplierOrder $supplierOrder
   * @return void
   */
  protected function notifyRequestUsers(ApSupplierOrder $supplierOrder): void
  {
    $requestDetailsCount = $supplierOrder->requestDetails()->count();

    if ($requestDetailsCount === 0) {
      return;
    }

    // Obtener usuarios únicos a notificar con sus correos
    $usersToNotify = $supplierOrder->getUsersToNotify();

    if ($usersToNotify->isEmpty()) {
      return;
    }

    $supplierOrder->requestDetails()->update(['status' => 'received']);

    $emailService = new EmailService();

    // Agrupar por email para enviar un solo correo por usuario
    $groupedByEmail = $usersToNotify->groupBy('email');

    foreach ($groupedByEmail as $email => $userRequests) {
      try {
        // Obtener el primer registro para obtener el nombre del usuario
        $firstUser = $userRequests->first();
        $userName = $firstUser['user_name'] ?? 'Usuario';

        // Recopilar todas las solicitudes de este usuario
        $requestNumbers = $userRequests->pluck('request_number')->unique()->toArray();

        // Usar número de orden de proveedor o PurchaseOrder si existe
        $orderNumber = $supplierOrder->apPurchaseOrder
          ? $supplierOrder->apPurchaseOrder->number
          : $supplierOrder->order_number;

        $emailConfig = [
          'to' => $email,
          'subject' => 'Pedido Recibido - Orden de Compra ' . $orderNumber,
          'template' => 'emails.purchase-order-received',
          'data' => [
            'badge' => 'Pedido Recibido',
            'title' => 'Pedido Recibido en Almacén',
            'subtitle' => 'Los productos de tu solicitud ya están disponibles',
            'user_name' => $userName,
            'purchase_order_number' => $orderNumber,
            'request_numbers' => $requestNumbers,
            'date' => now()->format('d/m/Y H:i'),
            'company_name' => 'Grupo Pakatnamu',
            'contact_info' => 'almacen@grupopakatnamu.com'
          ]
        ];

        // Enviar correo usando cola de trabajo
        $emailService->queue($emailConfig);
      } catch (\Exception $e) {
        \Log::error('Error al enviar notificación de recepción de orden de compra a ' . $email . ': ' . $e->getMessage());
      }
    }
  }
}
