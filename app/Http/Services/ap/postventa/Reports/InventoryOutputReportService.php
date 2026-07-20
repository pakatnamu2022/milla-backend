<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\maestroGeneral\Warehouse;
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
        'workOrder.typeCurrency',
        'workOrder.exchangeRate',
        'product.warehouseStocks',
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
        'orderQuotation.typeCurrency',
        'orderQuotation.exchangeRate',
        'product.warehouseStocks',
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
    $exchangeRate = $workOrder->getExchangeRateToUsd();

    // PVP: unit_price ya está sin IGV (tax_amount se calcula aparte sobre net_amount),
    // se convierte a dólares con el tipo de cambio de la OT/última factura
    $pvpUsd = ((float)($part->unit_price ?? 0)) / $exchangeRate;

    // Margen = diferencia entre PVP y costo, ambos en dólares
    $margen = $this->calculateProfitMargin($pvpUsd, $part->product, $part->warehouse_id, $exchangeRate);

    return [
      'fecha_factura_final' => $finalInvoice->fecha_de_emision ? $finalInvoice->fecha_de_emision->format('d/m/Y') : '',
      'codigo_afs' => $workOrder->sede?->code_afs ?? '',
      'concesionario' => $workOrder->sede?->abreviatura ?? '',
      'codigo_producto' => $part->product?->code ?? '',
      'numero' => number_format($part->quantity_used ?? 0, 2, '.', ''),
      'pvp' => number_format($pvpUsd, 2, '.', ''),
      'margen' => $margen,
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
    $exchangeRate = $quotation->getExchangeRateToUsd();

    // PVP: unit_price ya está sin IGV (tax_amount se calcula aparte sobre net_amount),
    // se convierte a dólares con el tipo de cambio de la cotización/última factura
    $pvpUsd = ((float)($detail->unit_price ?? 0)) / $exchangeRate;

    // Margen = diferencia entre PVP y costo, ambos en dólares.
    // El almacén físico de posventa de la sede es el que tiene el cost_price real
    // (output_generation_warehouse es un flag booleano, no un ID de almacén)
    $warehouseId = Warehouse::getPhysicalWarehouseForPostsale($quotation->sede_id)?->id;
    $margen = $this->calculateProfitMargin($pvpUsd, $detail->product, $warehouseId, $exchangeRate);

    return [
      'fecha_factura_final' => $finalInvoice->fecha_de_emision ? $finalInvoice->fecha_de_emision->format('d/m/Y') : '',
      'codigo_afs' => $quotation->sede?->code_afs ?? '',
      'concesionario' => $quotation->sede?->abreviatura ?? '',
      'codigo_producto' => $detail->product?->code ?? '',
      'numero' => number_format($detail->quantity ?? 0, 2, '.', ''),
      'pvp' => number_format($pvpUsd, 2, '.', ''),
      'margen' => $margen,
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

  /**
   * Calcula el margen de ganancia: la diferencia (no el porcentaje) entre el PVP
   * en dólares y el cost_price del almacén, también convertido a dólares con el
   * mismo tipo de cambio usado para el PVP.
   *
   * Margen = PVP(USD) - Costo(USD)
   *
   * @param float $pvpUsd Precio de venta unitario (PVP) ya en dólares
   * @param object|null $product Producto con la relación warehouseStocks cargada
   * @param int|null $warehouseId ID del almacén
   * @param float $exchangeRate Tipo de cambio usado para convertir el costo a dólares
   * @return string Margen formateado con 2 decimales o vacío si no se puede calcular
   */
  private function calculateProfitMargin(float $pvpUsd, ?object $product, ?int $warehouseId, float $exchangeRate): string
  {
    // Si no hay producto o warehouse_id, retornar vacío
    if (!$product || !$warehouseId || $pvpUsd <= 0) {
      return '';
    }

    // Buscar el stock del producto en el almacén especificado
    $warehouseStock = $product->warehouseStocks?->firstWhere('warehouse_id', $warehouseId);

    // Si no existe stock para ese almacén o no tiene cost_price, retornar vacío
    if (!$warehouseStock || !$warehouseStock->cost_price || $warehouseStock->cost_price <= 0) {
      return '';
    }

    $costPriceUsd = ((float)$warehouseStock->cost_price) / $exchangeRate;

    // Margen: diferencia simple entre PVP y costo, ambos en dólares
    $margin = $pvpUsd - $costPriceUsd;

    return number_format($margin, 2, '.', '');
  }
}
