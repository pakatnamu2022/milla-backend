<?php

namespace App\Http\Traits;
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

  public function applyReportFilter($query, $filter)
  {
    $column = $filter['column'];
    $operator = $filter['operator'] ?? '=';
    $value = $filter['value'];

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
