<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para RE-RESERVAR stock después de Notas de Crédito
 *
 * CONTEXTO:
 * Cuando se genera una Nota de Crédito (NC), el stock regresa a quantity pero NO a reserved_quantity.
 * Si vuelven a facturar la misma OT/Cotización, necesitan re-reservar manualmente el stock.
 *
 * CASOS DE USO:
 * - NC generada por error en datos → Vuelven a facturar → Necesitan re-reservar
 * - NC generada y NO vuelven a facturar → NO necesitan re-reservar (stock queda disponible)
 *
 * Este servicio permite re-reservar MANUALMENTE cuando confirman que SÍ van a re-facturar.
 */
class StockReReservationService
{
  /**
   * Re-reservar stock para una cotización de mesón que tuvo NC
   *
   * @param int $quotationId ID de la cotización
   * @return array Resultado de la operación
   * @throws Exception
   */
  public function reReserveStockForQuotation(int $quotationId): array
  {
    DB::beginTransaction();
    try {
      $quotation = ApOrderQuotations::with('details.product')->findOrFail($quotationId);

      // Validación 1: Verificar que tuvo NC
      if (!$quotation->had_credit_note) {
        throw new Exception("La cotización {$quotation->quotation_number} NO tiene nota de crédito registrada. No requiere re-reserva.");
      }

      // Validación 2: Verificar que NO se ha re-reservado ya
      if ($quotation->stock_re_reserved) {
        throw new Exception("La cotización {$quotation->quotation_number} YA tiene stock re-reservado. No se puede re-reservar nuevamente.");
      }

      // Obtener almacén físico de postventa
      $warehouse = Warehouse::getPhysicalWarehouseForPostsale($quotation->sede_id);
      if (!$warehouse) {
        throw new Exception("No se encontró almacén físico de postventa para sede {$quotation->sede_id}");
      }

      $productsReReserved = [];
      $errors = [];

      // Re-reservar cada producto con supply_type='STOCK' (los únicos que tienen reserva)
      $productDetails = $quotation->details
        ->where('item_type', ApOrderQuotationDetails::ITEM_TYPE_PRODUCT)
        ->where('product_id', '!=', null)
        ->where('is_traverse', false)
        ->where('supply_type', ApOrderQuotationDetails::SUPPLY_TYPE_STOCK);

      foreach ($productDetails as $detail) {
        try {
          $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

          // Validar que hay stock disponible suficiente para re-reservar
          if ($stock->available_quantity < $detail->quantity) {
            $errors[] = [
              'product_id' => $detail->product_id,
              'product_name' => $detail->product->descripcion ?? "Producto ID {$detail->product_id}",
              'quantity_required' => $detail->quantity,
              'available_quantity' => $stock->available_quantity,
              'error' => "Stock disponible insuficiente para re-reservar",
            ];
            continue;
          }

          // Re-reservar: Incrementar reserved_quantity
          $stockBefore = [
            'quantity' => $stock->quantity,
            'reserved' => $stock->reserved_quantity,
            'available' => $stock->available_quantity,
          ];

          $stock->reserved_quantity += $detail->quantity;
          $stock->updateAvailableQuantity(); // available = quantity - reserved
          $stock->save();

          $productsReReserved[] = [
            'product_id' => $detail->product_id,
            'product_name' => $detail->product->descripcion ?? "Producto ID {$detail->product_id}",
            'quantity_re_reserved' => $detail->quantity,
            'stock_before' => $stockBefore,
            'stock_after' => [
              'quantity' => $stock->quantity,
              'reserved' => $stock->reserved_quantity,
              'available' => $stock->available_quantity,
            ],
          ];

        } catch (Exception $e) {
          $errors[] = [
            'product_id' => $detail->product_id,
            'product_name' => $detail->product->descripcion ?? "Producto ID {$detail->product_id}",
            'error' => $e->getMessage(),
          ];
        }
      }

      // Si hubo errores, rollback
      if (!empty($errors)) {
        DB::rollBack();
        return [
          'success' => false,
          'message' => 'No se pudo completar la re-reserva debido a errores en algunos productos',
          'errors' => $errors,
          'products_re_reserved' => $productsReReserved,
        ];
      }

      // Marcar que se re-reservó
      $quotation->update(['stock_re_reserved' => true]);

      DB::commit();

      Log::info('Stock re-reservado exitosamente para cotización', [
        'quotation_id' => $quotationId,
        'quotation_number' => $quotation->quotation_number,
        'products_count' => count($productsReReserved),
      ]);

      return [
        'success' => true,
        'message' => "Stock re-reservado exitosamente para cotización {$quotation->quotation_number}",
        'quotation_id' => $quotationId,
        'quotation_number' => $quotation->quotation_number,
        'products_re_reserved' => $productsReReserved,
      ];

    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al re-reservar stock para cotización', [
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Re-reservar stock para una orden de trabajo que tuvo NC
   *
   * @param int $workOrderId ID de la orden de trabajo
   * @return array Resultado de la operación
   * @throws Exception
   */
  public function reReserveStockForWorkOrder(int $workOrderId): array
  {
    DB::beginTransaction();
    try {
      $workOrder = ApWorkOrder::with('parts.product')->findOrFail($workOrderId);

      // Validación 1: Verificar que tuvo NC
      if (!$workOrder->had_credit_note) {
        throw new Exception("La OT {$workOrder->correlative} NO tiene nota de crédito registrada. No requiere re-reserva.");
      }

      // Validación 2: Verificar que NO se ha re-reservado ya
      if ($workOrder->stock_re_reserved) {
        throw new Exception("La OT {$workOrder->correlative} YA tiene stock re-reservado. No se puede re-reservar nuevamente.");
      }

      // Obtener almacén físico de postventa
      $warehouse = Warehouse::getPhysicalWarehouseForPostsale($workOrder->sede_id);
      if (!$warehouse) {
        throw new Exception("No se encontró almacén físico de postventa para sede {$workOrder->sede_id}");
      }

      $productsReReserved = [];
      $errors = [];

      // Re-reservar cada repuesto (OTs SIEMPRE tienen reserva)
      $productParts = $workOrder->parts()
        ->where('is_traverse', false)
        ->with('product')
        ->get();

      foreach ($productParts as $part) {
        try {
          $stock = ProductWarehouseStock::where('product_id', $part->product_id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

          // Validar que hay stock disponible suficiente para re-reservar
          if ($stock->available_quantity < $part->quantity_used) {
            $errors[] = [
              'product_id' => $part->product_id,
              'product_name' => $part->product->descripcion ?? "Producto ID {$part->product_id}",
              'quantity_required' => $part->quantity_used,
              'available_quantity' => $stock->available_quantity,
              'error' => "Stock disponible insuficiente para re-reservar",
            ];
            continue;
          }

          // Re-reservar: Incrementar reserved_quantity
          $stockBefore = [
            'quantity' => $stock->quantity,
            'reserved' => $stock->reserved_quantity,
            'available' => $stock->available_quantity,
          ];

          $stock->reserved_quantity += $part->quantity_used;
          $stock->updateAvailableQuantity(); // available = quantity - reserved
          $stock->save();

          $productsReReserved[] = [
            'product_id' => $part->product_id,
            'product_name' => $part->product->descripcion ?? "Producto ID {$part->product_id}",
            'quantity_re_reserved' => $part->quantity_used,
            'stock_before' => $stockBefore,
            'stock_after' => [
              'quantity' => $stock->quantity,
              'reserved' => $stock->reserved_quantity,
              'available' => $stock->available_quantity,
            ],
          ];

        } catch (Exception $e) {
          $errors[] = [
            'product_id' => $part->product_id,
            'product_name' => $part->product->descripcion ?? "Producto ID {$part->product_id}",
            'error' => $e->getMessage(),
          ];
        }
      }

      // Si hubo errores, rollback
      if (!empty($errors)) {
        DB::rollBack();
        return [
          'success' => false,
          'message' => 'No se pudo completar la re-reserva debido a errores en algunos productos',
          'errors' => $errors,
          'products_re_reserved' => $productsReReserved,
        ];
      }

      // Marcar que se re-reservó
      $workOrder->update(['stock_re_reserved' => true]);

      DB::commit();

      Log::info('Stock re-reservado exitosamente para orden de trabajo', [
        'work_order_id' => $workOrderId,
        'correlative' => $workOrder->correlative,
        'products_count' => count($productsReReserved),
      ]);

      return [
        'success' => true,
        'message' => "Stock re-reservado exitosamente para OT {$workOrder->correlative}",
        'work_order_id' => $workOrderId,
        'correlative' => $workOrder->correlative,
        'products_re_reserved' => $productsReReserved,
      ];

    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al re-reservar stock para orden de trabajo', [
        'work_order_id' => $workOrderId,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }
}
