<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Collection;

class InventoryOutputReportService
{
  /**
   * Obtiene el reporte consolidado de salidas de productos
   * Incluye salidas de Taller (ApWorkOrderParts) y Repuestos (ApOrderQuotationDetails)
   *
   * @param array $filters
   * @return Collection
   */
  public function getInventoryOutputReport(array $filters = []): Collection
  {
    $tallerOutputs = $this->getTallerOutputs($filters);
    $repuestosOutputs = $this->getRepuestosOutputs($filters);

    // Combinar ambas colecciones
    $allOutputs = $tallerOutputs->merge($repuestosOutputs);

    // Ordenar por fecha de factura
    return $allOutputs->sortBy('fecha_factura_final')->values();
  }

  /**
   * Obtiene las salidas de productos de Taller (ApWorkOrderParts)
   *
   * @param array $filters
   * @return Collection
   */
  private function getTallerOutputs(array $filters): Collection
  {
    $query = ApWorkOrderParts::query()
      ->with([
        'workOrder.sede',
        'workOrder.invoiceTo.documentType',
        'product',
      ])
      ->whereHas('workOrder', function ($q) {
        // Solo OTs que tienen factura final
        $q->whereHas('advancesWorkOrder', function ($docQuery) {
          $docQuery->where('is_advance_payment', false)
            ->where('aceptada_por_sunat', true)
            ->whereIn('sunat_concept_document_type_id', [SunatConcepts::ID_FACTURA_ELECTRONICA, SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA]); // FACTURA o BOLETA
        });
      });

    // Aplicar filtros
    $this->applyFilters($query, $filters, 'taller');

    $parts = $query->get();

    return $parts->map(function ($part) {
      return $this->transformTallerOutput($part);
    })->filter(); // Filtrar nulos
  }

  /**
   * Obtiene las salidas de productos de Repuestos (ApOrderQuotationDetails)
   *
   * @param array $filters
   * @return Collection
   */
  private function getRepuestosOutputs(array $filters): Collection
  {
    $query = ApOrderQuotationDetails::query()
      ->with([
        'orderQuotation.sede',
        'orderQuotation.invoiceTo.documentType',
        'orderQuotation.client.documentType',
        'product',
      ])
      ->where('item_type', ApOrderQuotationDetails::ITEM_TYPE_PRODUCT)
      ->whereNotNull('product_id')
      ->whereHas('orderQuotation', function ($q) {
        // Solo cotizaciones que tienen factura final
        $q->whereHas('advancesOrderQuotation', function ($docQuery) {
          $docQuery->where('is_advance_payment', false)
            ->where('aceptada_por_sunat', true)
            ->whereIn('sunat_concept_document_type_id', [SunatConcepts::ID_FACTURA_ELECTRONICA, SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA]); // FACTURA o BOLETA
        });
      });

    // Aplicar filtros
    $this->applyFilters($query, $filters, 'repuestos');

    $details = $query->get();

    return $details->map(function ($detail) {
      return $this->transformRepuestosOutput($detail);
    })->filter(); // Filtrar nulos
  }

  /**
   * Transforma una salida de taller al formato del reporte
   *
   * @param ApWorkOrderParts $part
   * @return array|null
   */
  private function transformTallerOutput(ApWorkOrderParts $part): ?array
  {
    $workOrder = $part->workOrder;
    if (!$workOrder) {
      return null;
    }

    $finalInvoice = $workOrder->getFinalInvoice();
    if (!$finalInvoice) {
      return null;
    }

    $invoiceTo = $workOrder->invoiceTo;

    return [
      'fecha_factura_final' => $finalInvoice->fecha_de_emision ? $finalInvoice->fecha_de_emision->format('Y-m-d') : '',
      'codigo_afs' => '', // En blanco
      'concesionario' => $workOrder->sede?->abreviatura ?? '',
      'codigo_producto' => $part->product?->code ?? '',
      'numero' => number_format($part->quantity_used ?? 0, 2, '.', ''),
      'pvp' => number_format($part->unit_price ?? 0, 2, '.', ''),
      'margen' => '', // En blanco de momento
      'area' => 'TALLER',
      'documento' => $invoiceTo?->num_doc ?? '',
      'nombre_cliente' => $invoiceTo?->full_name ?? '',
    ];
  }

  /**
   * Transforma una salida de repuestos al formato del reporte
   *
   * @param ApOrderQuotationDetails $detail
   * @return array|null
   */
  private function transformRepuestosOutput(ApOrderQuotationDetails $detail): ?array
  {
    $quotation = $detail->orderQuotation;
    if (!$quotation) {
      return null;
    }

    $finalInvoice = $quotation->getFinalInvoice();
    if (!$finalInvoice) {
      return null;
    }

    // Priorizar invoiceTo, si no existe usar client
    $customer = $quotation->invoiceTo ?? $quotation->client;

    return [
      'fecha_factura_final' => $finalInvoice->fecha_de_emision ? $finalInvoice->fecha_de_emision->format('Y-m-d') : '',
      'codigo_afs' => '', // En blanco
      'concesionario' => $quotation->sede?->abreviatura ?? '',
      'codigo_producto' => $detail->product?->code ?? '',
      'numero' => number_format($detail->quantity ?? 0, 2, '.', ''),
      'pvp' => number_format($detail->unit_price ?? 0, 2, '.', ''),
      'margen' => '', // En blanco de momento
      'area' => 'REPUESTOS',
      'documento' => $customer?->num_doc ?? '',
      'nombre_cliente' => $customer?->full_name ?? '',
    ];
  }

  /**
   * Aplica filtros a la query según el área
   *
   * @param $query
   * @param array $filters
   * @param string $area 'taller' o 'repuestos'
   * @return void
   */
  private function applyFilters($query, array $filters, string $area): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      // Ajustar el nombre de la columna según el área
      $relationPrefix = $area === 'taller' ? 'workOrder' : 'orderQuotation';
      $advancesRelation = $area === 'taller' ? 'advancesWorkOrder' : 'advancesOrderQuotation';

      switch ($column) {
        case 'sede_id':
          $query->whereHas($relationPrefix, function ($q) use ($value) {
            $q->where('sede_id', $value);
          });
          break;

        case 'invoice_date':
          if ($operator === 'date_between' && is_array($value) && count($value) === 2) {
            $query->whereHas($relationPrefix . '.' . $advancesRelation, function ($q) use ($value) {
              $q->where('is_advance_payment', false)
                ->whereBetween('fecha_de_emision', [$value[0], $value[1]]);
            });
          }
          break;

        case 'product_id':
          $query->where('product_id', $value);
          break;

        case 'area':
          // Este filtro se manejará después de combinar ambas colecciones
          break;
      }
    }
  }
}
