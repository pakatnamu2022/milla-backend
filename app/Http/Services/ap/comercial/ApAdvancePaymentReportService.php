<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Services\common\ExportService;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ApAdvancePaymentReportService
{
  const STATUS_PENDING = 'PENDIENTE';
  const STATUS_INVOICED = 'FACTURADO';
  const STATUS_ANNULLED = 'ANULADO';

  /**
   * Comprobantes de anticipo del área comercial (mismos filtros que el export genérico
   * de comprobantes electrónicos), con el estado del anticipo y el comprobante que lo regulariza.
   */
  public function generate(Request $request): Collection
  {
    $user = $request->user();
    $model = new ElectronicDocument();

    $filters = (new ExportService())->buildFiltersFromRequest($request, ElectronicDocument::class);

    $query = ElectronicDocument::query()
      ->where('is_advance_payment', true)
      ->where('area_id', ApMasters::AREA_COMERCIAL)
      ->with($model->getReportRelations())
      ->orderBy('fecha_de_emision')
      ->orderBy('id');

    if ($user->role->id !== Constants::TICS_ROL_ID) {
      $sedes = $user->sedes()->pluck('config_sede.id')->toArray();
      $query->whereHas('seriesModel', fn($q) => $q->whereIn('sede_id', $sedes));
    }

    foreach ($filters as $filter) {
      // is_advance_payment y area_id ya están forzados arriba
      if (in_array($filter['column'], ['is_advance_payment', 'area_id'], true)) continue;
      $query = $model->applyReportFilter($query, $filter);
    }

    $advances = $query->get();
    $regularizations = $this->regularizationsByAdvance($advances);

    return $advances->each(function (ElectronicDocument $advance) use ($regularizations) {
      $invoices = $regularizations->get($advance->id, collect());

      $advance->setAttribute('advance_status_label', $this->statusLabel($advance, $invoices));
      $advance->setAttribute('regularization_document_number', $invoices->implode(', '));
    });
  }

  /**
   * Columnas del reporte: las del comprobante electrónico + las dos de anticipo,
   * ubicadas a continuación del ESTADO del comprobante.
   */
  public function columns(): array
  {
    $columns = [];

    foreach ((new ElectronicDocument())->getReportableColumns() as $key => $column) {
      $columns[$key] = $column;

      if ($key === 'status_label') {
        $columns['advance_status_label'] = ['label' => 'ESTADO ANTICIPO', 'formatter' => null];
        $columns['regularization_document_number'] = ['label' => 'COMPROBANTE DE REGULARIZACIÓN', 'formatter' => null];
      }
    }

    return $columns;
  }

  public function colorRules(): array
  {
    return (new ElectronicDocument())->getReportColorRules() + [
        'advance_status_label' => [
          self::STATUS_PENDING => ['bg' => 'FFF9C4', 'text' => 'F57F17'],
          self::STATUS_INVOICED => ['bg' => 'C8E6C9', 'text' => '1B5E20'],
          self::STATUS_ANNULLED => ['bg' => 'FFCDD2', 'text' => 'B71C1C'],
        ],
      ];
  }

  /**
   * Un anticipo se regulariza cuando otro comprobante vigente (no anulado, aceptado por SUNAT)
   * tiene un ítem de regularización que lo referencia por ID o por serie-número.
   *
   * @return Collection<int, Collection<int, string>> full_number de los comprobantes, por ID de anticipo
   */
  private function regularizationsByAdvance(Collection $advances): Collection
  {
    if ($advances->isEmpty()) return collect();

    $items = ElectronicDocumentItem::query()
      ->where('anticipo_regularizacion', true)
      ->where(function ($q) use ($advances) {
        $q->whereIn('reference_document_id', $advances->pluck('id'))
          ->orWhere(function ($q) use ($advances) {
            $q->whereIn('anticipo_documento_serie', $advances->pluck('serie')->unique())
              ->whereIn('anticipo_documento_numero', $advances->pluck('numero')->unique());
          });
      })
      ->whereHas('electronicDocument', function ($q) {
        $q->where('anulado', false)->where('aceptada_por_sunat', true);
      })
      ->with('electronicDocument:id,full_number,fecha_de_emision')
      ->get();

    return $advances->mapWithKeys(function (ElectronicDocument $advance) use ($items) {
      $numbers = $items
        ->filter(fn($item) => (int)$item->ap_billing_electronic_document_id !== (int)$advance->id
          && ((int)$item->reference_document_id === (int)$advance->id
            || ($item->anticipo_documento_serie === $advance->serie
              && (int)$item->anticipo_documento_numero === (int)$advance->numero)))
        ->map->electronicDocument
        ->unique('id')
        ->sortBy('fecha_de_emision')
        ->pluck('full_number');

      return [$advance->id => $numbers->values()];
    });
  }

  private function statusLabel(ElectronicDocument $advance, Collection $invoices): string
  {
    if ($advance->anulado || $advance->status === ElectronicDocument::STATUS_CANCELLED) {
      return self::STATUS_ANNULLED;
    }

    return $invoices->isNotEmpty() ? self::STATUS_INVOICED : self::STATUS_PENDING;
  }
}
