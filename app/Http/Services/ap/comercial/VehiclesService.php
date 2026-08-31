<?php

namespace App\Http\Services\ap\comercial;

use App\Exports\ap\comercial\VehiclesBillingExport;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\compras\PurchaseOrderResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Exports\GeneralExport;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Http\Utils\Constants;
use App\Imports\ap\comercial\VehicleUpdateByVinImport;
use App\Imports\ap\comercial\VehiclePurchaseOrderUpdateByVinImport;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ApReceivingAccessoryStatus;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VehiclesService extends BaseService implements BaseServiceInterface
{

  public function exportAll(Request $request)
  {
    $request->merge([
      'title' => $request->get('title', 'Reporte General de Vehículos'),
    ]);

    $exportService = new ExportService();
    return $exportService->exportFromRequest($request, Vehicles::class);
  }

  /**
   * Reporte de Facturación Comercial (3 hojas).
   *
   * Reglas:
   *  - "Facturación" = facturas + boletas aceptadas por SUNAT, no anticipo, no anuladas.
   *  - Una NC "aplica" (reduce el neto y anula/descuenta el comprobante) si:
   *      anulado = false  Y  ( aceptada_por_sunat = true  OR  fecha_de_emision = hoy ).
   *    Es decir: una NC del mismo día en que se genera el reporte cuenta aunque aún no
   *    esté aceptada; si es de un día anterior y sigue sin aceptarse, ya no cuenta.
   *  - Una ND solo cuenta si está aceptada por SUNAT.
   *  - Se agrupa por purchase_request_quote_id (1 fila por solicitud, sin duplicar VIN).
   *  - Neto de un comprobante = total - Σ NC aplicadas + Σ ND aplicadas.
   *  - Comprobante vigente = neto > ROUNDING_TOLERANCE.
   *  - Solicitud sin vigente (NC anula/descuenta todo y no hubo refacturación) => no aparece
   *    en la hoja principal, solo en la hoja de Notas de Crédito.
   *  - Refacturación: si la solicitud tiene ≥1 comprobante anulado por NC y 1 vigente
   *    posterior, el vigente se atribuye al periodo del PRIMER comprobante de la cadena.
   */
  public function exportBilling(Request $request)
  {
    $today = now()->toDateString();

    $dates = $request->get('fecha_de_emision');
    $hasRange = is_array($dates) && count($dates) === 2 && $dates[0] && $dates[1];
    $start = $hasRange ? substr($dates[0], 0, 10) : null;
    $end = $hasRange ? substr($dates[1], 0, 10) : null;

    $sedeId = $request->filled('sede_id') ? $request->get('sede_id') : null;

    // 1. Comprobantes base (facturas + boletas), aceptados o no por SUNAT.
    //    Los no aceptados también se muestran, con "NO" en la columna ACEPTADA POR SUNAT.
    //    No se filtra por fecha aquí: se necesita toda la cadena de la solicitud
    //    para detectar refacturaciones y atribuir el periodo correcto.
    $invoiceQuery = ElectronicDocument::with([
      'purchaseRequestQuote.sede',
      'purchaseRequestQuote.holder.typePerson',
      'purchaseRequestQuote.opportunity.worker',
      'purchaseRequestQuote.vehicle.model.family.brand',
      'purchaseRequestQuote.vehicle.color',
      'purchaseRequestQuote.creditType',
      'purchaseRequestQuote.creditEntity',
      'purchaseRequestQuote.insuranceEntity',
      'receivableAccounts',
      'bank',
    ])
      ->whereNotNull('purchase_request_quote_id')
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA,
      ])
      ->where('is_advance_payment', false)
      ->where('anulado', false);

    if ($sedeId) {
      $invoiceQuery->whereHas('purchaseRequestQuote', function ($q) use ($sedeId) {
        $q->where('sede_id', $sedeId);
      });
    }

    $invoices = $invoiceQuery->orderBy('fecha_de_emision')->orderBy('id')->get();
    $invoicesById = $invoices->keyBy('id');
    $invoiceIds = $invoices->pluck('id')->all();

    // 2. Notas de crédito que aplican a esos comprobantes.
    $creditNotes = collect();
    $debitNotes = collect();
    if (!empty($invoiceIds)) {
      $creditNotes = ElectronicDocument::with('creditNoteType')
        ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
        ->where('is_advance_payment', false)
        ->where('anulado', false)
        ->whereIn('original_document_id', $invoiceIds)
        ->where(function ($q) use ($today) {
          $q->where('aceptada_por_sunat', true)
            ->orWhereDate('fecha_de_emision', $today);
        })
        ->orderBy('fecha_de_emision')->orderBy('id')
        ->get();

      $debitNotes = ElectronicDocument::where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
        ->where('is_advance_payment', false)
        ->where('anulado', false)
        ->where('aceptada_por_sunat', true)
        ->whereIn('original_document_id', $invoiceIds)
        ->get();
    }

    $ncByInvoice = $creditNotes->groupBy('original_document_id');
    $ndByInvoice = $debitNotes->groupBy('original_document_id');
    $tol = ElectronicDocument::ROUNDING_TOLERANCE;

    $mainRows = [];
    $creditNoteRows = [];
    $refactRows = [];
    $referencedNcIds = [];

    foreach ($invoices->groupBy('purchase_request_quote_id') as $prqInvoices) {
      $prqInvoices = $prqInvoices
        ->sortBy(fn($i) => [$i->fecha_de_emision?->timestamp ?? 0, $i->id])
        ->values();
      $firstInvoice = $prqInvoices->first();

      $enriched = $prqInvoices->map(function ($inv) use ($ncByInvoice, $ndByInvoice) {
        $ncs = $ncByInvoice->get($inv->id, collect());
        $nds = $ndByInvoice->get($inv->id, collect());
        $totalNc = (float) $ncs->sum('total');
        $totalNd = (float) $nds->sum('total');
        $gross = (float) $inv->total;
        return [
          'invoice' => $inv,
          'ncs' => $ncs,
          'total_nc' => $totalNc,
          'total_nd' => $totalNd,
          'gross' => $gross,
          'net' => $gross - $totalNc + $totalNd,
        ];
      });

      // Un comprobante se considera "anulado por NC" solo si tiene una NC que lleva
      // su neto a ~0. Un comprobante de importe 0 sin NC NO se anula: sigue vigente.
      $wasCancelledByNc = fn($e) => $e['total_nc'] > $tol && round($e['net'], 2) <= $tol;
      $cancelled = $enriched->filter($wasCancelledByNc)->values();
      $vigentes = $enriched->reject($wasCancelledByNc)->values();

      // Registrar todas las NC de la solicitud como referenciadas (trazabilidad).
      foreach ($enriched as $e) {
        foreach ($e['ncs'] as $nc) {
          $creditNoteRows[] = $this->buildBillingCreditNoteRow($nc, $e['invoice'], 'REFERENCIADA');
          $referencedNcIds[] = $nc->id;
        }
      }

      if ($vigentes->isEmpty()) {
        // Solicitud totalmente anulada/descontada por NC y sin refacturación: no va a la hoja principal.
        continue;
      }

      // Comprobante vigente: se prefiere uno aceptado por SUNAT; a igualdad, el más reciente.
      $chosen = $vigentes
        ->sortBy(fn($e) => sprintf(
          '%d-%s-%012d',
          $e['invoice']->aceptada_por_sunat ? 1 : 0,
          substr((string) $e['invoice']->fecha_de_emision, 0, 10),
          $e['invoice']->id
        ))
        ->last();
      $vigInvoice = $chosen['invoice'];
      $prq = $vigInvoice->purchaseRequestQuote;

      $isRefact = $cancelled->isNotEmpty();
      // Caso anómalo (no debería existir): NC parcial sobre el comprobante vigente.
      $isPartial = $chosen['total_nc'] > $tol;

      $attributedDate = $isRefact ? $firstInvoice->fecha_de_emision : $vigInvoice->fecha_de_emision;

      // Filtro de periodo por fecha atribuida.
      if ($hasRange) {
        $ad = $attributedDate?->toDateString();
        $vigDate = $vigInvoice->fecha_de_emision?->toDateString();
        if ($ad === null || $ad < $start || $ad > $end) {
          // Fuera de periodo. Si es refacturación y el comprobante vigente SÍ se emitió
          // en este rango, dejar rastro en la hoja de Refacturaciones: explica por qué
          // una factura/boleta de este periodo no aparece en la hoja principal.
          if ($isRefact && $vigDate !== null && $vigDate >= $start && $vigDate <= $end) {
            $origEnriched = $cancelled->isNotEmpty() ? $cancelled->last() : $chosen;
            $origInvoice = $origEnriched['invoice'];
            $origNc = $origEnriched['ncs']->last();
            $refactRows[] = [
              'tipo' => 'REFACTURADA A OTRO PERIODO',
              'solicitud' => $prq?->correlative,
              'vin' => $prq?->vehicle?->vin,
              'cliente' => $prq?->holder?->full_name,
              'comprobante_original' => $origInvoice->full_number,
              'fecha_original' => $origInvoice->fecha_de_emision?->format('d/m/Y'),
              'monto_original' => (float) $origInvoice->total,
              'nc' => $origNc?->full_number,
              'fecha_nc' => $origNc?->fecha_de_emision?->format('d/m/Y'),
              'monto_nc' => $origNc ? (float) $origNc->total : null,
              'comprobante_nuevo' => $vigInvoice->full_number,
              'fecha_nuevo' => $vigInvoice->fecha_de_emision?->format('d/m/Y'),
              'monto_nuevo' => (float) $vigInvoice->total,
              'periodo_atribuido' => $attributedDate?->format('m/Y'),
              'observacion' => 'El comprobante nuevo se emitió en este periodo, pero la venta se atribuye al periodo del comprobante original (refacturación).',
            ];
          }
          continue;
        }
      }

      $observacion = null;
      if ($isPartial) {
        $observacion = 'NC PARCIAL — revisar: la NC no anula ni descuenta el total del comprobante.';
      }

      $totalBalance = $vigInvoice->receivableAccounts->sum('balance');
      $hasReceivable = $vigInvoice->receivableAccounts->isNotEmpty();
      $collectionRefs = $vigInvoice->receivableAccounts
        ->whereNotNull('collection_reference')
        ->pluck('collection_reference')
        ->unique()
        ->implode("\n");

      $facturaNeta = round($chosen['net'], 2);

      if (!$vigInvoice->is_accounted) {
        $estado = 'NO CONTABILIZADO';
        $pendiente = $facturaNeta;
      } elseif (!$hasReceivable || $totalBalance == 0) {
        $estado = 'CANCELADO';
        $pendiente = 0.0;
      } else {
        $estado = 'PENDIENTE';
        $pendiente = (float) $totalBalance;
      }

      $mainRows[] = [
        'solicitud' => $prq?->correlative,
        'sede' => $prq?->sede?->abreviatura,
        'tipo_persona' => $prq?->holder?->typePerson?->description,
        'dni' => $prq?->holder?->num_doc,
        'cliente' => $prq?->holder?->full_name,
        'asesor' => $prq?->opportunity?->worker?->nombre_completo,
        'marca' => $prq?->vehicle?->model?->family?->brand?->name,
        'modelo' => $prq?->vehicle?->model?->version,
        'vin' => $prq?->vehicle?->vin,
        'color' => $prq?->vehicle?->color?->description,
        'numero_documento' => $vigInvoice->full_number,
        'fecha_factura' => $vigInvoice->fecha_de_emision?->format('d/m/Y'),
        'fecha_atribuida' => $attributedDate?->format('d/m/Y'),
        'refacturacion' => $isRefact ? 'SÍ' : 'NO',
        'pct_beneficio' => $prq?->margin_pct,
        'beneficio' => is_numeric($prq?->margin_amount) ? (float) $prq->margin_amount : null,
        'total_factura' => $chosen['gross'],
        'total_nc' => $chosen['total_nc'] ?: null,
        'total_nd' => $chosen['total_nd'] ?: null,
        'factura_neta' => $facturaNeta,
        'pendiente' => $pendiente,
        'nc_asociada' => $chosen['ncs']->pluck('full_number')->implode("\n") ?: null,
        'ref_cancelacion' => $collectionRefs ?: null,
        'estado' => $estado,
        'forma_pago' => $vigInvoice->condiciones_de_pago,
        'banco' => $vigInvoice->bank?->description,
        'aceptada_sunat' => $vigInvoice->aceptada_por_sunat ? 'SÍ' : 'NO',
        'observacion' => $observacion,
        'tipo_credito' => $prq?->creditType?->description,
        'entidad_credito' => $prq?->creditEntity?->description,
        'entidad_seguro' => $prq?->insuranceEntity?->description,
        'gps_hunter' => $prq?->has_gps_hunter ? 'SÍ' : 'NO',
        'gps_hunter_anios' => $prq?->has_gps_hunter ? $prq?->gps_hunter_years : null,
      ];

      // Hoja de refacturaciones / NC parcial.
      if ($isRefact || $isPartial) {
        $origEnriched = $cancelled->isNotEmpty() ? $cancelled->last() : $chosen;
        $origInvoice = $origEnriched['invoice'];
        $origNc = $origEnriched['ncs']->last();
        $refactRows[] = [
          'tipo' => 'ATRIBUIDA A ESTE PERIODO',
          'solicitud' => $prq?->correlative,
          'vin' => $prq?->vehicle?->vin,
          'cliente' => $prq?->holder?->full_name,
          'comprobante_original' => $origInvoice->full_number,
          'fecha_original' => $origInvoice->fecha_de_emision?->format('d/m/Y'),
          'monto_original' => (float) $origInvoice->total,
          'nc' => $origNc?->full_number,
          'fecha_nc' => $origNc?->fecha_de_emision?->format('d/m/Y'),
          'monto_nc' => $origNc ? (float) $origNc->total : null,
          'comprobante_nuevo' => $isRefact ? $vigInvoice->full_number : null,
          'fecha_nuevo' => $isRefact ? $vigInvoice->fecha_de_emision?->format('d/m/Y') : null,
          'monto_nuevo' => $isRefact ? (float) $vigInvoice->total : null,
          'periodo_atribuido' => $attributedDate?->format('m/Y'),
          'observacion' => $isPartial
            ? 'NC PARCIAL — revisar: la NC no anula ni descuenta el total.'
            : 'Refacturación: se anuló el comprobante original con NC y se emitió uno nuevo.',
        ];
      }
    }

    // NC dentro del rango que aún no fueron listadas (p. ej. NC sin aceptar de días previos).
    if ($hasRange && !empty($invoiceIds)) {
      $rangeNc = ElectronicDocument::with('creditNoteType')
        ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
        ->where('is_advance_payment', false)
        ->where('anulado', false)
        ->whereIn('original_document_id', $invoiceIds)
        ->whereDate('fecha_de_emision', '>=', $start)
        ->whereDate('fecha_de_emision', '<=', $end)
        ->orderBy('fecha_de_emision')->orderBy('id')
        ->get();

      foreach ($rangeNc as $nc) {
        if (in_array($nc->id, $referencedNcIds, true)) {
          continue;
        }
        $creditNoteRows[] = $this->buildBillingCreditNoteRow(
          $nc,
          $invoicesById->get($nc->original_document_id),
          'EN RANGO'
        );
      }
    }

    $title = $request->get('title', 'Reporte de Facturación Comercial');
    $filename = \Str::slug($title) . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    $accountingUsd = '_($* #,##0.00_);_($* (#,##0.00);_($* "-"??_);_(@_)';

    $mainColumns = [
      'solicitud' => 'SOLICITUD',
      'sede' => 'SEDE',
      'tipo_persona' => 'TIPO DE PERSONA',
      'dni' => 'DNI',
      'cliente' => 'CLIENTE',
      'asesor' => 'ASESOR',
      'marca' => 'MARCA',
      'modelo' => 'MODELO',
      'vin' => 'VIN',
      'color' => 'COLOR',
      'numero_documento' => 'NUMERO DE DOCUMENTO',
      'fecha_factura' => 'FECHA COMPROBANTE',
      'fecha_atribuida' => 'FECHA ATRIBUIDA',
      'refacturacion' => 'REFACTURACIÓN',
      'pct_beneficio' => '% BENEFICIO',
      'beneficio' => 'BENEFICIO',
      'total_factura' => 'TOTAL FACTURA',
      'total_nc' => 'TOTAL NC',
      'total_nd' => 'TOTAL ND',
      'factura_neta' => 'FACTURA NETA',
      'pendiente' => 'PENDIENTE',
      'nc_asociada' => 'NC ASOCIADA',
      'ref_cancelacion' => 'REF CANCELACION',
      'estado' => 'ESTADO',
      'forma_pago' => 'FORMA DE PAGO',
      'banco' => 'BANCO',
      'aceptada_sunat' => 'ACEPTADA POR SUNAT',
      'observacion' => 'OBSERVACIÓN',
      'tipo_credito' => 'TIPO DE CRÉDITO',
      'entidad_credito' => 'ENTIDAD DE CRÉDITO',
      'entidad_seguro' => 'ENTIDAD DE SEGURO',
      'gps_hunter' => 'GPS HUNTER',
      'gps_hunter_anios' => 'AÑOS GPS HUNTER',
    ];

    $creditNoteColumns = [
      'solicitud' => 'SOLICITUD',
      'sede' => 'SEDE',
      'cliente' => 'CLIENTE',
      'vin' => 'VIN',
      'marca' => 'MARCA',
      'modelo' => 'MODELO',
      'asesor' => 'ASESOR',
      'nc' => 'NOTA DE CRÉDITO',
      'fecha_nc' => 'FECHA NC',
      'tipo_nc' => 'TIPO NC',
      'motivo' => 'MOTIVO',
      'monto_nc' => 'MONTO NC',
      'comprobante_origen' => 'COMPROBANTE ORIGEN',
      'fecha_comprobante_origen' => 'FECHA COMPROBANTE ORIGEN',
      'aceptada_sunat' => 'ACEPTADA POR SUNAT',
      'origen' => 'ORIGEN',
    ];

    $refactColumns = [
      'tipo' => 'TIPO',
      'solicitud' => 'SOLICITUD',
      'vin' => 'VIN',
      'cliente' => 'CLIENTE',
      'comprobante_original' => 'COMPROBANTE ORIGINAL',
      'fecha_original' => 'FECHA ORIGINAL',
      'monto_original' => 'MONTO ORIGINAL',
      'nc' => 'NOTA DE CRÉDITO',
      'fecha_nc' => 'FECHA NC',
      'monto_nc' => 'MONTO NC',
      'comprobante_nuevo' => 'COMPROBANTE NUEVO',
      'fecha_nuevo' => 'FECHA NUEVO',
      'monto_nuevo' => 'MONTO NUEVO',
      'periodo_atribuido' => 'PERIODO ATRIBUIDO',
      'observacion' => 'OBSERVACIÓN',
    ];

    $sheets = [
      [
        'title' => 'Facturación',
        'columns' => $mainColumns,
        'rows' => $mainRows,
        'cellColorRules' => [
          'estado' => [
            'CANCELADO' => ['bg' => 'DCFCE7', 'text' => '15803D', 'bold' => true],
            'PENDIENTE' => ['bg' => 'FFEDD5', 'text' => 'C2410C', 'bold' => true],
            'NO CONTABILIZADO' => ['bg' => 'F3F4F6', 'text' => '6B7280', 'bold' => true],
          ],
          'refacturacion' => [
            'SÍ' => ['bg' => 'FEF9C3', 'text' => '854D0E', 'bold' => true],
          ],
        ],
        'columnFormats' => [
          'beneficio' => $accountingUsd,
          'total_factura' => $accountingUsd,
          'total_nc' => $accountingUsd,
          'total_nd' => $accountingUsd,
          'factura_neta' => $accountingUsd,
          'pendiente' => $accountingUsd,
        ],
        'wrapTextColumns' => ['nc_asociada', 'ref_cancelacion', 'observacion'],
      ],
      [
        'title' => 'Notas de Crédito',
        'columns' => $creditNoteColumns,
        'rows' => $creditNoteRows,
        'cellColorRules' => [
          'aceptada_sunat' => [
            'NO' => ['bg' => 'FEE2E2', 'text' => 'B91C1C', 'bold' => true],
          ],
        ],
        'columnFormats' => [
          'monto_nc' => $accountingUsd,
        ],
        'wrapTextColumns' => ['motivo'],
      ],
      [
        'title' => 'Refacturaciones',
        'columns' => $refactColumns,
        'rows' => $refactRows,
        'columnFormats' => [
          'monto_original' => $accountingUsd,
          'monto_nc' => $accountingUsd,
          'monto_nuevo' => $accountingUsd,
        ],
        'wrapTextColumns' => ['observacion'],
      ],
    ];

    return \Maatwebsite\Excel\Facades\Excel::download(
      new VehiclesBillingExport($sheets),
      $filename
    );
  }

  /**
   * Arma una fila de la hoja "Notas de Crédito".
   *
   * @param  ElectronicDocument       $nc       Nota de crédito
   * @param  ElectronicDocument|null  $invoice  Comprobante de origen (con purchaseRequestQuote cargado)
   * @param  string                   $origen   'REFERENCIADA' | 'EN RANGO'
   */
  private function buildBillingCreditNoteRow(ElectronicDocument $nc, ?ElectronicDocument $invoice, string $origen): array
  {
    $prq = $invoice?->purchaseRequestQuote;

    return [
      'solicitud' => $prq?->correlative,
      'sede' => $prq?->sede?->abreviatura,
      'cliente' => $prq?->holder?->full_name,
      'vin' => $prq?->vehicle?->vin,
      'marca' => $prq?->vehicle?->model?->family?->brand?->name,
      'modelo' => $prq?->vehicle?->model?->version,
      'asesor' => $prq?->opportunity?->worker?->nombre_completo,
      'nc' => $nc->full_number,
      'fecha_nc' => $nc->fecha_de_emision?->format('d/m/Y'),
      'tipo_nc' => $nc->creditNoteType?->description,
      'motivo' => $nc->observaciones,
      'monto_nc' => (float) $nc->total,
      'comprobante_origen' => $invoice?->full_number,
      'fecha_comprobante_origen' => $invoice?->fecha_de_emision?->format('d/m/Y'),
      'aceptada_sunat' => $nc->aceptada_por_sunat ? 'SÍ' : 'NO',
      'origen' => $origen,
    ];
  }

  public function exportDelivery(Request $request)
  {
    $request->merge([
      'columns' => [
        'electronicDocumentParent.identityDocumentType.description',
        'electronicDocumentParent.cliente_denominacion',
        'electronicDocumentParent.cliente_numero_de_documento',
        'model.family.brand.name',
        'model.family.description',
        'vin',
        'plate',
        'electronicDocumentParent.seriesModel.sede.shop.description',
        'electronicDocumentParent.sale_date',
        'vehicleDelivery.real_delivery_date',
        'electronicDocumentParent.cliente_email',
        'electronicDocumentParent.client_phone',
        'vehicleDelivery.advisor.nombre_completo',
      ],
      'title' => $request->get('title', 'Consolidado Entregas Vehículos Nuevos'),
    ]);

    $exportService = new ExportService();
    return $exportService->exportFromRequest($request, Vehicles::class);
  }

  public function exportInventory(Request $request)
  {
    $query = Vehicles::with([
      'model.family.brand',
      'model.fuelType',
      'color',
      'vehicleStatus',
      'warehouse.sede',
      'purchaseOrder',
      'purchaseRequestQuote.opportunity.worker',
      'purchaseRequestQuote.holder',
    ])
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->whereNotIn('ap_vehicle_status_id', [
        ApVehicleStatus::VENDIDO_ENTREGADO,
      ]);

    if ($request->filled('emission_date')) {
      $dates = $request->get('emission_date');
      if (is_array($dates) && count($dates) === 2) {
        $query->whereHas('purchaseOrder', function ($q) use ($dates) {
          $q->whereDate('emission_date', '>=', $dates[0])
            ->whereDate('emission_date', '<=', $dates[1]);
        });
      }
    }

    if ($request->filled('sede_id')) {
      $sedeId = $request->get('sede_id');
      $query->whereHas('warehouse', function ($q) use ($sedeId) {
        $q->where('sede_id', $sedeId);
      });
    }

    $vehicles = $query->orderBy('ap_vehicle_status_id')->get();

    $columns = [
      'estado' => 'ESTADO',
      'fecha_emision' => 'FECHA EMISION OC',
      'importe_inicial' => 'IMPORTE INICIAL',
      'numero_factura' => 'NUMERO FACTURA OC',
      'marca' => 'MARCA VEHICULO',
      'modelo' => 'MODELO VEHICULO',
      'color' => 'COLOR VEHICULO',
      'anio_modelo' => 'AÑO MODELO',
      'combustible' => 'TIPO COMBUSTIBLE',
      'vin' => 'VIN',
      'serie_motor' => 'SERIE MOTOR',
      'sede' => 'SEDE',
      'almacen' => 'ALMACEN',
      'dias_vencidos' => 'DIAS EN STOCK',
      'solicitud' => 'SOLICITUD',
      'cliente' => 'CLIENTE',
      'asesor' => 'ASESOR',
    ];

    $rows = $vehicles->map(function ($vehicle) {
      $po = $vehicle->purchaseOrder;
      $emissionDate = $po?->emission_date;
      $diasVencidos = $emissionDate ? (int)$emissionDate->diffInDays(now()) : null;

      $invoiceNumber = null;
      if ($po?->invoice_series || $po?->invoice_number) {
        $invoiceNumber = trim(($po->invoice_series ?? '') . '-' . ($po->invoice_number ?? ''), '-');
      }

      $quote = $vehicle->purchaseRequestQuote;

      return [
        'estado' => $vehicle->vehicleStatus?->description,
        'fecha_emision' => $emissionDate?->format('d/m/Y'),
        'importe_inicial' => $po?->total,
        'numero_factura' => $invoiceNumber,
        'marca' => $vehicle->model?->family?->brand?->name,
        'modelo' => $vehicle->model?->version,
        'color' => $vehicle->color?->description,
        'anio_modelo' => $vehicle->model?->model_year,
        'combustible' => $vehicle->model?->fuelType?->description,
        'vin' => $vehicle->vin,
        'serie_motor' => $vehicle->engine_number,
        'sede' => $vehicle->warehouse?->sede?->abreviatura,
        'almacen' => $vehicle->warehouse?->description,
        'dias_vencidos' => $diasVencidos,
        'solicitud' => $quote ? 'COT-' . $quote->correlative : null,
        'cliente' => $quote?->holder?->full_name,
        'asesor' => $quote?->opportunity?->worker?->nombre_completo,
      ];
    });

    $title = $request->get('title', 'Reporte de Inventario de Vehículos');
    $filename = \Str::slug($title) . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
      new GeneralExport($rows, $columns, $title),
      $filename
    );
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Vehicles::with('purchaseOrder'),
      $request,
      Vehicles::filters,
      Vehicles::sorts,
      VehiclesResource::class
    );
  }

  public function find($id): Vehicles
  {
    $vehicle = Vehicles::where('id', $id)->first();
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }
    return $vehicle;
  }

  public function store(mixed $data, bool $skipMovement = false): JsonResource
  {
    DB::beginTransaction();
    try {
      // Enriquecer datos del vehículo
      $data = $this->enrichData($data);

      // Crear el vehículo
      $vehicle = Vehicles::create($data);

      // Para vehículos comerciales (consignación) no se crea movimiento aquí.
      // El primer y único movimiento inicial es EN CONSIGNACION, generado al crear la guía de consignación.

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function storeReplacement(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      // Enriquecer datos del vehículo
      $data['year'] = now()->year;
      $data['year_delivery'] = now()->year;
      $data['vehicle_color_id'] = ApMasters::COLOR_OTHERS_ID;
      $data['engine_type_id'] = ApMasters::ENGINE_TYPE_OTHERS_ID;
      $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
      $data['type_operation_id'] = ApMasters::TIPO_OPERACION_POSTVENTA;

      // Obtener el almacén físico de postventa usando la sede
      $warehouse = Warehouse::getPhysicalWarehouseForPostsale($data['sede_id']);

      if (!$warehouse) {
        throw new Exception("No se encontró un almacén físico de postventa para la sede especificada");
      }

      // Setear warehouse_id y warehouse_physical_id con el mismo valor
      $data['warehouse_id'] = $warehouse->id;
      $data['warehouse_physical_id'] = $warehouse->id;

      // Crear el vehículo
      $vehicle = Vehicles::create($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  protected function enrichData(mixed $data)
  {
    // Establecer estado inicial del vehículo
    if (!isset($data['ap_vehicle_status_id'])) {
      $data['ap_vehicle_status_id'] = ApVehicleStatus::VENDIDO_ENTREGADO;
    }

    // Validar que el VIN no exista
    $existingVehicle = Vehicles::where('vin', $data['vin'])
      ->whereNull('deleted_at')
      ->where('status', 1)
      ->first();

    if ($existingVehicle) {
      throw new Exception("El VIN {$data['vin']} ya existe en el sistema");
    }

    // Validar que el número de motor no exista
    $existingEngine = Vehicles::where('engine_number', $data['engine_number'])
      ->whereNull('deleted_at')
      ->where('status', 1)
      ->first();

    if ($existingEngine) {
      throw new Exception("El número de motor {$data['engine_number']} ya existe en el sistema");
    }

    if (!$data['type_operation_id']) $data['type_operation_id'] = ApMasters::TIPO_OPERACION_POSTVENTA;

    if (!isset($data['warehouse_id'])) {
      $data['warehouse_id'] = $data['warehouse_physical_id'] ?? null;
    }

    return $data;
  }

  /**
   * Muestra un vehículo por ID
   * @param int $id
   * @return VehiclesResource
   * @throws Exception
   */
  public function show(int $id): JsonResource
  {
    $vehicle = $this->find($id);
    return new VehiclesResource($vehicle);
  }

  /**
   * Actualiza un vehículo
   * @param mixed $data
   * @return Vehicles
   * @throws Exception|Throwable
   */
  public function update(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      $vehicle = $this->find($data['id']);

      // Si es del área comercial, solo permitir editar placa, titular, año de entrega, año e is_heavy
      if ($vehicle->type_operation_id === ApMasters::TIPO_OPERACION_COMERCIAL) {
        // Campos críticos que no pueden cambiar
        $criticalFields = [
          'vin' => 'VIN',
          'ap_models_vn_id' => 'modelo',
          'vehicle_color_id' => 'color',
          'engine_type_id' => 'tipo de motor',
        ];

        // Verificar que no estén intentando cambiar campos críticos
        $changedFields = [];
        foreach ($criticalFields as $field => $label) {
          if (isset($data[$field]) && $data[$field] != $vehicle->$field) {
            $changedFields[] = $label;
          }
        }

        if (!empty($changedFields)) {
          $fieldNames = implode(', ', $changedFields);
          throw new Exception("No se puede modificar los siguientes campos en un vehículo del ÁREA COMERCIAL: $fieldNames. Solo se permite editar la placa, titular (cliente), año, año de entrega e is_heavy.");
        }

        // Filtrar solo los campos permitidos para actualización
        $allowedFields = ['plate', 'customer_id', 'year', 'year_delivery', 'is_heavy', 'engine_number'];
        $data = array_intersect_key($data, array_flip($allowedFields));
        $data['id'] = $vehicle->id; // Mantener el ID

        // No permitir actualizar estos campos aunque se envíen en comercial
        unset($data['warehouse_physical_id']);
        unset($data['warehouse_id']);
      }

      // Si se actualiza el VIN, validar que no exista
      if (isset($data['vin']) && $data['vin'] !== $vehicle->vin) {
        $existingVehicle = Vehicles::where('vin', $data['vin'])
          ->where('id', '!=', $vehicle->id)
          ->whereNull('deleted_at')
          ->first();

        if ($existingVehicle) {
          throw new Exception("El VIN {$data['vin']} ya existe en el sistema");
        }
      }

      // Si se actualiza el número de motor, validar que no exista
      if (isset($data['engine_number']) && $data['engine_number'] !== $vehicle->engine_number) {
        $existingEngine = Vehicles::where('engine_number', $data['engine_number'])
          ->where('id', '!=', $vehicle->id)
          ->whereNull('deleted_at')
          ->first();

        if ($existingEngine) {
          throw new Exception("El número de motor {$data['engine_number']} ya existe en el sistema");
        }
      }

      // No permitir actualizar warehouse_physical_id aunque se envíe
      unset($data['warehouse_physical_id']);
      unset($data['warehouse_id']);

      $vehicle->update($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina un vehículo (soft delete)
   * @param $id
   * @return void
   * @throws Exception|Throwable
   */
  public function destroy($id): array
  {
    DB::beginTransaction();
    try {
      $vehicle = $this->find($id);
      $vehicle->delete();
      DB::commit();
      return ['message' => 'Vehículo eliminado correctamente'];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function updateByVin(UploadedFile $file): array
  {
    $import = new VehicleUpdateByVinImport();
    Excel::import($import, $file);
    return $import->getResults();
  }

  public function updatePurchaseOrderByVin(UploadedFile $file): array
  {
    $import = new VehiclePurchaseOrderUpdateByVinImport();
    Excel::import($import, $file);
    return $import->getResults();
  }

  /**
   * Lista todos los vehículos con sus costos (sin movements)
   * @param Request $request
   * @return mixed
   */
  public function listWithCosts(Request $request)
  {
    $isEditing = filter_var($request->get('is_editing', false), FILTER_VALIDATE_BOOLEAN);
    $excludeQuoteId = $request->get('purchase_request_quote_id');

    $allowedStatuses = [
      ApVehicleStatus::INVENTARIO_VN,
      ApVehicleStatus::VEHICULO_EN_TRAVESIA,
      ApVehicleStatus::EN_CURSO,
    ];

    $query = Vehicles::with([
      'model',
      'color',
      'engineType',
      'vehicleStatus',
      'warehousePhysical',
      'purchaseOrders.items'
    ])->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->where(function ($q) use ($allowedStatuses, $isEditing) {
        $q->whereIn('ap_vehicle_status_id', $allowedStatuses);
        // Al editar, también incluir vehículos con anticipos pero no totalmente pagados
        // (ej. status FACTURADO por anticipos, is_paid=false)
        if ($isEditing) {
          $q->orWhere(function ($sub) {
            $sub->where('is_paid', false)
              ->whereHas('purchaseRequestQuote');
          });
        }
      });

    // Aplicar filtros si existen
    if ($request->has('search') && $request->search) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('vin', 'like', "%{$search}%")
          ->orWhere('engine_number', 'like', "%{$search}%")
          ->orWhere('year', 'like', "%{$search}%");
      });
    }

    if ($request->has('ap_models_vn_id') && $request->ap_models_vn_id) {
      $query->where('ap_models_vn_id', $request->ap_models_vn_id);
    }

    if ($request->has('vehicle_color_id') && $request->vehicle_color_id) {
      $query->where('vehicle_color_id', $request->vehicle_color_id);
    }

    if ($request->has('warehouse_physical_id') && $request->warehouse_physical_id) {
      $query->where('warehouse_physical_id', $request->warehouse_physical_id);
    }

    // family_id
    if ($request->has('family_id') && $request->family_id) {
      $query->whereHas('model.family', function ($q) use ($request) {
        $q->where('id', $request->family_id);
      });
    }

    if (!$isEditing) {
      $query->where(function ($q) use ($excludeQuoteId) {
        $q->whereDoesntHave('purchaseRequestQuote');
        if ($excludeQuoteId) {
          $q->orWhereHas('purchaseRequestQuote', function ($subQ) use ($excludeQuoteId) {
            $subQ->where('id', $excludeQuoteId);
          });
        }
      });
    } else {
      if ($excludeQuoteId) {
        // Solo documentos reales (no anticipos) indican que la venta ya está comprometida
        $quoteHasDocuments = ElectronicDocument::where('purchase_request_quote_id', $excludeQuoteId)
          ->where('is_advance_payment', false)
          ->where('anulado', false)
          ->exists();
        if ($quoteHasDocuments) {
          $query->whereHas('purchaseRequestQuote', function ($subQ) use ($excludeQuoteId) {
            $subQ->where('id', $excludeQuoteId);
          });
        } else {
          $query->where(function ($q) use ($excludeQuoteId) {
            $q->whereDoesntHave('purchaseRequestQuote')
              ->orWhereHas('purchaseRequestQuote', function ($subQ) use ($excludeQuoteId) {
                $subQ->where('id', $excludeQuoteId);
              });
          });
        }
      }
    }

    // Excluir vehículos con facturación neta positiva (factura - NC/ND > tolerancia),
    // ignorando documentos que pertenezcan a la cotización que se está editando.
    $invoicedIds = $this->getEffectivelyInvoicedVehicleIds($excludeQuoteId);
    if (!empty($invoicedIds)) {
      $query->whereNotIn('ap_vehicles.id', $invoicedIds);
    }

    // Verificar si se solicita todos los registros sin paginación
    $all = filter_var($request->get('all', false), FILTER_VALIDATE_BOOLEAN);

    // Obtener vehículos (paginados o todos)
    if ($all) {
      $vehicles = $query->get();
    } else {
      $perPage = $request->get('per_page', 15);
      $vehicles = $query->paginate($perPage);
    }

    // Función de transformación para incluir costos
    $transformVehicle = function ($vehicle) {
      // Obtener transport_cost del modelo (temporal)
      $freightCost = $vehicle->model?->transport_cost ?? 0;

      return [
        'id' => $vehicle->id,
        'vin' => $vehicle->vin,
        'year' => $vehicle->year,
        'engine_number' => $vehicle->engine_number,
        'ap_models_vn_id' => $vehicle->ap_models_vn_id,
        'vehicle_color_id' => $vehicle->vehicle_color_id,
        'engine_type_id' => $vehicle->engine_type_id,
        'ap_vehicle_status_id' => $vehicle->ap_vehicle_status_id,
        'model' => $vehicle->model?->version,
        'model_code' => $vehicle->model?->code,
        'family' => $vehicle->model?->family?->description,
        'vehicle_color' => $vehicle->color?->description,
        'engine_type' => $vehicle->engineType?->description,
        'status' => $vehicle->status,
        'vehicle_status' => $vehicle->vehicleStatus?->description,
        'status_color' => $vehicle->vehicleStatus?->color,
        'warehouse_physical_id' => $vehicle->warehouse_physical_id,
        'warehouse_physical' => $vehicle->warehousePhysical?->description,
        'billed_cost' => $vehicle->purchase_price,
        'freight_cost' => $freightCost,
        'warehouse' => $vehicle->warehouse?->description,
      ];
    };

    // Transformar los datos según el tipo de resultado
    if ($all) {
      // Si es 'all', devolver array simple sin paginación
      $transformedData = $vehicles->map($transformVehicle);
      return response()->json($transformedData);
    } else {
      // Si está paginado, transformar la colección y mantener metadatos de paginación
      $vehicles->getCollection()->transform($transformVehicle);
      return response()->json($vehicles);
    }
  }

  /**
   * Retorna IDs de vehículos con facturación neta positiva (factura - NC + ND > tolerancia).
   * Si se pasa $excludeQuoteId, los documentos de esa cotización no cuentan (modo edición).
   */
  private function getEffectivelyInvoicedVehicleIds(?string $excludeQuoteId): array
  {
    $invoicesQuery = DB::table('ap_billing_electronic_documents as ed')
      ->join('ap_vehicle_movement as vm', 'ed.ap_vehicle_movement_id', '=', 'vm.id')
      ->whereNull('ed.deleted_at')
      ->where('ed.is_advance_payment', false)
      ->where('ed.anulado', false)
      ->where('ed.aceptada_por_sunat', true)
      ->whereIn('ed.sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA,
      ])
      ->selectRaw('ed.id, ed.total, vm.ap_vehicle_id');

    if ($excludeQuoteId) {
      $invoicesQuery->where(function ($q) use ($excludeQuoteId) {
        $q->whereNull('ed.purchase_request_quote_id')
          ->orWhere('ed.purchase_request_quote_id', '!=', $excludeQuoteId);
      });
    }

    $invoices = $invoicesQuery->get();

    if ($invoices->isEmpty()) {
      return [];
    }

    $invoiceIds = $invoices->pluck('id');

    $creditNotes = DB::table('ap_billing_electronic_documents')
      ->whereNull('deleted_at')
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->whereIn('original_document_id', $invoiceIds)
      ->selectRaw('original_document_id, SUM(total) as total_nc')
      ->groupBy('original_document_id')
      ->get()
      ->keyBy('original_document_id');

    $debitNotes = DB::table('ap_billing_electronic_documents')
      ->whereNull('deleted_at')
      ->where('is_advance_payment', false)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->whereIn('original_document_id', $invoiceIds)
      ->selectRaw('original_document_id, SUM(total) as total_nd')
      ->groupBy('original_document_id')
      ->get()
      ->keyBy('original_document_id');

    $netByVehicle = [];
    foreach ($invoices as $invoice) {
      $nc = $creditNotes->get($invoice->id)->total_nc ?? 0;
      $nd = $debitNotes->get($invoice->id)->total_nd ?? 0;
      $netByVehicle[$invoice->ap_vehicle_id] = ($netByVehicle[$invoice->ap_vehicle_id] ?? 0)
        + $invoice->total - $nc + $nd;
    }

    return array_keys(array_filter(
      $netByVehicle,
      fn($net) => round($net, 2) > ElectronicDocument::ROUNDING_TOLERANCE
    ));
  }

  /**
   * Obtiene las facturas (documentos electrónicos) asociadas a un vehículo
   * @param int $vehicleId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getInvoices(int $vehicleId)
  {
    $vehicle = $this->find($vehicleId);

    // Obtener los documentos electrónicos con sus relaciones
    $documents = $vehicle->electronicDocuments()
      ->with([
        'documentType',
        'transactionType',
        'identityDocumentType',
        'currency',
        'vehicleMovement',
        'items',
        'creator'
      ])
      ->where('anulado', false)
      ->orderBy('fecha_de_emision', 'desc')
      ->get();

    return response()->json([
      'vehicle' => VehiclesResource::make($vehicle),
      'documents' => ElectronicDocumentResource::collection($documents),
      'total_documents' => $documents->count(),
      'total_amount' => $documents->sum('total'),
    ]);
  }

  /**
   * Obtiene información del cliente asociado a un vehículo y su estado de deuda
   * @param int $vehicleId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getVehicleClientDebtInfo(int $vehicleId)
  {
    // Usar el método centralizado para obtener vehículo, documento y cliente
    $data = Vehicles::getElectronicDocumentWithClient($vehicleId);

    $vehicle = $data->vehicle;

    // Cargar datos de recepción
    $vehicle->load([
      'shippingGuideReceiving.receivingChecklists.receiving',
      'shippingGuideReceiving.receivingInspection.damages',
      'shippingGuideReceiving.receivingInspection.inspectedBy',
      'shippingGuideReceiving.receivedBy',
    ]);
    $electronicDocument = $data->electronicDocument;
    $client = $data->client;
    $purchaseRequestQuote = $electronicDocument->purchaseRequestQuote;

    // Obtener el monto total de la venta (sale_price de la cotización)
    $totalSalePrice = $purchaseRequestQuote->sale_price;

    // Obtener todos los documentos electrónicos asociados a esta cotización
    $documents = ElectronicDocument::where('purchase_request_quote_id', $purchaseRequestQuote->id)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->with(['documentType', 'currency', 'installments'])
      ->get();

    // Calcular total pagado
    $totalPaid = 0;
    $facturas = [];
    $notasCredito = [];
    $notasDebito = [];

    foreach ($documents as $doc) {
      $docInfo = [
        'id' => $doc->id,
        'serie' => $doc->serie,
        'numero' => $doc->numero,
        'document_number' => $doc->document_number,
        'fecha_emision' => $doc->fecha_de_emision?->format('Y-m-d'),
        'moneda' => $doc->currency?->description,
        'total' => $doc->total,
        'tipo_documento' => $doc->documentType?->description,
      ];

      // Facturas y boletas suman al total pagado
      if (in_array($doc->sunat_concept_document_type_id, [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA
      ])) {
        $totalPaid += $doc->total;
        $facturas[] = $docInfo;
      } // Notas de crédito restan del total pagado
      elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
        $totalPaid -= $doc->total;
        $notasCredito[] = $docInfo;
      } // Notas de débito suman al total pagado
      elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_DEBITO) {
        $totalPaid += $doc->total;
        $notasDebito[] = $docInfo;
      }
    }

    // Calcular deuda pendiente
    $pendingDebt = $totalSalePrice - $totalPaid;

    $isPaid = $vehicle->is_paid;

    // Determinar estado de la deuda
    $debtStatus = 'Sin deuda';
    $debtMessage = 'El cliente no tiene deuda pendiente';

    if ($pendingDebt > 0.01) {
      $debtStatus = 'Deuda pendiente';
      $debtMessage = 'El cliente tiene deuda pendiente';
    } elseif ($pendingDebt < -0.01) {
      $debtStatus = 'Sobrepago';
      $debtMessage = 'El cliente tiene un sobrepago';
    }

    return response()->json([
      'vehicle' => VehiclesResource::make($vehicle),
      'client' => [
        'id' => $client->id,
        'num_doc' => $client->num_doc,
        'full_name' => $client->full_name,
        'direction' => $client->direction,
        'email' => $client->email,
      ],
      'purchase_quote' => [
        'id' => $purchaseRequestQuote->id,
        'correlative' => $purchaseRequestQuote->correlative,
        'sale_price' => round($totalSalePrice, 2),
      ],
      'debt_summary' => [
        'total_sale_price' => round($totalSalePrice, 2),
        'total_paid' => round($totalPaid, 2),
        'pending_debt' => round($pendingDebt, 2),
        'status' => $debtStatus,
        'message' => $debtMessage,
        'has_pending_debt' => $pendingDebt > 0.01,
        'debt_is_paid' => $isPaid,
      ],
      'documents_summary' => [
        'total_documents' => $documents->count(),
        'total_facturas' => count($facturas),
        'total_notas_credito' => count($notasCredito),
        'total_notas_debito' => count($notasDebito),
      ],
      'facturas' => $facturas,
      'notas_credito' => $notasCredito,
      'notas_debito' => $notasDebito,
      'reception' => $this->buildReceptionData($vehicle),
    ]);
  }

  private function buildReceptionData(Vehicles $vehicle): ?array
  {
    $guide = $vehicle->shippingGuideReceiving;

    if (!$guide) {
      return null;
    }

    $inspection = $guide->receivingInspection;

    $accessoryStatuses = ApReceivingAccessoryStatus::where('shipping_guide_id', $guide->id)->get();

    return [
      'shipping_guide_id' => $guide->id,
      'document_number' => $guide->document_number,
      'issue_date' => $guide->issue_date?->format('Y-m-d'),
      'received_date' => $guide->received_date?->format('Y-m-d H:i:s'),
      'note_received' => $guide->note_received,
      'received_by' => $guide->receivedBy?->name,
      'checklist_items' => $guide->receivingChecklists->map(fn($c) => [
        'id' => $c->id,
        'description' => $c->receiving?->description,
        'quantity' => $c->quantity,
        'kilometers' => $c->kilometers,
      ])->values(),
      'inspection' => $inspection ? [
        'id' => $inspection->id,
        'photo_front_url' => $inspection->photo_front_url,
        'photo_back_url' => $inspection->photo_back_url,
        'photo_left_url' => $inspection->photo_left_url,
        'photo_right_url' => $inspection->photo_right_url,
        'general_observations' => $inspection->general_observations,
        'inspected_by' => $inspection->inspectedBy?->name,
        'created_at' => $inspection->created_at?->format('Y-m-d H:i:s'),
        'damages' => $inspection->damages->map(fn($d) => [
          'id' => $d->id,
          'damage_type' => $d->damage_type,
          'x_coordinate' => $d->x_coordinate,
          'y_coordinate' => $d->y_coordinate,
          'description' => $d->description,
          'photo_url' => $d->photo_url,
        ])->values(),
      ] : null,
      'accessories' => $accessoryStatuses->map(fn($a) => [
        'id' => $a->id,
        'description' => $a->description,
        'quantity' => $a->quantity,
        'received' => $a->received,
        'is_installed' => $a->is_installed,
      ])->values(),
    ];
  }

  /**
   * Obtiene la orden de compra asociada a un vehículo
   * @param int $vehicleId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getPurchaseOrder(int $vehicleId)
  {
    $vehicle = $this->find($vehicleId);

    // Obtener la orden de compra con sus relaciones
    $purchaseOrder = $vehicle->purchaseOrder()
      ->with([
        'supplier',
        'currency',
        'warehouse',
        'warehouse.articleClass',
        'supplierOrderType',
        'sede',
        'items',
        'items.product',
        'items.unitMeasurement',
        'vehicleMovement',
      ])
      ->first();

    if (!$purchaseOrder) {
      throw new Exception('Este vehículo no tiene una orden de compra asociada');
    }

    return response()->json([
      'vehicle' => VehiclesResource::make($vehicle),
      'purchase_order' => new PurchaseOrderResource($purchaseOrder),
    ]);
  }

  public function updateStatus(int $vehicleId, array $data): array
  {
    $statusId = (int)$data['status_id'];
    $observation = $data['observation'] ?? null;
    $movementDate = $data['movement_date'] ?? now();
    $movementType = $data['movement_type'] ?? null;

    $movementTypeMap = [
      ApVehicleStatus::PEDIDO_VN => VehicleMovement::ORDERED,
      ApVehicleStatus::VEHICULO_EN_TRAVESIA => VehicleMovement::IN_TRANSIT,
      ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO => VehicleMovement::IN_TRANSIT_RETURNED,
      ApVehicleStatus::VENDIDO_NO_ENTREGADO => VehicleMovement::SOLD_NOT_DELIVERED,
      ApVehicleStatus::INVENTARIO_VN => VehicleMovement::INVENTORY,
      ApVehicleStatus::VENDIDO_ENTREGADO => VehicleMovement::SOLD_DELIVERED,
      ApVehicleStatus::FACTURADO => VehicleMovement::INVOICED,
      ApVehicleStatus::CONSIGNACION => VehicleMovement::CONSIGNMENT,
      ApVehicleStatus::FACTURADO_FINAL => VehicleMovement::INVOICED,
    ];

    if (!$movementType) {
      $movementType = $movementTypeMap[$statusId] ?? null;
    }

    if (!$movementType) {
      throw new Exception("No se pudo determinar el tipo de movimiento para el estado ID {$statusId}.");
    }

    $vehicle = $this->find($vehicleId);
    $previousStatusId = $vehicle->ap_vehicle_status_id;

    DB::transaction(function () use ($vehicle, $statusId, $previousStatusId, $movementType, $movementDate, $observation) {
      $vehicle->update(['ap_vehicle_status_id' => $statusId]);

      VehicleMovement::create([
        'movement_type' => $movementType,
        'ap_vehicle_id' => $vehicle->id,
        'ap_vehicle_status_id' => $statusId,
        'previous_status_id' => $previousStatusId,
        'new_status_id' => $statusId,
        'movement_date' => $movementDate,
        'confirmed_at' => now(),
        'observation' => $observation,
        'created_by' => auth()->id(),
      ]);
    });

    return [
      'vehicle_id' => $vehicle->id,
      'previous_status_id' => $previousStatusId,
      'new_status_id' => $statusId,
      'movement_type' => $movementType,
    ];
  }
}
