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
   *
   * El rango de fechas se aplica sobre la fecha de emisión de la factura final
   * de cada cotización: sólo se incluyen los bonos de cotizaciones cuya factura
   * final se emitió dentro del periodo seleccionado.
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
      // Optimización: limita a cotizaciones con al menos una factura/boleta
      // válida emitida dentro del rango. El filtro definitivo sobre la factura
      // "final" se aplica en memoria más abajo.
      $query->whereHas('electronicDocuments', function ($q) use ($fechaInicio, $fechaFin) {
        $q->where('aceptada_por_sunat', 1)
          ->where('anulado', 0)
          ->whereNull('deleted_at')
          ->where('is_advance_payment', 0)
          ->whereIn('sunat_concept_document_type_id', [
            ElectronicDocument::TYPE_FACTURA,
            ElectronicDocument::TYPE_BOLETA,
          ])
          ->whereDate('fecha_de_emision', '>=', $fechaInicio)
          ->whereDate('fecha_de_emision', '<=', $fechaFin);
      });
    }

    if (!empty($sedeIds)) {
      $query->whereIn('sede_id', $sedeIds);
    }

    // is_paid es un accessor calculado (no una columna de BD), por lo que el
    // filtro "cotizaciones totalmente pagadas" se aplica en memoria.
    $quotes = $query->get()->filter(fn($quote) => $quote->is_paid);

    // Sólo cotizaciones cuya factura FINAL (la última factura/boleta válida por
    // fecha de emisión) fue emitida dentro del periodo seleccionado.
    if ($fechaInicio && $fechaFin) {
      $quotes = $quotes->filter(function ($quote) use ($fechaInicio, $fechaFin) {
        $finalInvoiceDate = $quote->electronicDocuments->first()?->fecha_de_emision;

        if (!$finalInvoiceDate) {
          return false;
        }

        return $finalInvoiceDate->toDateString() >= $fechaInicio
          && $finalInvoiceDate->toDateString() <= $fechaFin;
      });
    }

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
