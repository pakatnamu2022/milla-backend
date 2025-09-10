<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApAssignmentLeadershipRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'boss_id' => 'required|exists:rrhh_persona,id',
      'workers' => 'required|array|min:1',
      'workers.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'boss_id.required' => 'El campo boss_id es obligatorio.',
      'boss_id.exists' => 'El jefe proporcionado no existe.',

      'workers.required' => 'El campo asesores es obligatorio.',
      'workers.array' => 'El campo asesores debe ser un arreglo.',
      'workers.min' => 'Debe proporcionar al menos un asesor.',

      'workers.*.integer' => 'Cada asesor debe ser un ID entero válido.',
      'workers.*.exists' => 'Uno o más IDs de asesores proporcionados no existen.',
    ];
  }
}
