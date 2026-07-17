<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class ExportVehiclesBillingRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'format'           => 'nullable|in:excel,pdf',
      'title'            => 'nullable|string|max:255',
      'fecha_de_emision' => 'nullable|array|size:2',
      'fecha_de_emision.*' => 'nullable|date',
      'sede_id'          => 'nullable|integer|exists:sedes,id',
    ];
  }
}
