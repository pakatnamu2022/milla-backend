<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class ExportVehiclesInventoryRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'format'           => 'nullable|in:excel,pdf',
      'title'            => 'nullable|string|max:255',
      'emission_date'    => 'nullable|array|size:2',
      'emission_date.*'  => 'nullable|date',
      'sede_id'          => 'nullable|integer|exists:sedes,id',
    ];
  }
}
