<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Collection;

class ApBonusReportService
{
  /**
   * Sedes cuyas abreviaturas deben agruparse en una sola hoja del reporte
   * (ej. AP_PIMENTEL y AP_PIMENTEL_JAC son la misma sede comercial).
   */
  private const SEDE_LABEL_OVERRIDES = [
    'AP_PIMENTEL'     => 'PIMENTEL',
    'AP_PIMENTEL_JAC' => 'PIMENTEL',
  ];

  /**
   * Genera el detalle de bonos (no descuentos) agrupado por sede, únicamente de
   * cotizaciones (PurchaseRequestQuote) que ya están totalmente pagadas
   * (is_paid = true), tal como lo requiere el reporte de bonos comercial.
   */
  public function generate(
    ?string $fechaInicio = null,
    ?string $fechaFin = null,
    ?array  $sedeIds = null
  ): Collection {
    $query = PurchaseRequestQuote::query()
      ->with([
        'sede',
        'vehicle.purchaseOrder',
        'vehicle.model.family.brand',
        'opportunity.client',
        'discountCoupons' => function ($q) {
          $q->where('is_negative', false)->with('conceptCode.parent');
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
            ->orderByDesc('fecha_de_emision');
        },
      ])
      ->whereHas('discountCoupons', function ($q) {
        $q->where('is_negative', false);
      });

    if ($fechaInicio && $fechaFin) {
      $query->whereDate('created_at', '>=', $fechaInicio)
        ->whereDate('created_at', '<=', $fechaFin);
    }

    if (!empty($sedeIds)) {
      $query->whereIn('sede_id', $sedeIds);
    }

    // is_paid es un accessor calculado (no una columna de BD), por lo que el
    // filtro "cotizaciones totalmente pagadas" se aplica en memoria.
    $quotes = $query->get()->filter(fn($quote) => $quote->is_paid);

    $rows = collect();

    foreach ($quotes as $quote) {
      $abreviatura = $quote->sede->abreviatura ?? null;
      $sedeLabel = $abreviatura
        ? (self::SEDE_LABEL_OVERRIDES[$abreviatura] ?? $abreviatura)
        : 'SIN SEDE';

      $clientName = $quote->opportunity->client->full_name ?? '';

      // Factura final con la que se pagó la cotización: la última (por fecha
      // de emisión) factura/boleta aceptada, no anulada y que no sea anticipo.
      $finalInvoice = $quote->electronicDocuments->first();

      foreach ($quote->discountCoupons as $bonus) {
        $concept   = $bonus->conceptCode;
        $bonusType = $concept?->parent->description ?? $concept?->description ?? '';
        $bonusConcept = $concept->description ?? '';

        $vehicle = $quote->vehicle;
        $purchaseOrder = $vehicle->purchaseOrder ?? null;
        $purchaseInvoice = ($purchaseOrder && $purchaseOrder->invoice_series && $purchaseOrder->invoice_number)
          ? $purchaseOrder->invoice_series . '-' . $purchaseOrder->invoice_number
          : '';
        $rows->push((object) [
          'sede_id'          => $quote->sede_id,
          'sede'             => $sedeLabel,
          'correlative'      => $quote->full_correlative,
          'client'           => $clientName,
          'sale_price'       => (float) $quote->sale_price,
          'vin'              => $vehicle->vin ?? '',
          'brand'            => $vehicle->model->family->brand->name ?? '',
          'model_version'    => $vehicle->model->version ?? '',
          'purchase_invoice' => $purchaseInvoice,
          'purchase_date'    => $purchaseOrder?->emission_date?->format('Y-m-d') ?? '',
          'bonus_type'       => $bonusType,
          'bonus_concept'    => $bonusConcept,
          'amount'           => (float) $bonus->amount,
          'invoice_number'   => $finalInvoice->full_number ?? '',
          'invoice_amount'   => $finalInvoice ? (float) $finalInvoice->total : 0.0,
          'invoice_date'     => $finalInvoice?->fecha_de_emision?->format('Y-m-d') ?? '',
        ]);
      }
    }

    return $rows->groupBy('sede');
  }
}
