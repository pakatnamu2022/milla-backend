<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Exception;

/**
 * Servicio para validar salidas de inventario ANTES de ejecutarlas
 *
 * Este servicio simula el proceso de salida de inventario para detectar
 * problemas potenciales (stock insuficiente, reservas faltantes, etc.)
 * sin modificar la base de datos.
 */
class InventoryOutputValidationService
{
  /**
   * Valida si un comprobante puede generar salida de inventario correctamente
   *
   * @param int $documentId ID del comprobante electrónico
   * @return array ['valid' => bool, 'errors' => array, 'details' => array]
   * @throws Exception
   */
  public function validateInventoryOutput(int $documentId): array
  {
    $document = ElectronicDocument::findOrFail($documentId);

    // Validar que sea área 881 (Taller) o 882 (Mesón)
    if (!in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
      return [
        'valid' => true,
        'errors' => [],
        'details' => ['message' => 'Área no requiere validación de inventario'],
      ];
    }

    // Los anticipos NO mueven inventario, solo validar facturas finales (is_advance_payment = 0)
    if ($document->is_advance_payment) {
      return [
        'valid' => true,
        'errors' => [],
        'details' => ['message' => 'Anticipos no mueven inventario, no requiere validación'],
      ];
    }

    // TODO: Implementar validación para notas de crédito en el futuro
    // Por ahora, las notas de crédito no validan inventario
    if ($document->is_nota_credito || $document->is_nota_debito) {
      return [
        'valid' => true,
        'errors' => [],
        'details' => ['message' => 'Notas de crédito/débito no validan inventario (TODO: implementar validación futura)'],
      ];
    }

    $errors = [];
    $details = [];

    // Determinar tipo de procesamiento
    if ($document->consolidation_type === 'massive') {
      $result = $this->validateMassiveInvoice($document);
      $errors = $result['errors'];
      $details = $result['details'];
    } elseif ($document->work_order_id) {
      $result = $this->validateWorkOrder($document->workOrder);
      $errors = $result['errors'];
      $details = $result['details'];
    } elseif ($document->order_quotation_id) {
      $result = $this->validateQuotation($document->orderQuotation);
      $errors = $result['errors'];
      $details = $result['details'];
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
      'details' => $details,
    ];
  }

  /**
   * Valida factura masiva (múltiples OTs)
   */
  private function validateMassiveInvoice(ElectronicDocument $document): array
  {
    $errors = [];
    $details = [];

    $internalNotes = $document->internalNotes()
      ->with('workOrder.parts.product')
      ->get();

    foreach ($internalNotes as $note) {
      $workOrder = $note->workOrder;
      if (!$workOrder) {
        continue;
      }

      $result = $this->validateWorkOrder($workOrder);
      if (!empty($result['errors'])) {
        $errors = array_merge($errors, $result['errors']);
      }
      $details[] = $result['details'];
    }

    return ['errors' => $errors, 'details' => $details];
  }

  /**
   * Valida una orden de trabajo
   */
  private function validateWorkOrder(ApWorkOrder $workOrder): array
  {
    $errors = [];
    $details = [
      'type' => 'work_order',
      'id' => $workOrder->id,
      'correlative' => $workOrder->correlative,
      'products' => [],
    ];

    // Si ya generó salida, omitir
    if ($workOrder->output_generation_warehouse) {
      $details['already_processed'] = true;
      return ['errors' => [], 'details' => $details];
    }

    // VALIDACIÓN: Detectar si tuvo NC sin re-reservar
    if ($workOrder->had_credit_note && !$workOrder->stock_re_reserved) {
      $errors[] = [
        'work_order_id' => $workOrder->id,
        'correlative' => $workOrder->correlative,
        'error' => "⚠️ Esta OT tuvo NOTA DE CRÉDITO y NO se ha re-reservado el stock. " .
          "Debe ejecutar el endpoint POST /api/ap/postVenta/productWarehouseStock/re-reserve-after-credit-note " .
          "con work_order_id={$workOrder->id} antes de volver a facturar.",
      ];
      $details['requires_re_reservation'] = true;
      return ['errors' => $errors, 'details' => $details];
    }

    // Obtener almacén físico de postventa
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($workOrder->sede_id);

    if (!$warehouse) {
      $errors[] = [
        'work_order_id' => $workOrder->id,
        'correlative' => $workOrder->correlative,
        'error' => "No se encontró almacén físico de postventa para sede {$workOrder->sede_id}",
      ];
      return ['errors' => $errors, 'details' => $details];
    }

    // Validar cada producto
    $productParts = $workOrder->parts()
      ->where('is_traverse', false)
      ->with('product')
      ->get();

    foreach ($productParts as $part) {
      $productDetail = $this->validateProduct(
        $part->product_id,
        $warehouse->id,
        $part->quantity_used,
        true, // OTs siempre tienen reserva
        $part->product->descripcion ?? "Producto ID {$part->product_id}"
      );

      $details['products'][] = $productDetail;

      if (!$productDetail['valid']) {
        $errors[] = [
          'work_order_id' => $workOrder->id,
          'correlative' => $workOrder->correlative,
          'product_id' => $part->product_id,
          'product_name' => $part->product->descripcion ?? "Producto ID {$part->product_id}",
          'quantity_required' => $part->quantity_used,
          'error' => $productDetail['error'],
        ];
      }
    }

    return ['errors' => $errors, 'details' => $details];
  }

  /**
   * Valida una cotización de mesón
   */
  private function validateQuotation(ApOrderQuotations $quotation): array
  {
    $errors = [];
    $details = [
      'type' => 'quotation',
      'id' => $quotation->id,
      'quotation_number' => $quotation->quotation_number,
      'products' => [],
    ];

    // Si ya generó salida, omitir
    if ($quotation->output_generation_warehouse) {
      $details['already_processed'] = true;
      return ['errors' => [], 'details' => $details];
    }

    // VALIDACIÓN: Detectar si tuvo NC sin re-reservar
    if ($quotation->had_credit_note && !$quotation->stock_re_reserved) {
      $errors[] = [
        'quotation_id' => $quotation->id,
        'quotation_number' => $quotation->quotation_number,
        'error' => "⚠️ Esta cotización tuvo NOTA DE CRÉDITO y NO se ha re-reservado el stock. " .
          "Debe ejecutar el endpoint POST /api/ap/postVenta/productWarehouseStock/re-reserve-after-credit-note " .
          "con quotation_id={$quotation->id} antes de volver a facturar.",
      ];
      $details['requires_re_reservation'] = true;
      return ['errors' => $errors, 'details' => $details];
    }

    // Obtener almacén físico de postventa
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($quotation->sede_id);

    if (!$warehouse) {
      $errors[] = [
        'quotation_id' => $quotation->id,
        'quotation_number' => $quotation->quotation_number,
        'error' => "No se encontró almacén físico de postventa para sede {$quotation->sede_id}",
      ];
      return ['errors' => $errors, 'details' => $details];
    }

    // Validar cada producto
    $productDetails = $quotation->details()
      ->where('is_traverse', false)
      ->with('product')
      ->get();

    foreach ($productDetails as $detail) {
      $hasReservation = $detail->supply_type === ApOrderQuotationDetails::SUPPLY_TYPE_STOCK;

      $productDetail = $this->validateProduct(
        $detail->product_id,
        $warehouse->id,
        $detail->quantity,
        $hasReservation,
        $detail->product->descripcion ?? "Producto ID {$detail->product_id}"
      );

      $details['products'][] = $productDetail;

      if (!$productDetail['valid']) {
        $errors[] = [
          'quotation_id' => $quotation->id,
          'quotation_number' => $quotation->quotation_number,
          'product_id' => $detail->product_id,
          'product_name' => $detail->product->descripcion ?? "Producto ID {$detail->product_id}",
          'quantity_required' => $detail->quantity,
          'supply_type' => $detail->supply_type,
          'error' => $productDetail['error'],
        ];
      }
    }

    return ['errors' => $errors, 'details' => $details];
  }

  /**
   * Valida un producto individual
   */
  private function validateProduct(
    int    $productId,
    int    $warehouseId,
    float  $quantity,
    bool   $hasReservation,
    string $productName
  ): array
  {
    $stock = ProductWarehouseStock::where('product_id', $productId)
      ->where('warehouse_id', $warehouseId)
      ->first();

    if (!$stock) {
      return [
        'valid' => false,
        'product_id' => $productId,
        'product_name' => $productName,
        'quantity_required' => $quantity,
        'has_reservation' => $hasReservation,
        'error' => 'Sin registro de stock en el almacén',
      ];
    }

    $result = [
      'valid' => true,
      'product_id' => $productId,
      'product_name' => $productName,
      'quantity_required' => $quantity,
      'has_reservation' => $hasReservation,
      'current_stock' => [
        'quantity' => $stock->quantity,
        'reserved' => $stock->reserved_quantity,
        'available' => $stock->available_quantity,
      ],
    ];

    // Validar según tipo de flujo
    if ($hasReservation) {
      // Flujo CON RESERVA: releaseReservedStockAndRemove()
      if ($stock->reserved_quantity < $quantity) {
        $result['valid'] = false;
        $result['error'] = "Stock reservado insuficiente (tiene {$stock->reserved_quantity}, necesita {$quantity})";
      } elseif ($stock->quantity < $quantity) {
        $result['valid'] = false;
        $result['error'] = "Stock físico insuficiente (tiene {$stock->quantity}, necesita {$quantity})";
      }
    } else {
      // Flujo SIN RESERVA: removeStockWithoutReservation()
      if ($stock->available_quantity < $quantity) {
        $result['valid'] = false;
        $result['error'] = "Stock disponible insuficiente (tiene {$stock->available_quantity}, necesita {$quantity})";
      }
    }

    return $result;
  }
}
