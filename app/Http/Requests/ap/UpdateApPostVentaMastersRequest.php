<?php

namespace App\Http\Requests\ap;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApPostVentaMastersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_post_venta_masters', 'code')
          ->where('type', $this->type)
          ->whereNotIn('type', ['TIPO_DOCUMENTO'])
          ->whereNull('deleted_at')
          ->ignore($this->route('commercialMaster')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_post_venta_masters', 'description')
          ->where('type', $this->type)
          ->whereNull('deleted_at')
          ->ignore($this->route('commercialMaster')),
      ],
      'type' => [
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
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no puede exceder los 50 caracteres.',
      'code.unique' => 'El código ya está registrado.',

      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no puede exceder los 255 caracteres.',
      'description.unique' => 'La descripción ya está registrada.',

      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.max' => 'El tipo no puede exceder los 100 caracteres.',
      'type.unique' => 'El tipo ya está registrado.',
    ];
  }
}
