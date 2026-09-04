<?php

namespace App\Console\Commands;

use App\Http\Resources\Dynamics\AccountingEntryHeaderDynamicsResource;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccountingEntryRefacturationReportCommand extends Command
{
  protected $signature = 'accounting-entry:refacturation-report
    {--csv   : Exportar como CSV en lugar de tabla}
    {--sede= : Filtrar por dyn_code de sede (ej. GPCAJ)}';

  protected $description = 'Detecta refacturaciones donde se envió la referencia incorrecta a GPIN. Solo muestra casos accionables (EN GPIN INCORRECTO o PROCESADO INCORRECTO). Los NO ENVIADO se omiten porque el job ya tiene el fix y los enviará correctamente.';

  // Solo los estados que representan un problema real
  private const S_GPIN_WRONG      = 'EN GPIN INCORRECTO';   // ref incorrecta en GPIN, Estado=0 → hay que limpiar y re-enviar
  private const S_GPIN_BOTH       = 'EN GPIN AMBOS';        // ambas refs en GPIN → limpiar la incorrecta
  private const S_PROCESSED_WRONG = 'PROCESADO INCORRECTO'; // GP ya la procesó con ref mala → corregir en Dynamics

  public function handle(): int
  {
    $this->info('Detectando refacturaciones...');

    // ── 1. Facturas/boletas de venta final ────────────────────────────────────
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

    // ── 2. NC aplicadas ───────────────────────────────────────────────────────
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

    // ── 3. Detectar refacturaciones ───────────────────────────────────────────
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
          'invoice'   => $inv,
          'ncs'       => $ncs,
          'total_nc'  => $totalNc,
          'net'       => $net,
          'cancelled' => $totalNc > $tol && round($net, 2) <= $tol,
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

    // ── 4. Cargar ShippingGuides + migration logs para todos los VINs ─────────
    $vins = collect($refacturations)->map(function ($case) {
      $inv = $case['vigentes']->first()['invoice'];
      return $inv->vehicle?->vin ?? $inv->purchaseRequestQuote?->vehicle?->vin;
    })->filter()->unique()->values()->toArray();

    $guides = ShippingGuides::with([
      'vehicleMovement.vehicle',
      'migrationLogs' => fn($q) => $q->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER),
    ])
      ->where('transfer_reason_id', SunatConcepts::TRANSFER_REASON_VENTA)
      ->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('status_dynamic', 1)
      ->whereHas('sedeTransmitter', fn($q) => $q->where('empresa_id', Company::COMPANY_AP_ID))
      ->whereHas('vehicleMovement.vehicle', fn($q) => $q->whereIn('vin', $vins))
      ->whereExists(fn($q) => $q
        ->from('ap_vehicle_delivery')
        ->whereColumn('ap_vehicle_delivery.shipping_guide_id', 'shipping_guides.id')
        ->whereNull('ap_vehicle_delivery.deleted_at')
      )
      ->get();

    // Indexar por VIN → guía (un VIN puede tener solo una guía VENTA)
    $guideByVin = $guides->keyBy(fn($g) => $g->vehicleMovement?->vehicle?->vin);

    // Migration log external_id indexado por VIN
    $logRefByVin = $guides->mapWithKeys(function ($g) {
      $vin = $g->vehicleMovement?->vehicle?->vin;
      $ref = $g->migrationLogs->first()?->external_id;
      return [$vin => $ref];
    });

    // ── 5. Construir referencias y consultarlas en GPIN ───────────────────────
    $allReferencias = [];
    foreach ($refacturations as $case) {
      $inv = $case['vigentes']->first()['invoice'];
      $vin = $inv->vehicle?->vin ?? $inv->purchaseRequestQuote?->vehicle?->vin;
      if (!$vin) {
        continue;
      }
      foreach ($case['cancelled'] as $e) {
        $allReferencias[] = AccountingEntryHeaderDynamicsResource::buildReferencia($e['invoice']->full_number, $vin);
      }
      foreach ($case['vigentes'] as $e) {
        $allReferencias[] = AccountingEntryHeaderDynamicsResource::buildReferencia($e['invoice']->full_number, $vin);
      }
    }

    $allReferencias = array_values(array_unique(array_filter($allReferencias)));

    $gpinRefs = [];
    if (!empty($allReferencias)) {
      $gpinRefs = DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->whereIn('Referencia', $allReferencias)
        ->pluck('Referencia')
        ->mapWithKeys(fn($ref) => [$ref => true])
        ->toArray();
    }

    // ── 6. Construir filas ────────────────────────────────────────────────────
    $sede = $this->option('sede');
    $rows = [];

    foreach ($refacturations as $case) {
      $vigente = $case['vigentes']->first();
      $cancelled = $case['cancelled']->last();

      $inv     = $vigente['invoice'];
      $origInv = $cancelled['invoice'];
      $nc      = $cancelled['ncs']->last();

      $prq         = $inv->purchaseRequestQuote;
      $vin         = $inv->vehicle?->vin ?? $prq?->vehicle?->vin ?? '';
      $sedeDynCode = $prq?->sede?->dyn_code ?? '';

      if ($sede && $sedeDynCode !== $sede) {
        continue;
      }
      if (empty($vin)) {
        continue;
      }

      $refCorrecto   = AccountingEntryHeaderDynamicsResource::buildReferencia($inv->full_number, $vin);
      $refIncorrecto = AccountingEntryHeaderDynamicsResource::buildReferencia($origInv->full_number, $vin);

      $inGpinCorrect   = array_key_exists($refCorrecto, $gpinRefs);
      $inGpinIncorrect = array_key_exists($refIncorrecto, $gpinRefs);
      $logRef          = $logRefByVin->get($vin); // qué referencia usó el job cuando corrió
      $guide           = $guideByVin->get($vin);

      // Solo interesan los casos con problema real
      if ($inGpinCorrect && !$inGpinIncorrect) {
        continue; // en GPIN con ref correcta → ok
      }
      if (!$inGpinCorrect && !$inGpinIncorrect && $logRef !== $refIncorrecto) {
        continue; // no enviado o enviado correctamente → el job ya tiene el fix, no hay acción
      }

      if ($inGpinCorrect && $inGpinIncorrect) {
        $estado = self::S_GPIN_BOTH;
      } elseif ($inGpinIncorrect) {
        $estado = self::S_GPIN_WRONG;
      } else {
        // logRef === $refIncorrecto y no está en GPIN → GP ya la procesó
        $estado = self::S_PROCESSED_WRONG;
      }

      $rows[] = [
        'sede'           => $sedeDynCode,
        'solicitud'      => $prq?->correlative ?? '-',
        'vin'            => $vin,
        'comp_original'  => $origInv->full_number,
        'fecha_original' => $origInv->fecha_de_emision?->format('Y-m-d') ?? '-',
        'nc'             => $nc ? "{$nc->serie}-{$nc->numero}" : '-',
        'comp_vigente'   => $inv->full_number,
        'fecha_vigente'  => $inv->fecha_de_emision?->format('Y-m-d') ?? '-',
        'ref_incorrecta' => $refIncorrecto,
        'ref_correcta'   => $refCorrecto,
        'log_ref'        => $logRef ?? '-',
        'estado'         => $estado,
        '_vin'           => $vin,
        '_guide'         => $guide,
      ];
    }

    // ── 7. Resumen y salida ───────────────────────────────────────────────────
    $counts = array_count_values(array_column($rows, 'estado'));
    $this->line(sprintf(
      'Casos con problema: %d | En GPIN incorrecto: %d | Procesado incorrecto (Dynamics): %d | Doble en GPIN: %d',
      count($rows),
      $counts[self::S_GPIN_WRONG]      ?? 0,
      $counts[self::S_PROCESSED_WRONG] ?? 0,
      $counts[self::S_GPIN_BOTH]       ?? 0,
    ));
    $this->newLine();

    $displayRows = array_map(function ($r) {
      return [
        $r['sede'], $r['solicitud'], $r['vin'],
        $r['comp_original'], $r['fecha_original'], $r['nc'],
        $r['comp_vigente'], $r['fecha_vigente'],
        $r['ref_incorrecta'], $r['ref_correcta'],
        $r['log_ref'], $r['estado'],
      ];
    }, $rows);

    if ($this->option('csv')) {
      $headers = ['sede','solicitud','vin','comp_original','fecha_original','nc',
        'comp_vigente','fecha_vigente','ref_incorrecta','ref_correcta','log_ref','estado'];
      $this->line(implode(',', $headers));
      foreach ($displayRows as $row) {
        $this->line(implode(',', $row));
      }
      return 0;
    }

    $this->table(
      ['Sede','Solicitud','VIN','Comp. original','Fecha orig.','NC',
        'Comp. vigente','Fecha vigente','Ref. incorrecta','Ref. correcta','Ref. en log','Estado'],
      $displayRows
    );

    return 0;
  }

}
