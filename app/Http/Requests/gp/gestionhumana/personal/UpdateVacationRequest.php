<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVacationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'empleado_id'               => 'sometimes|integer|exists:rrhh_persona,id',
      'fecha_inicio'              => 'sometimes|date_format:Y-m-d',
      'fecha_fin'                 => 'sometimes|date_format:Y-m-d|after_or_equal:fecha_inicio',
      'tipo'                      => 'nullable|integer',
      'periodo_inicio'            => 'nullable|string|max:50',
      'periodo_fin'               => 'nullable|string|max:50',
      'observacion'               => 'nullable|string',
      'status_id'                 => 'nullable|integer',
      'aprobacion_jefatura'       => 'nullable|integer|in:0,1',
      'fecha_aprobacion_jefatura' => 'nullable|date_format:Y-m-d H:i:s',
      'user_jefatura_id'          => 'nullable|integer',
      'aprobacion_rrhh'           => 'nullable|integer|in:0,1',
      'fecha_aprobacion_rrhh'     => 'nullable|date_format:Y-m-d H:i:s',
      'user_id_rrhh'              => 'nullable|integer',
      'sede_id'                   => 'nullable|integer|exists:config_sede,id',
      'write_id'                  => 'nullable|integer',
    ];
  }
}
