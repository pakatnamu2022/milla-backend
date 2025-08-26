<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApCommercialMastersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_commercial_masters', 'codigo')
          ->where('tipo', $this->tipo)
          ->whereNull('deleted_at'),
      ],
      'descripcion' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_commercial_masters', 'descripcion')
          ->where('tipo', $this->tipo)
          ->whereNull('deleted_at'),
      ],
      'tipo' => [
        'required',
        'string',
        'max:100',
      ]
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.required' => 'El campo código es obligatorio.',
      'codigo.string' => 'El código debe ser una cadena de texto.',
      'codigo.max' => 'El código no debe exceder los 50 caracteres.',
      'codigo.unique' => 'El código ingresado ya existe en los registros.',

      'descripcion.required' => 'La descripción es obligatoria.',
      'descripcion.string' => 'La descripción debe ser una cadena de texto.',
      'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ingresada ya existe en los registros.',

      'tipo.required' => 'El tipo es obligatorio.',
      'tipo.string' => 'El tipo debe ser una cadena de texto.',
      'tipo.max' => 'El tipo no debe exceder los 100 caracteres.',
      'tipo.unique' => 'El tipo ingresado ya existe en los registros.',
    ];
  }
}
