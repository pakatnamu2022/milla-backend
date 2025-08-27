<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApFamiliesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_families', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('family')),
      ],
      'marca_id' => [
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
      'descripcion.string' => 'La descripción debe ser una cadena de texto.',
      'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ingresada ya existe en los registros.',

      'marca_id.integer' => 'El campo marca es obligatorio.',
      'marca_id.exists' => 'La marca seleccionado no existe',
    ];
  }
}
