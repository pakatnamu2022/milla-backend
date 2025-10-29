<?php

namespace App\Http\Services;

use App\Http\Traits\Filterable;

class BaseService
{
  use Filterable;

  public function nextCorrelativeCount($model, $length = 8, $where = []): string
  {
    $query = $model::query();
    if (!empty($where)) {
      $query->where($where);
    }
    $count = $query->count();
    $correlative = $count == 0 ? 1 : $count + 1;
    return str_pad($correlative, $length, '0', STR_PAD_LEFT);
  }


  public function nextCorrelativeField($model, $field, $length = 8): string
  {
    $last = $model::orderBy($field, 'desc')->first();
    $correlative = $last ? $last->$field + 1 : 1;
    return str_pad($correlative, $length, '0', STR_PAD_LEFT);
  }

  public function nextCorrelativeQuery($query, $field, $length = 8): string
  {
    $last = $query->orderBy($field, 'desc')->first();
    $correlative = $last ? $last->$field + 1 : 1;
    return $this->completeNumber($correlative, $length);
  }

  public function nextCorrelativeQueryInteger($query, $field): int
  {
    $last = $query->orderBy($field, 'desc')->first();
    return $last ? $last->$field + 1 : 1;
  }

  public function completeSeries($series, $length = 2): string
  {
    return str_pad($series, $length, '0', STR_PAD_LEFT);
  }

  public function completeNumber($number, $length = 8): string
  {
    return str_pad($number, $length, '0', STR_PAD_LEFT);
  }
}
