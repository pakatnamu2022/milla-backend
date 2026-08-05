<?php

namespace App\Http\Traits;

/**
 * Trait Reportable — habilita exportación Excel/PDF en modelos Eloquent.
 *
 * USO EN EL MODELO
 * ----------------
 * 1. Agregar el trait:
 *      use App\Http\Traits\Reportable;
 *      class MiModelo extends BaseModel { use Reportable; }
 *
 * 2. Definir las propiedades que el trait lee:
 *
 *    $reportColumns   — columnas a exportar (ver tipo ReportColumn abajo)
 *    $reportRelations — relaciones Eloquent a eager-load antes de exportar
 *                       ej: ['sede', 'vehicle.model.family.brand']
 *    $reportStyles    — estilos PhpSpreadsheet para el Excel (opcional)
 *    $reportColorRules — colores condicionales por valor de celda (opcional)
 *                        ej: ['migration_status' => ['PENDIENTE' => 'FFA500', 'COMPLETADO' => '00AA00']]
 *
 * EJEMPLO MÍNIMO
 * --------------
 *    protected $reportColumns = [
 *        'number'           => ['label' => 'NRO.'],
 *        'emission_date'    => ['label' => 'FECHA', 'formatter' => 'date'],
 *        'supplier.name'    => ['label' => 'PROVEEDOR'],
 *        'total'            => ['label' => 'TOTAL', 'formatter' => 'currency'],
 *    ];
 *
 *    protected $reportRelations = ['supplier'];
 *
 * TIPO DE CADA COLUMNA (ReportColumn)
 * ------------------------------------
 * @phpstan-type ReportColumn array{
 *   label:        string,   // Encabezado visible en Excel/PDF — REQUERIDO
 *   width?:       int,      // Ancho sugerido de columna en caracteres
 *
 *   formatter?:  'date'        // Formatea Carbon/string → d/m/Y
 *               |'datetime'    // Formatea Carbon/string → d/m/Y H:i:s
 *               |'currency'    // Agrega $ y 2 decimales: $ 1,234.56
 *               |'number'      // number_format sin decimales: 1,234
 *               |'percentage'  // Devuelve el valor numérico (Excel da el formato %)
 *               |'boolean',    // true/false → true_label/false_label
 *
 *   true_label?:  string,  // Solo con formatter='boolean'. Default: 'Sí'
 *   false_label?: string,  // Solo con formatter='boolean'. Default: 'No'
 *
 *   accessor?:    string,  // Nombre de método del modelo. Llama $row->método()
 *                          // en lugar de data_get($row, 'clave')
 *
 *   value?:       mixed,   // Valor estático fijo. Ignora completamente el row.
 *                          // Útil para columnas calculadas fuera del modelo.
 *
 *   fallback?:    string,  // Dot-notation alternativo si el valor principal es null/''
 *                          // ej: 'vehicle.vin' como fallback de 'vin'
 *
 *   default?:     mixed,   // Valor final si sigue siendo null/'' tras el fallback.
 *                          // NOTA: si default aplica, el formatter se omite.
 * }
 *
 * PRIORIDAD DE RESOLUCIÓN DEL VALOR (de mayor a menor)
 * ------------------------------------------------------
 *   1. value    → valor estático, se usa tal cual
 *   2. accessor → método del modelo
 *   3. clave    → data_get($row, 'clave.dot.notation')
 *   4. fallback → si 3 es null/''
 *   5. default  → si sigue siendo null/'' (sin formatter)
 *   6. formatter→ se aplica sobre el valor resultante de 2/3/4
 */
trait Reportable
{
  public function getReportableColumns()
  {
    return $this->reportColumns ?? [];
  }

  public function getReportStyles()
  {
    return $this->reportStyles ?? [];
  }

  public function getReportRelations()
  {
    return $this->reportRelations ?? [];
  }

  public function getReportColorRules()
  {
    return $this->reportColorRules ?? [];
  }

  public function applyReportFilter($query, $filter)
  {
    $column = $filter['column'];
    $operator = $filter['operator'] ?? '=';
    $value = $filter['value'];

    if (str_contains($column, '.')) {
      $parts = explode('.', $column);
      $relation = implode('.', array_slice($parts, 0, -1));
      $field = end($parts);
      return $query->whereHas($relation, function ($q) use ($field, $operator, $value) {
        $this->applyReportFilter($q, ['column' => $field, 'operator' => $operator, 'value' => $value]);
      });
    }

    switch (strtolower($operator)) {
      case 'like':
        return $query->where($column, 'LIKE', "%{$value}%");
      case 'in':
        return $query->whereIn($column, is_array($value) ? $value : [$value]);
      case 'between':
        return $query->whereBetween($column, $value);
      case 'date_btw':
      case 'date_between':
        if (is_array($value) && count($value) === 2) {
          return $query->whereDate($column, '>=', $value[0])
            ->whereDate($column, '<=', $value[1]);
        }
        return $query;
      case '>=':
        return $query->where($column, '>=', $value);
      case '<=':
        return $query->where($column, '<=', $value);
      case '>':
        return $query->where($column, '>', $value);
      case '<':
        return $query->where($column, '<', $value);
      case '!=':
        return $query->where($column, '!=', $value);
      default:
        return $query->where($column, $operator, $value);
    }
  }

  public function getReportData($filters = [], $columns = null)
  {
    $query = $this->newQuery();

    // Cargar relaciones si están definidas
    $relations = $this->getReportRelations();
    if (!empty($relations)) {
      $query->with($relations);
    }

    // Aplicar filtros
    foreach ($filters as $filter) {
      $query = $this->applyReportFilter($query, $filter);
    }

    return $query->get();
  }
}
