<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use Illuminate\Foundation\Http\FormRequest;

class StoreVacationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'empleado_id'    => 'required|integer|exists:rrhh_persona,id',
      'fecha_inicio'   => 'required|date_format:Y-m-d',
      'fecha_fin'      => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
      'tipo'           => 'nullable|integer',
      'periodo_inicio' => 'nullable|string|max:50',
      'periodo_fin'    => 'nullable|string|max:50',
      'observacion'    => 'nullable|string',
      'status_id'      => 'nullable|integer',
      'sede_id'        => 'nullable|integer|exists:config_sede,id',
      'write_id'       => 'nullable|integer',
    ];
  }
}
