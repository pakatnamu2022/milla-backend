<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApCommercialManagerBrandGroupRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => 'required|integer|min:2000|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'brand_group_id' => 'required|exists:ap_commercial_masters,id',
      'commercial_managers' => 'required|array|min:1',
      'commercial_managers.*' => 'integer|exists:rrhh_persona,id',
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

      'brand_group_id.required' => 'El grupo de marca es obligatorio.',
      'brand_group_id.exists' => 'El grupo de marca no existe en la base de datos.',

      'commercial_managers.required' => 'Los gerentes comerciales asignados son obligatorios.',
      'commercial_managers.array' => 'Los gerentes comerciales asignados deben ser un arreglo.',
      'commercial_managers.min' => 'Debe asignar al menos un gerente comercial.',

      'commercial_managers.*.integer' => 'Cada gerente comercial asignado debe ser un número entero.',
      'commercial_managers.*.exists' => 'Uno o más gerentes comerciales asignados no existen en la base de datos.',
    ];
  }
}
