<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApFamiliesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_families', 'description')
          ->whereNull('deleted_at'),
      ],
      'brand_id' => [
        'required',
        'integer',
        'exists:ap_vehicle_brand,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ingresada ya existe en los registros.',

      'brand_id.required' => 'Debe seleccionar un marca',
      'brand_id.integer' => 'El campo marca es obligatorio.',
      'brand_id.exists' => 'La marca seleccionado no existe',
    ];
  }
}
