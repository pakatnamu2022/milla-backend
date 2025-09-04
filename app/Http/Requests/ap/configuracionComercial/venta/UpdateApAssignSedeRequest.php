<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApAssignSedeRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'anio' => 'required|integer|min:2000|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'sede_id' => 'required|exists:config_sede,id',
      'asesores' => 'required|array|min:1',
      'asesores.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'anio.required' => 'El campo anio es obligatorio.',
      'anio.integer' => 'El campo anio debe ser un número entero.',
      'anio.min' => 'El campo anio debe ser al menos 2000.',
      'anio.max' => 'El campo anio no debe ser mayor que 2100.',
      'month.required' => 'El campo month es obligatorio.',
      'month.integer' => 'El campo month debe ser un número entero.',
      'month.min' => 'El campo month debe ser al menos 1.',
      'month.max' => 'El campo month no debe ser mayor que 12.',
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
