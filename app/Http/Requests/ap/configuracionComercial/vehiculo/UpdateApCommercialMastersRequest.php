<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApCommercialMastersRequest extends StoreRequest
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
          ->whereNull('deleted_at')
          ->ignore($this->route('commercialMaster')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_commercial_masters', 'descripcion')
          ->where('tipo', $this->tipo)
          ->whereNull('deleted_at')
          ->ignore($this->route('commercialMaster')),
      ],
      'tipo' => [
        'nullable',
        'string',
        'max:100',
      ],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.string' => 'El código debe ser una cadena de texto.',
      'codigo.max' => 'El código no puede exceder los 50 caracteres.',
      'codigo.unique' => 'El código ya está registrado.',

      'descripcion.string' => 'La descripción debe ser una cadena de texto.',
      'descripcion.max' => 'La descripción no puede exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ya está registrada.',

      'tipo.string' => 'El tipo debe ser una cadena de texto.',
      'tipo.max' => 'El tipo no puede exceder los 100 caracteres.',
      'tipo.unique' => 'El tipo ya está registrado.',
    ];
  }
}
