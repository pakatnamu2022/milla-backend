<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use App\Http\Requests\IndexRequest;

class IndexWorkerStatusHistoryRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'empleado_id' => 'nullable|integer',
      'estado'      => 'nullable|integer',
      'sucursal_id' => 'nullable|integer',
      'fecha'       => 'nullable|date_format:Y-m-d',
    ]);
  }
}
