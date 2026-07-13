<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Collection;

class ElectronicDocumentsReportService
{
  /**
   * Obtiene el reporte de documentos electrónicos
   *
   * @param array $filters
   * @return Collection
   */
  public function getElectronicDocumentsReport(array $filters = []): Collection
  {
    $query = ElectronicDocument::query()
      ->with([
        'items',
        'documentType',
        'currency',
      ])
      ->where('area_id', ApMasters::AREA_POSVENTA)
      ->where('aceptada_por_sunat', true);

    // Aplicar filtros
    $this->applyFilters($query, $filters);

    $documents = $query->orderBy('fecha_de_emision')->get();

    // Transformar documentos para el reporte
    return $documents->map(function ($document) {
      return $this->transformDocumentForReport($document);
    });
  }

  /**
   * Transforma un documento electrónico en el formato del reporte
   *
   * @param ElectronicDocument $document
   * @return array
   */
  private function transformDocumentForReport(ElectronicDocument $document): array
  {
    // Obtener tipo de comprobante
    $tipo = $this->getDocumentTypeName($document->sunat_concept_document_type_id);

    // Concatenar descripciones de items
    $descripcion = $document->items
      ->pluck('descripcion')
      ->filter()
      ->implode(', ');

    // Obtener moneda
    $moneda = $document->currency?->description ?? '';

    return [
      'tipo' => $tipo,
      'fecha' => $document->fecha_de_emision ? $document->fecha_de_emision->format('d/m/Y') : '',
      'cliente' => $document->cliente_denominacion ?? '',
      'descripcion' => $descripcion,
      'serie' => $document->serie ?? '',
      'numero' => $document->numero ?? '',
      'total' => number_format($document->total ?? 0, 2, '.', ''),
      'moneda' => $moneda,
    ];
  }

  /**
   * Obtiene el nombre del tipo de documento
   *
   * @param int|null $documentTypeId
   * @return string
   */
  private function getDocumentTypeName(?int $documentTypeId): string
  {
    return match ($documentTypeId) {
      29 => 'FACTURA',
      30 => 'BOLETA',
      default => '',
    };
  }

  /**
   * Aplica filtros a la query
   *
   * @param $query
   * @param array $filters
   * @return void
   */
  private function applyFilters($query, array $filters): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      switch ($operator) {
        case 'date_between':
          // Filtro de rango de fechas
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween($column, [$value[0], $value[1]]);
          }
          break;
        case '=':
          $query->where($column, $value);
          break;
      }
    }
  }
}