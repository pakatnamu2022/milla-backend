<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Http\Requests\IndexRequest;

class IndexSelectedWorkerRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      ...parent::rules(),
      'sede_id'            => 'nullable|integer',
      'tipo_trabajador_id' => 'nullable',
    ];
  }
}
