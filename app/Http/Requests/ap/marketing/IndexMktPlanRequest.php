<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktPlanRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'brand_id' => 'nullable|integer|exists:ap_vehicle_brand,id',
      'year'     => 'nullable|integer|min:2020|max:2100',
      'status'   => 'nullable|string|in:draft,active,closed,cancelled',
      'search'   => 'nullable|string|max:150',
    ]);
  }
}
