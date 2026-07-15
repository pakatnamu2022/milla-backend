<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Collection;

class MesonInvoicingReportService
{
  /**
   * Obtiene el reporte de facturación de Cotizaciones de Mesón
   *
   * @param array $filters
   * @return array
   */
  public function getMesonInvoicingReport(array $filters = []): array
  {
    // Consultar documentos electrónicos de cotizaciones (mesón)
    $queryDocuments = ElectronicDocument::query()
      ->with([
        'orderQuotation.sede',
        'orderQuotation.invoiceTo',
        'orderQuotation.details.product',
        'exchangeRate',
      ])
      ->whereNotNull('order_quotation_id')
      ->where('aceptada_por_sunat', true)
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA,
      ]);

    // Aplicar filtros
    $this->applyDocumentFilters($queryDocuments, $filters);

    $documents = $queryDocuments->get();

    // Transformar documentos para el reporte con detalles por artículo
    $reportData = collect();

    foreach ($documents as $document) {
      $quotation = $document->orderQuotation;

      if (!$quotation) {
        continue;
      }

      // Obtener el almacén físico de postventa para la sede
      $warehouse = Warehouse::getPhysicalWarehouseForPostsale($quotation->sede_id);

      foreach ($quotation->details as $detail) {
        // Solo incluir productos, no mano de obra
        if ($detail->item_type !== 'PRODUCT' || !$detail->product_id) {
          continue;
        }

        $reportData->push($this->transformDetailForReport($document, $quotation, $detail, $warehouse));
      }
    }

    return [
      'report_data' => $reportData->values(),
    ];
  }

  /**
   * Transforma un detalle de cotización en el formato del reporte
   *
   * @param ElectronicDocument $document
   * @param $quotation
   * @param $detail
   * @param $warehouse
   * @return array
   */
  private function transformDetailForReport($document, $quotation, $detail, $warehouse): array
  {
    // Determinar tipo de comprobante
    $tipoComprobante = $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_FACTURA
      ? 'FACTURA'
      : 'BOLETA';

    // Determinar moneda original y tasa de cambio
    $currencyId = $document->sunat_concept_currency_id;
    $isUSD = $currencyId === SunatConcepts::CURRENCY_USD;
    $exchangeRate = $isUSD ? ($document->exchangeRate?->rate ?? 1) : 1;

    // Moneda original del comprobante
    $monedaOriginal = $isUSD ? 'USD' : 'PEN';

    // Obtener el costo del producto desde ProductWarehouseStock
    $costPrice = 0;
    if ($warehouse && $detail->product_id) {
      $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      $costPrice = $stock ? (float)$stock->cost_price : 0;
    }

    // PVP (Precio de Venta al Público) = Precio Unitario - Convertir a soles
    $pvp = (float)$detail->unit_price * $exchangeRate;

    // Calcular costo total
    $costoTotal = $costPrice * (float)$detail->quantity;

    // Neto del item - Convertir a soles
    $neto = (float)$detail->net_amount * $exchangeRate;

    // Beneficio = PVP - Costo
    $beneficio = $pvp - $costPrice;

    // % Beneficio = (Beneficio / PVP) * 100 (evitar división por cero)
    $porcentajeBeneficio = $pvp > 0 ? ($beneficio / $pvp) * 100 : 0;

    return [
      'sede' => $quotation->sede?->abreviatura ?? '',
      'numero_cotizacion' => $quotation->quotation_number ?? '',
      'tipo_comprobante' => $tipoComprobante,
      'fecha_emision' => $document->fecha_de_emision ? $document->fecha_de_emision->format('d/m/Y') : '',
      'serie_comprobante' => $document->serie ?? '',
      'numero_comprobante' => $document->numero ?? '',
      'codigo_articulo' => $detail->product?->code ?? '',
      'nombre_articulo' => $detail->product?->name ?? '',
      'cantidad' => number_format($detail->quantity, 2, '.', ''),
      'pvp' => number_format($pvp, 2, '.', ''),
      'descuento_porcentaje' => number_format($detail->discount_percentage, 2, '.', ''),
      'neto' => number_format($neto, 2, '.', ''),
      'costo' => number_format($costPrice, 2, '.', ''),
      'costo_total' => number_format($costoTotal, 2, '.', ''),
      'beneficio' => number_format($beneficio, 2, '.', ''),
      'porcentaje_beneficio' => number_format($porcentajeBeneficio, 2, '.', ''),
      'comision' => '0.00',
      'cliente' => $quotation->invoiceTo?->full_name ?? '',
      'numero_documento_cliente' => $quotation->invoiceTo?->num_doc ?? '',
      'moneda' => 'PEN',
      'moneda_original' => $monedaOriginal,
      'document_id' => $document->id,
      'quotation_id' => $quotation->id,
      'detail_id' => $detail->id,
    ];
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
          // Filtros en la tabla orderQuotation
          if (in_array($column, ['sede_id'])) {
            $query->whereHas('orderQuotation', function ($q) use ($column, $value) {
              $q->where($column, $value);
            });
          } elseif ($column === 'document_type_id') {
            $query->where('sunat_concept_document_type_id', $value);
          }
          break;
        case 'like':
          // Filtros like en la tabla orderQuotation
          if (in_array($column, ['quotation_number'])) {
            $query->whereHas('orderQuotation', function ($q) use ($column, $value) {
              $q->where($column, 'like', '%' . $value . '%');
            });
          }
          break;
      }
    }
  }
}