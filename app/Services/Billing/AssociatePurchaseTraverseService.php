<?php

namespace App\Services\Billing;

use App\Jobs\VerifyAndMigrateTraverseJob;
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
      $idUser = auth()->id() ?? 0;

      if ($electronicDocument->status !== ElectronicDocument::STATUS_ACCEPTED) {
        throw new Exception('El documento electrónico debe estar aceptado aceptado.');
      }

      if (!$electronicDocument->aceptada_por_sunat) {
        throw new Exception('El documento electrónico no ha sido aceptado por SUNAT.');
      }

      if ($electronicDocument->migration_status !== 'completed') {
        throw new Exception('El documento electrónico no ha completado su migración en dynamics.');
      }

      if ($electronicDocument->anulado) {
        throw new Exception('El documento electrónico ha sido anulado.');
      }

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
          $association = $this->associateItem($traverseItem, $purchaseOrderItems, $idUser);
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
        $errorMessages = [];
        foreach ($errors as $error) {
          $errorMessages[] = "• Producto '{$error['descripcion']}' (ID: {$error['product_id']}): {$error['error']}";
        }
        throw new Exception(
          "No se pudo completar la asociación. Se encontraron los siguientes errores:\n\n" .
          implode("\n", $errorMessages)
        );
      }

      // 6. Verificar si todos los items en travesía están asociados
      $allAssociated = $this->checkAllTraverseItemsAssociated($electronicDocumentId);

      if ($allAssociated) {
        $electronicDocument->update([
          'associate_purchase_traverse' => true,
          'traverse_migration_status' => 'pending',
        ]);

        // Despachar job de migración automáticamente
        VerifyAndMigrateTraverseJob::dispatch($electronicDocumentId);
      }

      return [
        'success' => true,
        'message' => 'Asociación completada exitosamente.' . ($allAssociated ? ' Job de migración despachado.' : ''),
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

        // Validar que no quede negativo
        $nuevoValor = $purchaseOrderItem->quantity_available_traverse - $transaction->cantidad;
        if ($nuevoValor < 0) {
          $purchaseOrderNumber = $purchaseOrderItem->purchaseOrder->number ?? 'N/A';
          throw new Exception(
            "No se puede revertir la transacción #{$transaction->id}. " .
            "La cantidad disponible en travesía quedaría negativa ({$nuevoValor}) para el item de compra #{$purchaseOrderItem->id} " .
            "de la orden {$purchaseOrderNumber}. Posible corrupción de datos, contacte al administrador del sistema."
          );
        }

        $purchaseOrderItem->quantity_available_traverse = $nuevoValor;
        $purchaseOrderItem->save();

        // Marcar como revertida
        $transaction->status = 'reverted';
        $transaction->save();

        $reverted[] = [
          'transaction_id' => $transaction->id,
          'purchase_order_item_id' => $purchaseOrderItem->id,
          'purchase_order_number' => $purchaseOrderItem->purchaseOrder->number ?? null,
          'product_id' => $purchaseOrderItem->product_id,
          'cantidad_revertida' => $transaction->cantidad,
          'quantity_available_traverse_anterior' => $purchaseOrderItem->quantity_available_traverse + $transaction->cantidad,
          'quantity_available_traverse_nuevo' => $purchaseOrderItem->quantity_available_traverse,
        ];
      }

      // Marcar el documento como no asociado y resetear estado de migración
      $electronicDocument->update([
        'associate_purchase_traverse' => false,
        'traverse_migration_status' => 'pending',
      ]);

      // Despachar job de reversión automáticamente
      VerifyAndMigrateTraverseJob::dispatch($electronicDocumentId, isReversal: true);

      return [
        'success' => true,
        'message' => 'Asociación revertida exitosamente. Job de reversión despachado.',
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
   * Asociar un item de travesía con uno o más items de compra
   * Soporta dividir cantidades entre múltiples compras si es necesario
   *
   * @param ElectronicDocumentItem $traverseItem
   * @param \Illuminate\Support\Collection $purchaseOrderItems
   * @return array
   * @throws Exception
   */
  private function associateItem(ElectronicDocumentItem $traverseItem, $purchaseOrderItems, int $idUser): array
  {
    // Filtrar solo los items de compra del mismo producto
    $matchingItems = $purchaseOrderItems->filter(function ($item) use ($traverseItem) {
      return $item->product_id == $traverseItem->product_id;
    })->values();

    if ($matchingItems->isEmpty()) {
      throw new Exception(
        "No se encontraron items de compra para el producto '{$traverseItem->descripcion}' " .
        "(product_id: {$traverseItem->product_id})."
      );
    }

    // Calcular disponibilidad de cada item y total
    $itemsConDisponibilidad = $matchingItems->map(function ($item) {
      return [
        'item' => $item,
        'disponible' => $item->quantity - $item->quantity_available_traverse,
      ];
    })->filter(function ($data) {
      return $data['disponible'] > 0;
    })->values();

    if ($itemsConDisponibilidad->isEmpty()) {
      throw new Exception(
        "No hay cantidad disponible en las compras para el producto '{$traverseItem->descripcion}' " .
        "(product_id: {$traverseItem->product_id})."
      );
    }

    $totalDisponible = $itemsConDisponibilidad->sum('disponible');
    $cantidadNecesaria = $traverseItem->cantidad;

    // Validar que haya suficiente cantidad total
    if ($totalDisponible < $cantidadNecesaria) {
      throw new Exception(
        "Cantidad insuficiente para el producto '{$traverseItem->descripcion}'. " .
        "Necesario: {$cantidadNecesaria}, Disponible: {$totalDisponible}."
      );
    }

    // Validar que NO se envíen compras innecesarias
    // Si UNA sola compra puede cubrir toda la cantidad, NO deberían enviar otras
    $itemsQueCubrenSolos = $itemsConDisponibilidad->filter(function ($data) use ($cantidadNecesaria) {
      return $data['disponible'] >= $cantidadNecesaria;
    });

    if ($itemsQueCubrenSolos->count() > 0 && $itemsConDisponibilidad->count() > 1) {
      throw new Exception(
        "El producto '{$traverseItem->descripcion}' (cantidad: {$cantidadNecesaria}) puede ser cubierto " .
        "por una sola compra. No se deben enviar múltiples items de compra del mismo producto cuando " .
        "uno solo es suficiente. Elimine las compras innecesarias."
      );
    }

    // Distribuir cantidad entre los items de compra disponibles
    $cantidadRestante = $cantidadNecesaria;
    $transacciones = [];

    foreach ($itemsConDisponibilidad as $data) {
      if ($cantidadRestante <= 0) {
        break;
      }

      $purchaseItem = $data['item'];
      $disponible = $data['disponible'];

      // Tomar lo que se necesite o lo que haya disponible (lo que sea menor)
      $cantidadATomar = min($cantidadRestante, $disponible);

      // Crear la transacción
      $transaction = LinkPurchaseSaleTransaction::create([
        'billing_electronic_document_item_id' => $traverseItem->id,
        'purchase_order_item_id' => $purchaseItem->id,
        'cantidad' => $cantidadATomar,
        'status' => 'active',
        'create_by' => $idUser,
      ]);

      // Actualizar quantity_available_traverse
      $purchaseItem->quantity_available_traverse += $cantidadATomar;
      $purchaseItem->save();

      $transacciones[] = [
        'transaction_id' => $transaction->id,
        'purchase_order_item_id' => $purchaseItem->id,
        'cantidad_tomada' => $cantidadATomar,
        'disponible_restante' => $purchaseItem->quantity - $purchaseItem->quantity_available_traverse,
      ];

      $cantidadRestante -= $cantidadATomar;
    }

    return [
      'traverse_item_id' => $traverseItem->id,
      'product_id' => $traverseItem->product_id,
      'descripcion' => $traverseItem->descripcion,
      'cantidad_necesaria' => $cantidadNecesaria,
      'cantidad_asociada' => $cantidadNecesaria - $cantidadRestante,
      'transacciones' => $transacciones,
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
