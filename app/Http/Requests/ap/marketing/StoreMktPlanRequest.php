<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class StoreMktPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'brand_id'    => 'nullable|integer|exists:ap_vehicle_brand,id',
      'name'        => 'required|string|max:150',
      'concept'     => 'nullable|string|max:150',
      'year'        => 'required|integer|min:2020|max:2100',
      'description' => 'nullable|string',
      'status'      => 'nullable|string|in:draft,active,closed,cancelled',
    ];
  }

  public function attributes(): array
  {
    return [
      'brand_id'    => 'marca',
      'name'        => 'nombre',
      'concept'     => 'concepto',
      'year'        => 'año',
      'description' => 'descripción',
      'status'      => 'estado',
    ];
  }
}
