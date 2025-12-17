<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\IndexRequest;

class IndexPerDiemRatesRequestRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'district_id' => ['required', 'integer', 'exists:districts,id'],
      'category_id' => ['required', 'integer', 'exists:per_diem_categories,id'],
    ];
  }
}
