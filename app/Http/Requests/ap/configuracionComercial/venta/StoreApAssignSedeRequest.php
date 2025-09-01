<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreApAssignSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_id' => 'required|exists:config_sede,id',
      'asesores' => 'required|array|min:1',
      'asesores.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'sede_id.required' => 'El campo sede_id es obligatorio.',
      'sede_id.exists' => 'La sede_id proporcionada no existe.',
      'asesores.required' => 'El campo asesores es obligatorio.',
      'asesores.array' => 'El campo asesores debe ser un arreglo.',
      'asesores.min' => 'Debe proporcionar al menos un asesor.',
      'asesores.*.integer' => 'Cada asesor debe ser un ID entero válido.',
      'asesores.*.exists' => 'Uno o más IDs de asesores proporcionados no existen.',
    ];
  }
}
