<?php

namespace App\Http\Traits;

use App\Http\Utils\Constants;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
  protected function applyFilters($query, $request, $filters)
  {
    foreach ($filters as $filter => $operator) {
      $paramName = str_replace('.', '$', $filter);
      $value = $request->query($paramName);

      if ($value === null) {
        continue;
      }

      // 👇 soporte para alias/columnas virtuales (se filtran con HAVING)
      if (is_string($operator) && str_starts_with($operator, 'virtual')) {
        $this->applyVirtualFilter($query, $filter, $operator, $value);
        continue;
      }

      // 👇 NUEVO: soporte para accessors (se filtran después de la query)
      if (is_string($operator) && str_starts_with($operator, 'accessor')) {
        // Los accessors se manejan después en getFilteredResults
        continue;
      }

      if ($filter === 'search') {
        $fields = $operator;
        $query->where(function ($q) use ($fields, $value) {
          foreach ($fields as $field) {
            if (str_contains($field, '.')) {
              $parts = explode('.', $field);
              $relation = implode('.', array_slice($parts, 0, -1));
              $relationField = end($parts);

              $q->orWhereHas($relation, function ($relQuery) use ($relationField, $value) {
                $relQuery->where($relationField, 'like', '%' . $value . '%');
              });
            } else {
              $q->orWhere($field, 'like', '%' . $value . '%');
            }
          }
        });
      } elseif (str_contains($filter, '.')) {
        $parts = explode('.', $filter);
        $relation = implode('.', array_slice($parts, 0, -1));
        $relationField = end($parts);

        $query->whereHas($relation, function ($q) use ($relationField, $operator, $value) {
          $this->applyFilterCondition($q, $relationField, $operator, $value);
        });
      } else {
        $this->applyFilterCondition($query, $filter, $operator, $value);
      }
    }

    return $query;
  }

  protected function applyVirtualFilter($query, $alias, $operator, $value): void
  {
    switch ($operator) {
      case 'virtual_bool':
        // acepta 1/0/true/false/yes/no/on/off
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
          // edge: "0" evalúa a null con FILTER_NULL_ON_FAILURE si viene como string raro;
          // forzamos falso solo si literalmente es '0'
          if ($value === '0' || $value === 0) {
            $bool = false;
          } else {
            return; // valor inválido, no aplica filtro
          }
        }
        $query->having($alias, '=', $bool ? 1 : 0);
        break;

      case 'virtual_like':
        $query->having($alias, 'like', '%' . $value . '%');
        break;

      case 'virtual_numeric':
        // útil por si luego quieres exponer total_personas, etc.
        if (is_array($value) && count($value) === 2) {
          $query->havingBetween($alias, [$value[0], $value[1]]);
        } else {
          $query->having($alias, '=', is_numeric($value) ? $value : 0);
        }
        break;

      default:
        $query->having($alias, '=', $value);
        break;
    }
  }

  // 👇 NUEVO: Método para aplicar filtros de accessor
  protected function applyAccessorFilters($collection, $request, $filters)
  {
    foreach ($filters as $filter => $operator) {
      if (!is_string($operator) || !str_starts_with($operator, 'accessor')) {
        continue;
      }

      $paramName = str_replace('.', '$', $filter);
      $value = $request->query($paramName);

      if ($value === null) {
        continue;
      }

      $collection = $collection->filter(function ($item) use ($filter, $operator, $value) {
        return $this->applyAccessorFilterCondition($item, $filter, $operator, $value);
      });
    }

    return $collection;
  }

  // 👇 NUEVO: Condiciones para filtros de accessor
  protected function applyAccessorFilterCondition($item, $filter, $operator, $value)
  {
    $itemValue = data_get($item, $filter);

    switch ($operator) {
      case 'accessor_bool':
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null && ($value === '0' || $value === 0)) {
          $bool = false;
        }
        return $itemValue == $bool;

      case 'accessor_like':
        return stripos($itemValue, $value) !== false;

      case 'accessor_numeric':
        return $itemValue == $value;

      case 'accessor_gt':
        return $itemValue > $value;

      case 'accessor_lt':
        return $itemValue < $value;

      case 'accessor_gte':
        return $itemValue >= $value;

      case 'accessor_lte':
        return $itemValue <= $value;

      case 'accessor_between':
        if (is_array($value) && count($value) === 2) {
          return $itemValue >= $value[0] && $itemValue <= $value[1];
        }
        return false;

      case 'accessor_in':
        return in_array($itemValue, (array)$value);

      default:
        return $itemValue == $value;
    }
  }

  protected function applyFilterCondition($query, $filter, $operator, $value)
  {
    switch ($operator) {
      case 'like':
        $query->where($filter, 'like', '%' . $value . '%');
        break;
      case 'between':
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween($filter, $value);
        }
        break;
      case '>':
        $query->where($filter, '>', $value);
        break;
      case '<':
        $query->where($filter, '<', $value);
        break;
      case '>=':
        $query->where($filter, '>=', $value);
        break;
      case '<=':
        $query->where($filter, '<=', $value);
        break;
      case '=':
        $query->where($filter, '=', $value);
        break;
      case 'in':
        $query->whereIn($filter, $value);
        break;
      default:
        break;
    }
  }

  protected function applySorting($query, $request, $sorts)
  {
    $sortField = $request->query('sort');
    $sortOrder = $request->query('direction', 'desc');

    if ($sortField !== null && in_array($sortField, $sorts)) {
      $query->orderBy($sortField, $sortOrder);
    } else {
      $query->orderBy('id', $sortOrder);
    }

    return $query;
  }

  // 👇 NUEVO: Método para ordenar por accessor
  protected function applyAccessorSorting($collection, $request, $sorts)
  {
    $sortField = $request->query('sort');
    $sortOrder = $request->query('direction', 'desc');

    if ($sortField !== null && in_array($sortField, $sorts) && str_starts_with($sorts[$sortField] ?? '', 'accessor')) {
      $collection = $collection->sortBy(function ($item) use ($sortField) {
        return data_get($item, $sortField);
      }, SORT_REGULAR, $sortOrder === 'desc');
    }

    return $collection;
  }

  protected function getFilteredResults($modelOrQuery, $request, $filters, $sorts, $resource)
  {
    $query = $modelOrQuery instanceof Builder ? $modelOrQuery : $modelOrQuery::query();

    // Aplicar filtros de base de datos
    $query = $this->applyFilters($query, $request, $filters);

    // Solo aplicar ordenamiento de BD si no es por accessor
    $sortField = $request->query('sort');
    $hasAccessorSort = $sortField && isset($sorts[$sortField]) &&
      is_string($sorts[$sortField]) &&
      str_starts_with($sorts[$sortField], 'accessor');

    if (!$hasAccessorSort) {
      $query = $this->applySorting($query, $request, $sorts);
    }

    $all = $request->query('all', false) === 'true';

    // Verificar si hay filtros de accessor
    $hasAccessorFilters = collect($filters)->contains(function ($operator) {
      return is_string($operator) && str_starts_with($operator, 'accessor');
    });

    if ($hasAccessorFilters || $hasAccessorSort) {
      // Si hay filtros o ordenamiento de accessor, obtenemos todo y filtramos en memoria
      $results = $query->get();

      // Aplicar filtros de accessor
      if ($hasAccessorFilters) {
        $results = $this->applyAccessorFilters($results, $request, $filters);
      }

      // Aplicar ordenamiento de accessor
      if ($hasAccessorSort) {
        $results = $this->applyAccessorSorting($results, $request, $sorts);
        $results = $results->values(); // Re-indexar
      }

      // Paginación manual para accessors
      if (!$all) {
        $perPage = $request->query('per_page', Constants::DEFAULT_PER_PAGE);
        $page = $request->query('page', 1);
        $total = $results->count();
        $results = $results->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
          'data' => $resource::collection($results),
          'current_page' => (int)$page,
          'per_page' => (int)$perPage,
          'total' => $total,
          'last_page' => ceil($total / $perPage),
        ]);
      }

      return response()->json($resource::collection($results));
    }

    // Flujo normal sin accessors
    $results = $all ? $query->get() : $query->paginate($request->query('per_page', Constants::DEFAULT_PER_PAGE));

    return $all ? response()->json($resource::collection($results)) : $resource::collection($results);
  }
}
