<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApAssignCompanyBranchRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => 'required|integer|min:2000|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'sede_id' => 'required|exists:config_sede,id',
      'assigned_workers' => 'required|array|min:1',
      'assigned_workers.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'year.required' => 'El año es obligatorio.',
      'year.integer' => 'El año debe ser un número entero.',
      'year.min' => 'El año no puede ser menor a 2000.',
      'year.max' => 'El año no puede ser mayor a 2100.',

      'month.required' => 'El mes es obligatorio.',
      'month.integer' => 'El mes debe ser un número entero.',
      'month.min' => 'El mes no puede ser menor a 1.',
      'month.max' => 'El mes no puede ser mayor a 12.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede no existe en la base de datos.',

      'assigned_workers.required' => 'Los asesores asignados son obligatorios.',
      'assigned_workers.array' => 'Los asesores asignados deben ser un arreglo.',
      'assigned_workers.min' => 'Debe asignar al menos un trabajador.',

      'assigned_workers.*.integer' => 'Cada asesor asignado debe ser un número entero.',
      'assigned_workers.*.exists' => 'Uno o más asesores asignados no existen en la base de datos.',
    ];
  }
}
