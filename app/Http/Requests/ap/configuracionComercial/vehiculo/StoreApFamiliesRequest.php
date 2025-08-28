<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApFamiliesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'descripcion' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_families', 'descripcion')
          ->whereNull('deleted_at'),
      ],
      'marca_id' => [
        'required',
        'integer',
        'exists:ap_vehicle_brand,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'descripcion.required' => 'La descripción es obligatoria.',
      'descripcion.string' => 'La descripción debe ser una cadena de texto.',
      'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ingresada ya existe en los registros.',

      'marca_id.required' => 'Debe seleccionar un marca',
      'marca_id.integer' => 'El campo marca es obligatorio.',
      'marca_id.exists' => 'La marca seleccionado no existe',
    ];
  }
}
