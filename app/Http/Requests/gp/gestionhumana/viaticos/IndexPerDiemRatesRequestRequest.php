<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\IndexRequest;

class IndexPerDiemRatesRequestRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'district_id' => ['required', 'integer', 'exists:district,id'],
      'category_id' => ['required', 'integer', 'exists:gh_per_diem_category,id'],
    ];
  }
}
