<?php

namespace App\Console\Commands;

use App\Http\Resources\Dynamics\AccountingEntryHeaderDynamicsResource;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccountingEntryRefacturationReportCommand extends Command
{
  protected $signature = 'accounting-entry:refacturation-report
    {--csv         : Exportar como CSV en lugar de tabla}
    {--wrong       : Mostrar solo casos con referencia incorrecta en GPIN}
    {--not-sent    : Mostrar solo casos sin ningún asiento en GPIN}
    {--sede=       : Filtrar por dyn_code de sede (ej. GPCAJ)}';

  protected $description = 'Reporte de asientos contables enviados a GPIN en casos de refacturación (boleta/factura → NC → nueva boleta/factura)';

  private const STATUS_CORRECT   = 'CORRECTO';
  private const STATUS_WRONG     = 'INCORRECTO';
  private const STATUS_NOT_SENT  = 'NO ENVIADO';
  private const STATUS_BOTH      = 'AMBOS (doble)';

  public function handle(): int
  {
    $this->info('Detectando refacturaciones...');

    // 1. Obtener todas las facturas/boletas de venta final agrupadas por solicitud
    $invoices = ElectronicDocument::with([
      'purchaseRequestQuote.sede',
      'purchaseRequestQuote.vehicle',
      'vehicle',
    ])
      ->whereNotNull('purchase_request_quote_id')
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA,
      ])
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->orderBy('fecha_de_emision')
      ->orderBy('id')
      ->get();

    if ($invoices->isEmpty()) {
      $this->warn('No se encontraron facturas/boletas.');
      return 0;
    }

    $invoiceIds = $invoices->pluck('id');

    // 2. NC aplicadas a esas facturas
    $ncRows = DB::table('ap_billing_electronic_documents')
      ->whereNull('deleted_at')
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->whereIn('original_document_id', $invoiceIds)
      ->select('id', 'original_document_id', 'serie', 'numero', 'total', 'fecha_de_emision')
      ->get()
      ->groupBy('original_document_id');

    $tol = ElectronicDocument::ROUNDING_TOLERANCE;

    // 3. Detectar refacturaciones: solicitudes con ≥1 cancelado por NC y ≥1 vigente
    $refacturations = [];

    foreach ($invoices->groupBy('purchase_request_quote_id') as $prqId => $prqInvoices) {
      if ($prqInvoices->count() < 2) {
        continue;
      }

      $enriched = $prqInvoices->map(function ($inv) use ($ncRows, $tol) {
        $ncs     = $ncRows->get($inv->id, collect());
        $totalNc = (float) $ncs->sum('total');
        $net     = (float) $inv->total - $totalNc;
        return [
          'invoice'    => $inv,
          'ncs'        => $ncs,
          'total_nc'   => $totalNc,
          'net'        => $net,
          'cancelled'  => $totalNc > $tol && round($net, 2) <= $tol,
        ];
      });

      $cancelled = $enriched->where('cancelled', true)->values();
      $vigentes  = $enriched->where('cancelled', false)->values();

      if ($cancelled->isEmpty() || $vigentes->isEmpty()) {
        continue;
      }

      $refacturations[] = [
        'prq_id'    => $prqId,
        'cancelled' => $cancelled,
        'vigentes'  => $vigentes,
      ];
    }

    $this->info('Refacturaciones detectadas: ' . count($refacturations));
    $this->newLine();

    if (empty($refacturations)) {
      $this->info('No hay casos de refacturación.');
      return 0;
    }

    // 4. Obtener todas las referencias posibles y consultarlas en GPIN de una sola vez
    $allReferencias = [];
    foreach ($refacturations as $case) {
      $vin = $case['vigentes']->first()['invoice']->vehicle?->vin
        ?? $case['vigentes']->first()['invoice']->purchaseRequestQuote?->vehicle?->vin;

      if (!$vin) {
        continue;
      }

      foreach ($case['cancelled'] as $e) {
        $allReferencias[] = AccountingEntryHeaderDynamicsResource::buildReferencia(
          $e['invoice']->full_number, $vin
        );
      }
      foreach ($case['vigentes'] as $e) {
        $allReferencias[] = AccountingEntryHeaderDynamicsResource::buildReferencia(
          $e['invoice']->full_number, $vin
        );
      }
    }

    $allReferencias = array_unique(array_filter($allReferencias));

    $gpinRefs = DB::connection('dbtp')
      ->table('neInTbIntegracionAsientoCab')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->whereIn('Referencia', $allReferencias)
      ->pluck('Referencia')
      ->flip(); // keyed by referencia for O(1) lookup

    // 5. Construir filas del reporte
    $sede = $this->option('sede');
    $rows = [];

    foreach ($refacturations as $case) {
      $vigente   = $case['vigentes']->first();
      $cancelled = $case['cancelled']->last();

      $inv        = $vigente['invoice'];
      $origInv    = $cancelled['invoice'];
      $nc         = $cancelled['ncs']->last();

      $prq = $inv->purchaseRequestQuote;
      $vin = $inv->vehicle?->vin ?? $prq?->vehicle?->vin ?? '';

      // Filtro por sede
      $sedeDynCode = $prq?->sede?->dyn_code ?? '';
      if ($sede && $sedeDynCode !== $sede) {
        continue;
      }

      if (empty($vin)) {
        continue;
      }

      $refCorrecto   = AccountingEntryHeaderDynamicsResource::buildReferencia($inv->full_number, $vin);
      $refIncorrecto = AccountingEntryHeaderDynamicsResource::buildReferencia($origInv->full_number, $vin);

      $inGpinCorrect   = isset($gpinRefs[$refCorrecto]);
      $inGpinIncorrect = isset($gpinRefs[$refIncorrecto]);

      if ($inGpinCorrect && $inGpinIncorrect) {
        $estado = self::STATUS_BOTH;
      } elseif ($inGpinCorrect) {
        $estado = self::STATUS_CORRECT;
      } elseif ($inGpinIncorrect) {
        $estado = self::STATUS_WRONG;
      } else {
        $estado = self::STATUS_NOT_SENT;
      }

      $rows[] = [
        'sede'             => $sedeDynCode,
        'solicitud'        => $prq?->correlative ?? '-',
        'vin'              => $vin,
        'comp_original'    => $origInv->full_number,
        'fecha_original'   => $origInv->fecha_de_emision?->format('Y-m-d') ?? '-',
        'nc'               => $nc ? "{$nc->serie}-{$nc->numero}" : '-',
        'comp_vigente'     => $inv->full_number,
        'fecha_vigente'    => $inv->fecha_de_emision?->format('Y-m-d') ?? '-',
        'ref_incorrecta'   => $refIncorrecto,
        'ref_correcta'     => $refCorrecto,
        'estado'           => $estado,
      ];
    }

    // 6. Aplicar filtros de opción
    if ($this->option('wrong')) {
      $rows = array_filter($rows, fn($r) => in_array($r['estado'], [self::STATUS_WRONG, self::STATUS_BOTH]));
    } elseif ($this->option('not-sent')) {
      $rows = array_filter($rows, fn($r) => $r['estado'] === self::STATUS_NOT_SENT);
    }

    $rows = array_values($rows);

    // Conteos resumen
    $counts = array_count_values(array_column($rows, 'estado'));
    $this->line(sprintf(
      'Total: %d | Correctos: %d | Incorrectos: %d | No enviados: %d | Dobles: %d',
      count($rows),
      $counts[self::STATUS_CORRECT]  ?? 0,
      $counts[self::STATUS_WRONG]    ?? 0,
      $counts[self::STATUS_NOT_SENT] ?? 0,
      $counts[self::STATUS_BOTH]     ?? 0,
    ));
    $this->newLine();

    if (empty($rows)) {
      $this->info('No hay casos para mostrar con los filtros aplicados.');
      return 0;
    }

    // 7. Salida
    if ($this->option('csv')) {
      $headers = ['sede', 'solicitud', 'vin', 'comp_original', 'fecha_original', 'nc',
        'comp_vigente', 'fecha_vigente', 'ref_incorrecta', 'ref_correcta', 'estado'];
      $this->line(implode(',', $headers));
      foreach ($rows as $row) {
        $this->line(implode(',', array_values($row)));
      }
      return 0;
    }

    $this->table(
      ['Sede', 'Solicitud', 'VIN', 'Comp. original', 'Fecha orig.', 'NC',
        'Comp. vigente', 'Fecha vigente', 'Ref. incorrecta', 'Ref. correcta', 'Estado GPIN'],
      array_map('array_values', $rows)
    );

    return 0;
  }
}
