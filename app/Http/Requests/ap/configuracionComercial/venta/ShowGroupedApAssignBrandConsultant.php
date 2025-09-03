<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\IndexRequest;

class ShowGroupedApAssignBrandConsultant extends IndexRequest
{
  public function rules(): array
  {
    return [
      'anio' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'sede_id' => 'required|integer',
      'marca_id' => 'required|integer',
    ];
  }

  public function messages(): array
  {
    return [
      'anio.required' => 'El año es obligatorio.',
      'anio.integer' => 'El año debe ser un número entero.',

      'month.required' => 'El mes es obligatorio.',
      'month.integer' => 'El mes debe ser un número entero.',
      'month.min' => 'El mes no puede ser menor a 1.',
      'month.max' => 'El mes no puede ser mayor a 12.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.integer' => 'La sede debe ser un número entero.',

      'marca_id.required' => 'La marca es obligatoria.',
      'marca_id.integer' => 'La marca debe ser un número entero.',
    ];
  }
}
