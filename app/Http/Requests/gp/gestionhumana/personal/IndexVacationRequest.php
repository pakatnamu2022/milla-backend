<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use App\Http\Requests\IndexRequest;

class IndexVacationRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'empleado_id'        => 'nullable|integer',
      'sede_id'            => 'nullable|integer',
      'status_id'          => 'nullable|integer',
      'tipo'               => 'nullable|integer',
      'aprobacion_jefatura' => 'nullable|in:0,1',
      'aprobacion_rrhh'    => 'nullable|in:0,1',
      'fecha_inicio'       => 'nullable|date_format:Y-m-d',
      'fecha_fin'          => 'nullable|date_format:Y-m-d',
    ]);
  }
}
