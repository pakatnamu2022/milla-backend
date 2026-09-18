<?php

namespace App\Services\Billing;

use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use App\Models\ap\facturacion\LinkPurchaseSaleTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class AssociatePurchaseTraverseService
{
  /**
   * Asociar items de travesía de una venta con items de compra
   *
   * @param int $electronicDocumentId
   * @param array $purchaseOrderItemIds
   * @return array
   * @throws Exception
   */
  public function associate(int $electronicDocumentId, array $purchaseOrderItemIds): array
  {
    return DB::transaction(function () use ($electronicDocumentId, $purchaseOrderItemIds) {
      // 1. Validar que el documento tenga productos en travesía
      $electronicDocument = ElectronicDocument::findOrFail($electronicDocumentId);

      if (!$electronicDocument->has_product_traverse) {
        throw new Exception('El documento electrónico no tiene productos en travesía habilitados.');
      }

      // 2. Obtener items en travesía
      $traverseItems = ElectronicDocumentItem::where('ap_billing_electronic_document_id', $electronicDocumentId)
        ->where('is_traverse', true)
        ->whereNotNull('product_id')
        ->get();

      if ($traverseItems->isEmpty()) {
        throw new Exception('No se encontraron items en travesía con product_id válido.');
      }

      // 3. Obtener los purchase order items seleccionados
      $purchaseOrderItems = PurchaseOrderItem::with('purchaseOrder')
        ->whereIn('id', $purchaseOrderItemIds)
        ->get()
        ->keyBy('id');

      if ($purchaseOrderItems->isEmpty()) {
        throw new Exception('No se encontraron items de compra válidos.');
      }

      // 4. Validar las órdenes de compra
      $this->validatePurchaseOrders($purchaseOrderItems);

      // 5. Procesar asociaciones
      $associations = [];
      $errors = [];

      foreach ($traverseItems as $traverseItem) {
        try {
          $association = $this->associateItem($traverseItem, $purchaseOrderItems);
          $associations[] = $association;
        } catch (Exception $e) {
          $errors[] = [
            'item_id' => $traverseItem->id,
            'product_id' => $traverseItem->product_id,
            'descripcion' => $traverseItem->descripcion,
            'error' => $e->getMessage(),
          ];
        }
      }

      // Si hay errores, revertir todo
      if (!empty($errors)) {
        throw new Exception('Errores en la asociación: ' . json_encode($errors));
      }

      // 6. Verificar si todos los items en travesía están asociados
      $allAssociated = $this->checkAllTraverseItemsAssociated($electronicDocumentId);

      if ($allAssociated) {
        $electronicDocument->update(['associate_purchase_traverse' => true]);
      }

      return [
        'success' => true,
        'message' => 'Asociación completada exitosamente.',
        'associations' => $associations,
        'all_associated' => $allAssociated,
      ];
    });
  }

  /**
   * Revertir la asociación de travesía
   *
   * @param int $electronicDocumentId
   * @return array
   * @throws Exception
   */
  public function revert(int $electronicDocumentId): array
  {
    return DB::transaction(function () use ($electronicDocumentId) {
      $electronicDocument = ElectronicDocument::findOrFail($electronicDocumentId);

      // Obtener todas las transacciones activas
      $activeTransactions = LinkPurchaseSaleTransaction::where('status', 'active')
        ->whereHas('electronicDocumentItem', function ($query) use ($electronicDocumentId) {
          $query->where('ap_billing_electronic_document_id', $electronicDocumentId);
        })
        ->with(['electronicDocumentItem', 'purchaseOrderItem'])
        ->get();

      if ($activeTransactions->isEmpty()) {
        throw new Exception('No se encontraron transacciones activas para revertir.');
      }

      $reverted = [];

      foreach ($activeTransactions as $transaction) {
        // Revertir cantidad en el purchase order item
        $purchaseOrderItem = $transaction->purchaseOrderItem;
        $purchaseOrderItem->quantity_available_traverse -= $transaction->cantidad;
        $purchaseOrderItem->save();

        // Marcar como revertida
        $transaction->status = 'reverted';
        $transaction->save();

        $reverted[] = [
          'transaction_id' => $transaction->id,
          'cantidad' => $transaction->cantidad,
          'product_id' => $purchaseOrderItem->product_id,
        ];
      }

      // Marcar el documento como no asociado
      $electronicDocument->update(['associate_purchase_traverse' => false]);

      return [
        'success' => true,
        'message' => 'Asociación revertida exitosamente.',
        'reverted' => $reverted,
      ];
    });
  }

  /**
   * Validar que las órdenes de compra cumplan con los requisitos
   *
   * @param \Illuminate\Support\Collection $purchaseOrderItems
   * @throws Exception
   */
  private function validatePurchaseOrders($purchaseOrderItems): void
  {
    $purchaseOrders = $purchaseOrderItems->pluck('purchaseOrder')->unique('id');

    foreach ($purchaseOrders as $purchaseOrder) {
      if (!$purchaseOrder) {
        throw new Exception('Orden de compra no encontrada.');
      }

      if ($purchaseOrder->status != 1) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no está activa (status debe ser 1).");
      }

      if (!in_array($purchaseOrder->migration_status, ['completed', 'updated_with_nc'])) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no tiene un migration_status válido (debe ser 'completed' o 'updated_with_nc').");
      }

      if (empty($purchaseOrder->invoice_dynamics)) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no tiene invoice_dynamics.");
      }

      if (empty($purchaseOrder->receipt_dynamics)) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no tiene receipt_dynamics.");
      }
    }
  }

  /**
   * Asociar un item de travesía con un item de compra
   *
   * @param ElectronicDocumentItem $traverseItem
   * @param \Illuminate\Support\Collection $purchaseOrderItems
   * @return array
   * @throws Exception
   */
  private function associateItem(ElectronicDocumentItem $traverseItem, $purchaseOrderItems): array
  {
    // Buscar un purchase order item con el mismo product_id
    $matchingItem = null;

    foreach ($purchaseOrderItems as $purchaseOrderItem) {
      if ($purchaseOrderItem->product_id == $traverseItem->product_id) {
        $disponible = $purchaseOrderItem->quantity - $purchaseOrderItem->quantity_available_traverse;

        if ($disponible >= $traverseItem->cantidad) {
          $matchingItem = $purchaseOrderItem;
          break;
        }
      }
    }

    if (!$matchingItem) {
      throw new Exception(
        "No se encontró un item de compra disponible para el producto '{$traverseItem->descripcion}' " .
        "(product_id: {$traverseItem->product_id}, cantidad requerida: {$traverseItem->cantidad})."
      );
    }

    // Crear la transacción
    $transaction = LinkPurchaseSaleTransaction::create([
      'billing_electronic_document_item_id' => $traverseItem->id,
      'purchase_order_item_id' => $matchingItem->id,
      'cantidad' => $traverseItem->cantidad,
      'status' => 'active',
    ]);

    // Actualizar quantity_available_traverse
    $matchingItem->quantity_available_traverse += $traverseItem->cantidad;
    $matchingItem->save();

    return [
      'transaction_id' => $transaction->id,
      'traverse_item_id' => $traverseItem->id,
      'purchase_order_item_id' => $matchingItem->id,
      'product_id' => $traverseItem->product_id,
      'cantidad' => $traverseItem->cantidad,
      'disponible_restante' => $matchingItem->quantity - $matchingItem->quantity_available_traverse,
    ];
  }

  /**
   * Verificar si todos los items en travesía están asociados
   *
   * @param int $electronicDocumentId
   * @return bool
   */
  private function checkAllTraverseItemsAssociated(int $electronicDocumentId): bool
  {
    $traverseItemsCount = ElectronicDocumentItem::where('ap_billing_electronic_document_id', $electronicDocumentId)
      ->where('is_traverse', true)
      ->whereNotNull('product_id')
      ->count();

    $associatedCount = ElectronicDocumentItem::where('ap_billing_electronic_document_id', $electronicDocumentId)
      ->where('is_traverse', true)
      ->whereNotNull('product_id')
      ->whereHas('linkTransactions', function ($query) {
        $query->where('status', 'active');
      })
      ->count();

    return $traverseItemsCount === $associatedCount && $traverseItemsCount > 0;
  }
}