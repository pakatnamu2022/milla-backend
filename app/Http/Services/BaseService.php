<?php

namespace App\Http\Services;

use App\Http\Traits\Filterable;

class BaseService
{
  use Filterable;

  public function nextCorrelativeCount($model, $length = 8)
  {
    $count = $model::count();
    $correlative = $count == 0 ? 1 : $count + 1;
    return str_pad($correlative, $length, '0', STR_PAD_LEFT);
  }

  public function nextCorrelativeField($model, $field, $length = 8)
  {
    $last = $model::orderBy($field, 'desc')->first();
    $correlative = $last ? $last->$field + 1 : 1;
    return str_pad($correlative, $length, '0', STR_PAD_LEFT);
  }

  public function nextCorrelativeQuery($query, $field, $length = 8)
  {
    $last = $query->orderBy($field, 'desc')->first();
    $correlative = $last ? $last->$field + 1 : 1;
    return str_pad($correlative, $length, '0', STR_PAD_LEFT);
  }
}
