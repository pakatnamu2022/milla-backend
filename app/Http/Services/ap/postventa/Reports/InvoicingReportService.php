<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

class InvoicingReportService
{
  /**
   * Obtiene el reporte de facturación de Órdenes de Trabajo
   *
   * @param array $filters
   * @return array
   */
  public function getInvoicingReport(array $filters = []): array
  {
    // Para la hoja principal: Consultar TODOS los documentos electrónicos sin filtrar por estado de OT
    $queryDocuments = ElectronicDocument::query()
      ->with([
        'workOrder.sede',
        'workOrder.advisor',
        'workOrder.status',
        'workOrder.vehicle.model.family.brand',
        'workOrder.items.typePlanning',
        'workOrder.plannings.worker',
        'currency',
        'exchangeRate',
      ])
      ->whereNotNull('work_order_id')
      ->where('aceptada_por_sunat', true);

    // Aplicar filtros de documentos
    $this->applyDocumentFilters($queryDocuments, $filters);

    $documents = $queryDocuments->get();

    // Separar documentos finales de anticipos
    $finalDocuments = $documents->filter(function ($document) {
      return !$document->is_advance_payment;
    });

    $advanceDocuments = $documents->filter(function ($document) {
      return $document->is_advance_payment;
    });

    // Transformar documentos finales para el reporte (Primera página)
    $reportDataFinal = $finalDocuments->map(function ($document) {
      return $this->transformDocumentForReport($document, $document->workOrder);
    })->values();

    // Transformar documentos de anticipos para el reporte (Segunda página)
    $reportDataAdvances = $advanceDocuments->map(function ($document) {
      return $this->transformDocumentForReport($document, $document->workOrder);
    })->values();

    // Para el resumen: Consultar WorkOrders que NO estén cerradas ni canceladas
    // (son las que aún no tienen factura final o no terminaron de facturar)
    $queryWorkOrders = ApWorkOrder::query()
      ->with([
        'sede',
        'advancesWorkOrder' => function ($q) {
          $q->with(['creditNote']);
        },
      ])
      ->whereNotIn('status_id', [
        ApMasters::CLOSED_WORK_ORDER_ID,
        ApMasters::CANCELED_WORK_ORDER_ID,
      ]);

    // Aplicar filtros de workorders
    $this->applyWorkOrderFilters($queryWorkOrders, $filters);

    $workOrders = $queryWorkOrders->get();

    // Generar tabla resumen de OTs pendientes de pago (Tercera página)
    $summary = $this->generatePaymentSummary($workOrders);

    return [
      'final_documents' => $reportDataFinal,
      'advance_documents' => $reportDataAdvances,
      'summary' => $summary,
    ];
  }

  /**
   * Transforma un documento electrónico en el formato del reporte
   *
   * @param ElectronicDocument $document
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function transformDocumentForReport(ElectronicDocument $document, ApWorkOrder $workOrder): array
  {
    // Obtener técnicos únicos consolidados
    $technicians = $this->getConsolidatedTechnicians($workOrder);

    // Obtener el primer item de la OT para el trabajo realizado
    $firstItem = $workOrder->items->first();

    // Verificar si ya tiene factura final emitida
    $finalInvoice = $workOrder->getFinalInvoice();
    $estado = $finalInvoice ? 'CERRADO' : ($workOrder->status?->description ?? '');

    // Determinar moneda original y tasa de cambio
    $currencyId = $document->sunat_concept_currency_id;
    $isUSD = $currencyId === SunatConcepts::CURRENCY_USD;
    $exchangeRate = $isUSD ? ($document->exchangeRate?->rate ?? 1) : 1;

    // Moneda original del comprobante
    $monedaOriginal = $isUSD ? 'USD' : 'PEN';

    // Convertir montos a soles
    $totalManoObra = ($workOrder->total_labor_cost ?? 0) * $exchangeRate;
    $totalRepuestos = ($workOrder->total_parts_cost ?? 0) * $exchangeRate;
    $descuentoMonto = ($workOrder->discount_amount ?? 0) * $exchangeRate;
    $montoSinIgv = ($document->total_gravada ?? 0) * $exchangeRate;
    $igv = ($document->total_igv ?? 0) * $exchangeRate;
    $total = ($document->total ?? 0) * $exchangeRate;

    return [
      'taller' => $workOrder->sede?->abreviatura ?? '',
      'numero_ot' => $workOrder->correlative ?? '',
      'fecha_apertura_ot' => $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : '',
      'estado' => $estado,
      'asesor_servicio' => $workOrder->advisor?->nombre_completo ?? '',
      'tipo_servicio' => $firstItem?->typePlanning?->description ?? '',
      'marca' => $workOrder->vehicle?->model?->family?->brand?->name ?? '',
      'modelo_vehiculo' => $workOrder->vehicle?->model?->family?->description ?? '',
      'trabajo_realizado' => $firstItem?->description ?? '',
      'operario' => $technicians,
      'serie_comprobante' => $document->serie ?? '',
      'numero_comprobante' => $document->numero ?? '',
      'fecha_comprobante' => $document->fecha_de_emision ? $document->fecha_de_emision->format('d/m/Y') : '',
      'num_doc_cliente' => $document->cliente_numero_de_documento ?? '',
      'cliente' => $document->cliente_denominacion ?? '',
      'total_mano_obra' => number_format($totalManoObra, 2, '.', ''),
      'total_repuestos' => number_format($totalRepuestos, 2, '.', ''),
      'descuento_porcentaje' => number_format($workOrder->discount_percentage ?? 0, 2, '.', ''),
      'descuento_monto' => number_format($descuentoMonto, 2, '.', ''),
      'monto_sin_igv' => number_format($montoSinIgv, 2, '.', ''),
      'igv' => number_format($igv, 2, '.', ''),
      'total' => number_format($total, 2, '.', ''),
      'moneda' => 'PEN',
      'moneda_original' => $monedaOriginal,
      'work_order_id' => $workOrder->id,
      'document_id' => $document->id,
    ];
  }

  /**
   * Obtiene los técnicos únicos consolidados de una OT
   *
   * @param ApWorkOrder $workOrder
   * @return string
   */
  private function getConsolidatedTechnicians(ApWorkOrder $workOrder): string
  {
    $technicians = $workOrder->plannings
      ->whereNotNull('worker_id')
      ->pluck('worker.nombre_completo')
      ->filter()
      ->unique()
      ->values();

    return $technicians->implode(', ');
  }

  /**
   * Genera la tabla resumen de OTs pendientes de pago
   *
   * @param Collection $workOrders
   * @return array
   */
  private function generatePaymentSummary(Collection $workOrders): array
  {
    $summary = [];
    $totalOtGeneral = 0;
    $totalAnticiposGeneral = 0;
    $totalDeudaGeneral = 0;

    foreach ($workOrders as $workOrder) {
      // Obtener anticipos activos usando el método del modelo
      $activeAdvances = $workOrder->getActiveAdvances();

      // Obtener factura final usando el método del modelo
      $finalInvoice = $workOrder->getFinalInvoice();

      // Solo incluir OTs que:
      // 1. Tienen anticipos activos
      // 2. NO tienen factura final (aunque el estado sea diferente de CLOSED)
      if ($activeAdvances->isEmpty() || $finalInvoice !== null) {
        continue;
      }

      // Calcular total de la OT desde el campo final_amount (ya que no hay factura final)
      $totalOt = (float)($workOrder->final_amount ?? 0);

      // Calcular total neto de anticipos (considerando NC y ND)
      $totalAnticiposNeto = 0;
      $advanceDetails = [];

      foreach ($activeAdvances as $advance) {
        // Usar el método privado getNetAmountForAdvance a través de reflexión
        $netAmount = $this->getNetAmountForAdvance($workOrder, $advance);
        $totalAnticiposNeto += $netAmount;

        // Guardar detalles del anticipo
        $advanceDetails[] = [
          'full_number' => $advance->full_number,
          'amount' => $netAmount,
        ];
      }

      // Calcular la deuda
      $deuda = $totalOt - $totalAnticiposNeto;

      // Solo incluir OTs con deuda pendiente
      if ($deuda > 0 && $totalOt > 0) {
        // Construir la lista de series y números con sus montos
        $seriesNumeros = collect($advanceDetails)
          ->map(function ($detail) {
            $amount = number_format($detail['amount'], 2, '.', '');
            return $detail['full_number'] . ' (' . $amount . ')';
          })
          ->implode("\n");

        $summary[] = [
          'taller' => $workOrder->sede?->abreviatura ?? '',
          'numero_ot' => $workOrder->correlative ?? '',
          'cliente' => $activeAdvances->first()?->cliente_denominacion ?? '',
          'num_anticipos' => $activeAdvances->count(),
          'series_numeros' => $seriesNumeros,
          'total_anticipos' => number_format($totalAnticiposNeto, 2, '.', ''),
          'total_ot' => number_format($totalOt, 2, '.', ''),
          'deuda' => number_format($deuda, 2, '.', ''),
        ];

        $totalOtGeneral += $totalOt;
        $totalAnticiposGeneral += $totalAnticiposNeto;
        $totalDeudaGeneral += $deuda;
      }
    }

    // Agregar fila de totales
    if (!empty($summary)) {
      $summary[] = [
        'taller' => '',
        'numero_ot' => '',
        'cliente' => 'TOTALES',
        'num_anticipos' => '',
        'series_numeros' => '',
        'total_anticipos' => number_format($totalAnticiposGeneral, 2, '.', ''),
        'total_ot' => number_format($totalOtGeneral, 2, '.', ''),
        'deuda' => number_format($totalDeudaGeneral, 2, '.', ''),
      ];
    }

    return $summary;
  }

  /**
   * Obtiene el monto neto de un anticipo considerando NC y ND
   * Usa reflexión para acceder al método privado del modelo
   *
   * @param ApWorkOrder $workOrder
   * @param ElectronicDocument $advance
   * @return float
   */
  private function getNetAmountForAdvance(ApWorkOrder $workOrder, ElectronicDocument $advance): float
  {
    try {
      $reflection = new ReflectionClass($workOrder);
      $method = $reflection->getMethod('getNetAmountForAdvance');
      $method->setAccessible(true);
      return $method->invoke($workOrder, $advance);
    } catch (\Exception $e) {
      // Si falla la reflexión, calcular manualmente
      return $this->calculateNetAmountManually($advance);
    }
  }

  /**
   * Calcula manualmente el monto neto de un anticipo
   * (fallback si la reflexión falla)
   *
   * @param ElectronicDocument $advance
   * @return float
   */
  private function calculateNetAmountManually(ElectronicDocument $advance): float
  {
    $netAmount = $advance->total;

    // Restar notas de crédito parciales
    $creditNotes = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($creditNotes as $creditNote) {
      $netAmount -= $creditNote->total;
    }

    // Sumar notas de débito
    $debitNotes = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($debitNotes as $debitNote) {
      $netAmount += $debitNote->total;
    }

    return (float)$netAmount;
  }

  /**
   * Aplica filtros a la query de ElectronicDocument
   *
   * @param $query
   * @param array $filters
   * @return void
   */
  private function applyDocumentFilters($query, array $filters): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      switch ($operator) {
        case 'documentDateFilter':
          // Filtro de fecha de emisión en documentos
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween('fecha_de_emision', [$value[0], $value[1]]);
          }
          break;
        case '=':
          // Filtros en la tabla workOrder
          if (in_array($column, ['sede_id'])) {
            $query->whereHas('workOrder', function ($q) use ($column, $value) {
              $q->where($column, $value);
            });
          }
          break;
        case 'like':
          // Filtros like en la tabla workOrder
          if (in_array($column, ['correlative'])) {
            $query->whereHas('workOrder', function ($q) use ($column, $value) {
              $q->where($column, 'like', '%' . $value . '%');
            });
          }
          break;
      }
    }
  }

  /**
   * Aplica filtros a la query de ApWorkOrder
   *
   * @param $query
   * @param array $filters
   * @return void
   */
  private function applyWorkOrderFilters($query, array $filters): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      switch ($operator) {
        case 'documentDateFilter':
          // Filtro de fecha de emisión en documentos relacionados
          if (is_array($value) && count($value) === 2) {
            $query->whereHas('advancesWorkOrder', function ($q) use ($value) {
              $q->whereBetween('fecha_de_emision', [$value[0], $value[1]]);
            });
          }
          break;
        case '=':
          $query->where($column, $value);
          break;
        case 'like':
          $query->where($column, 'like', '%' . $value . '%');
          break;
      }
    }
  }
}
