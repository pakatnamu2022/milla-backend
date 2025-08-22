<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApVehicleCategoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_categoria_vehiculos', 'name')->whereNull('deleted_at'),],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El campo nombre es obligatorio.',
      'name.string' => 'El campo nombre debe ser una cadena de texto.',
      'name.max' => 'El campo nombre no debe exceder los 100 caracteres.',
      'name.unique' => 'El campo nombre ya existe.',
    ];
  }
}
