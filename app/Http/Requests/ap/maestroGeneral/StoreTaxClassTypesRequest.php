<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreTaxClassTypesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'required',
        'string',
        'max:50',
        Rule::unique('tax_class_types', 'dyn_code')
          ->whereNull('deleted_at'),
      ],
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('tax_class_types', 'description')
          ->whereNull('deleted_at'),
      ],
      'type' => [
        'required',
        'string',
        'max:100',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.required' => 'El campo código es obligatorio.',
      'dyn_code.string' => 'El código debe ser una cadena de texto.',
      'dyn_code.max' => 'El código no debe exceder los 50 caracteres.',
      'dyn_code.unique' => 'El código ingresado ya existe en los registros.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ingresada ya existe en los registros.',

      'type.required' => 'El tipo es obligatorio.',
      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.max' => 'El tipo no debe exceder los 100 caracteres.',
    ];
  }
}
