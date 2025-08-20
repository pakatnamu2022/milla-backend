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

  protected function getFilteredResults($modelOrQuery, $request, $filters, $sorts, $resource)
  {
    $query = $modelOrQuery instanceof Builder ? $modelOrQuery : $modelOrQuery::query();

    $query = $this->applyFilters($query, $request, $filters);
    $query = $this->applySorting($query, $request, $sorts);

    $all = $request->query('all', false) === 'true';

    $results = $all ? $query->get() : $query->paginate($request->query('per_page', Constants::DEFAULT_PER_PAGE));

    return $all ? response()->json($resource::collection($results)) : $resource::collection($results);
  }
}
