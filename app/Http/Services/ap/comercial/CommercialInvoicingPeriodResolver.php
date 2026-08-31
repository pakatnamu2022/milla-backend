<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve, para un rango de fechas, qué solicitudes (purchase_request_quote_id)
 * tienen una venta facturada "real" atribuible a ese periodo.
 *
 * Reglas (compartidas con el Reporte de Facturación Comercial):
 *  - Neto de un comprobante = total - Σ NC + Σ ND.
 *  - Una NC aplica si: anulado = false Y ( aceptada_por_sunat = true
 *    OR fecha_de_emision = hoy [día en que se genera el reporte] ).
 *  - Una ND aplica solo si está aceptada por SUNAT.
 *  - Comprobante vigente = neto > ROUNDING_TOLERANCE.
 *  - Solicitud sin comprobante vigente (NC anuló/descontó todo y no hubo
 *    refacturación) => no cuenta en ningún periodo.
 *  - Refacturación: si la solicitud tiene ≥1 comprobante anulado por NC y otro
 *    vigente posterior, la venta se atribuye al periodo del PRIMER comprobante
 *    de la cadena.
 */
class CommercialInvoicingPeriodResolver
{
  /**
   * @return object{
   *   quote_ids: Collection<int>,                 // solicitudes atribuidas al rango
   *   out_of_period: Collection<int>,             // solicitudes facturadas en el rango pero atribuidas a un periodo anterior
   *   refactured: array<int,array>,               // detalle de refacturaciones / NC parcial atribuidas al rango (key = quote_id)
   * }
   */
  public function resolve(string $start, string $end, ?string $today = null): object
  {
    $today = $today ?: now()->toDateString();
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);

    $invoices = DB::table('ap_billing_electronic_documents')
      ->whereNull('deleted_at')
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->where('area_id', ApMasters::AREA_COMERCIAL)
      ->whereNotNull('purchase_request_quote_id')
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA,
      ])
      ->select('id', 'purchase_request_quote_id', 'total', 'fecha_de_emision')
      ->get();

    if ($invoices->isEmpty()) {
      return (object) [
        'quote_ids' => collect(),
        'out_of_period' => collect(),
        'refactured' => [],
      ];
    }

    $invoiceIds = $invoices->pluck('id')->all();

    $ncByInvoice = DB::table('ap_billing_electronic_documents')
      ->whereNull('deleted_at')
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->whereIn('original_document_id', $invoiceIds)
      ->where(function ($q) use ($today) {
        $q->where('aceptada_por_sunat', true)
          ->orWhereDate('fecha_de_emision', $today);
      })
      ->selectRaw('original_document_id, SUM(total) as total')
      ->groupBy('original_document_id')
      ->pluck('total', 'original_document_id');

    $ndByInvoice = DB::table('ap_billing_electronic_documents')
      ->whereNull('deleted_at')
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->whereIn('original_document_id', $invoiceIds)
      ->selectRaw('original_document_id, SUM(total) as total')
      ->groupBy('original_document_id')
      ->pluck('total', 'original_document_id');

    $tol = ElectronicDocument::ROUNDING_TOLERANCE;

    $quoteIds = [];
    $outOfPeriod = [];
    $refactured = [];

    foreach ($invoices->groupBy('purchase_request_quote_id') as $quoteId => $group) {
      $group = $group
        ->sortBy(fn($i) => sprintf('%s-%012d', substr((string) $i->fecha_de_emision, 0, 10), $i->id))
        ->values();
      $firstDate = substr((string) $group->first()->fecha_de_emision, 0, 10);

      $enriched = $group->map(function ($inv) use ($ncByInvoice, $ndByInvoice) {
        $nc = (float) ($ncByInvoice[$inv->id] ?? 0);
        $nd = (float) ($ndByInvoice[$inv->id] ?? 0);
        return [
          'inv' => $inv,
          'nc' => $nc,
          'net' => (float) $inv->total - $nc + $nd,
        ];
      });

      $vigentes = $enriched->filter(fn($e) => round($e['net'], 2) > $tol)->values();
      if ($vigentes->isEmpty()) {
        // Venta anulada / descontada por NC y sin refacturación: no cuenta en ningún periodo.
        continue;
      }

      $cancelled = $enriched->filter(fn($e) => round($e['net'], 2) <= $tol)->values();
      $chosen = $vigentes->last();
      $isRefact = $cancelled->isNotEmpty();
      $isPartial = $chosen['nc'] > $tol;

      $effDate = $isRefact
        ? $firstDate
        : substr((string) $chosen['inv']->fecha_de_emision, 0, 10);

      $inRange = $effDate >= $start && $effDate <= $end;
      $chosenDate = substr((string) $chosen['inv']->fecha_de_emision, 0, 10);
      $invoicedInRange = $chosenDate >= $start && $chosenDate <= $end;

      if (!$inRange) {
        if ($invoicedInRange) {
          $outOfPeriod[] = (int) $quoteId;
        }
        continue;
      }

      $quoteIds[] = (int) $quoteId;

      if ($isRefact || $isPartial) {
        $orig = $cancelled->isNotEmpty() ? $cancelled->last() : $chosen;
        $refactured[(int) $quoteId] = [
          'quote_id' => (int) $quoteId,
          'comprobante_original_id' => $orig['inv']->id,
          'fecha_original' => substr((string) $orig['inv']->fecha_de_emision, 0, 10),
          'nc_total' => round($orig['nc'] ?: $chosen['nc'], 2),
          'comprobante_vigente_id' => $chosen['inv']->id,
          'fecha_vigente' => $chosenDate,
          'periodo_atribuido' => $effDate,
          'refacturacion' => $isRefact,
          'parcial' => $isPartial,
        ];
      }
    }

    return (object) [
      'quote_ids' => collect($quoteIds),
      'out_of_period' => collect($outOfPeriod),
      'refactured' => $refactured,
    ];
  }
}
