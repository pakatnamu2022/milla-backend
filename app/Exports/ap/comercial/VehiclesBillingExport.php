<?php

namespace App\Exports\ap\comercial;

use App\Exports\GeneralExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de Facturación Comercial con múltiples hojas:
 *  1. Facturación  - 1 fila por solicitud, solo comprobantes vigentes (neto > tolerancia)
 *  2. Notas de Crédito - 1 fila por NC (aplicadas + referenciadas + en rango)
 *  3. Refacturaciones - 1 fila por solicitud refacturada / con NC parcial
 *
 * Cada hoja se arma con GeneralExport (que ya implementa WithTitle).
 */
class VehiclesBillingExport implements WithMultipleSheets
{
  /**
   * @param array<int,array{title:string,columns:array,rows:array|\Illuminate\Support\Collection,cellColorRules?:array,columnFormats?:array,wrapTextColumns?:array}> $sheetsConfig
   */
  public function __construct(protected array $sheetsConfig) {}

  public function sheets(): array
  {
    return array_map(
      fn(array $c) => new GeneralExport(
        $c['rows'],
        $c['columns'],
        $c['title'],
        [],
        $c['cellColorRules'] ?? [],
        $c['columnFormats'] ?? [],
        $c['wrapTextColumns'] ?? []
      ),
      $this->sheetsConfig
    );
  }
}
