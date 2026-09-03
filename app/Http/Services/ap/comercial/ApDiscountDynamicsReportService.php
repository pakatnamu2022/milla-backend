<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Collection;

class ApDiscountDynamicsReportService
{
  /**
   * Genera el detalle de descuentos (is_negative=true) por cotización, mostrando
   * cómo cada descuento se refleja en el ítem del documento electrónico y qué
   * se envía (o se enviaría) a Dynamics en DescuentoUnitario y ArticuloId.
   */
  public function generate(
    ?string $fechaInicio = null,
    ?string $fechaFin    = null,
    ?array  $sedeIds     = null
  ): Collection {
    $query = PurchaseRequestQuote::query()
      ->with([
        'sede',
        'vehicle',
        'opportunity.client',
        'typeCurrency',
        'discountCoupons' => function ($q) {
          $q->where('is_negative', true)->with('conceptCode.parent');
        },
        'electronicDocuments' => function ($q) {
          $q->where('aceptada_por_sunat', 1)
            ->where('anulado', 0)
            ->whereNull('deleted_at')
            ->where('is_advance_payment', 0)
            ->whereIn('sunat_concept_document_type_id', [
              ElectronicDocument::TYPE_FACTURA,
              ElectronicDocument::TYPE_BOLETA,
            ])
            ->orderByDesc('fecha_de_emision')
            ->with(['items.accountPlan']);
        },
      ])
      ->whereHas('discountCoupons', function ($q) {
        $q->where('is_negative', true);
      });

    if ($fechaInicio && $fechaFin) {
      $query->whereDate('created_at', '>=', $fechaInicio)
            ->whereDate('created_at', '<=', $fechaFin);
    }

    if (!empty($sedeIds)) {
      $query->whereIn('sede_id', $sedeIds);
    }

    $quotes = $query->get();

    $rows = collect();

    foreach ($quotes as $quote) {
      $client  = $quote->opportunity->client->full_name ?? '';
      $vin     = $quote->vehicle->vin ?? '';
      $finalDoc = $quote->electronicDocuments->first();

      // Ítem principal: el primero que no sea regularización de anticipo
      $mainItem = $finalDoc
        ? $finalDoc->items->firstWhere('anticipo_regularizacion', false) ?? $finalDoc->items->first()
        : null;

      // ArticuloId que se envía a Dynamics para el ítem principal
      $articuloId = null;
      if ($mainItem) {
        $hasSpecialOrigin = $finalDoc->order_quotation_id || $finalDoc->work_order_id;
        if ($finalDoc->is_advance_payment) {
          $articuloId = $mainItem->accountPlan?->code_dynamics;
        } elseif ($hasSpecialOrigin) {
          $articuloId = $mainItem->anticipo_regularizacion
            ? $mainItem->accountPlan?->code_dynamics
            : $mainItem->dyn_code;
        } else {
          $articuloId = $mainItem->accountPlan?->code_dynamics;
        }
      }

      $currencySymbol = $quote->typeCurrency?->symbol ?? 'S/';

      foreach ($quote->discountCoupons as $discount) {
        $concept     = $discount->conceptCode;
        $conceptName = $concept?->description ?? '';
        $conceptType = $concept?->parent?->description ?? $conceptName;

        $rows->push((object) [
          'correlative'           => $quote->full_correlative,
          'quote_date'            => $quote->created_at?->format('Y-m-d') ?? '',
          'client'                => $client,
          'vin'                   => $vin,
          'currency_symbol'       => $currencySymbol,
          'sale_price'            => (float) $quote->sale_price,
          'discount_type'         => $discount->type,
          'discount_concept_type' => $conceptType,
          'discount_concept'      => $conceptName,
          'discount_sin_igv'      => (float) ($discount->valor_unitario ?? 0),
          'discount_con_igv'      => (float) ($discount->precio_unitario ?? 0),
          'invoice_number'        => $finalDoc?->full_number ?? '',
          'invoice_date'          => $finalDoc?->fecha_de_emision?->format('Y-m-d') ?? '',
          'invoice_total'         => (float) ($finalDoc?->total ?? 0),
          'item_description'      => $mainItem?->descripcion ?? '',
          'item_valor_unitario'   => (float) ($mainItem?->valor_unitario ?? 0),
          'item_descuento_unitario' => (float) ($mainItem?->descuento_unitario ?? 0),
          'item_subtotal'         => (float) ($mainItem?->subtotal ?? 0),
          'item_articulo_id'      => $articuloId ?? '',
          'was_dyn_requested'     => (bool) ($finalDoc?->was_dyn_requested ?? false),
          'migration_status'      => $finalDoc?->migration_status ?? '',
        ]);
      }
    }

    return $rows;
  }
}
