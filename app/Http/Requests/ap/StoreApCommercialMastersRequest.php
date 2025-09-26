<?php

namespace App\Http\Requests\ap;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApCommercialMastersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_commercial_masters', 'code')
          ->where('type', $this->type)
          ->whereNotIn('type', ['TIPO_DOCUMENTO'])
          ->whereNull('deleted_at'),
      ],
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_commercial_masters', 'description')
          ->where('type', $this->type)
          ->whereNull('deleted_at'),
      ],
      'type' => [
        'required',
        'string',
        'max:100',
      ]
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El campo código es obligatorio.',
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no debe exceder los 50 caracteres.',
      'code.unique' => 'El código ingresado ya existe en los registros.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ingresada ya existe en los registros.',

      'type.required' => 'El tipo es obligatorio.',
      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.max' => 'El tipo no debe exceder los 100 caracteres.',
      'type.unique' => 'El tipo ingresado ya existe en los registros.',
    ];
  }
}
