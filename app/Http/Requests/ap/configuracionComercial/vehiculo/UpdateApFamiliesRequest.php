<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApFamiliesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_families', 'description')
          ->whereNull('deleted_at')
          ->ignore($this->route('family')),
      ],
      'brand_id' => [
        'nullable',
        'integer',
        'exists:ap_vehicle_brand,id',
      ],
      'status' => ['nullable', 'boolean']
    ];
  }

  public function messages(): array
  {
    return [
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ingresada ya existe en los registros.',

      'brand_id.integer' => 'El campo marca es obligatorio.',
      'brand_id.exists' => 'La marca seleccionado no existe',
    ];
  }
}
