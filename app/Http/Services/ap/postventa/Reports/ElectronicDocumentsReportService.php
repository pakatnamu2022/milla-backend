<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Collection;

class ElectronicDocumentsReportService
{
  /**
   * Obtiene el reporte de Documentos Electrónicos (solo cabecera con totales)
   *
   * @param array $filters
   * @return Collection
   */
  public function getElectronicDocumentReport(array $filters = []): Collection
  {
    // Consultar ElectronicDocuments con sus relaciones
    $query = ElectronicDocument::query()
      ->with([
        'documentType',
        'identityDocumentType',
        'currency',
        'seriesModel.sede',
        'client',
        'area',
        'creator',
      ])
      ->where('anulado', false);

    // Aplicar filtros
    $this->applyFilters($query, $filters);

    // Obtener documentos ordenados por fecha de emisión
    $documents = $query->orderBy('fecha_de_emision', 'desc')
      ->orderBy('full_number', 'desc')
      ->get();

    // Transformar documentos para el reporte
    $reportData = $documents->flatMap(function ($document) {
      return $this->transformDocumentForReport($document);
    })->values();

    return $reportData;
  }

  /**
   * Transforma un documento electrónico en filas del reporte
   * Retorna solo la fila de cabecera con totales
   *
   * @param ElectronicDocument $document
   * @return Collection
   */
  private function transformDocumentForReport(ElectronicDocument $document): Collection
  {
    $rows = collect();

    // Solo retornar la fila de cabecera del documento con totales
    $headerRow = [
      'documento_id' => $document->id,
      'full_number' => $document->full_number,
      'tipo_documento' => $document->documentType?->description ?? '',
      'fecha_emision' => $document->fecha_de_emision?->format('d/m/Y') ?? '',
      'fecha_vencimiento' => $document->fecha_de_vencimiento?->format('d/m/Y') ?? '',
      'cliente_documento' => $document->cliente_numero_de_documento ?? '',
      'tipo_doc_cliente' => $document->identityDocumentType?->description ?? '',
      'cliente_nombre' => $document->cliente_denominacion ?? '',
      'cliente_direccion' => $document->cliente_direccion ?? '',
      'cliente_email' => $document->cliente_email ?? '',
      'moneda' => $document->currency?->description ?? '',
      'tipo_cambio' => number_format((float)$document->tipo_de_cambio, 3, '.', ''),
      'total_gravada' => number_format((float)$document->total_gravada, 2, '.', ''),
      'total_inafecta' => number_format((float)$document->total_inafecta, 2, '.', ''),
      'total_exonerada' => number_format((float)$document->total_exonerada, 2, '.', ''),
      'total_igv' => number_format((float)$document->total_igv, 2, '.', ''),
      'total' => number_format((float)$document->total, 2, '.', ''),
      'area' => $document->area?->description ?? '',
      'sede' => $document->seriesModel?->sede?->suc_abrev ?? '',
      'estado' => $document->status_label ?? '',
      'aceptada_sunat' => $document->aceptada_por_sunat ? 'SI' : 'NO',
      'creado_por' => $document->creator?->name ?? '',
      'fecha_creacion' => $document->created_at?->format('d/m/Y H:i:s') ?? '',
    ];

    $rows->push($headerRow);

    return $rows;
  }

  /**
   * Aplica filtros a la query de ElectronicDocument
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
        case 'in':
          if (is_array($value)) {
            $query->whereIn($column, $value);
          }
          break;

        case 'between':
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween($column, [$value[0], $value[1]]);
          }
          break;

        case 'dateRange':
          // Filtrar por rango de fechas usando whereDate para ignorar la hora
          if (is_array($value) && count($value) === 2) {
            $query->whereDate($column, '>=', $value[0])
              ->whereDate($column, '<=', $value[1]);
          }
          break;

        case '=':
          // Para relaciones con punto (ej: seriesModel.sede_id)
          if (str_contains($column, '.')) {
            $parts = explode('.', $column);
            $relation = $parts[0];
            $relationColumn = $parts[1];

            $query->whereHas($relation, function ($q) use ($relationColumn, $value) {
              $q->where($relationColumn, $value);
            });
          } else {
            $query->where($column, $value);
          }
          break;

        case 'like':
          $query->where($column, 'like', '%' . $value . '%');
          break;
      }
    }
  }
}
