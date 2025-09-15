<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateTaxClassTypesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('tax_class_types', 'dyn_code')
          ->where('type', $this->type)
          ->whereNull('deleted_at')
          ->ignore($this->route('taxClassType')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('tax_class_types', 'description')
          ->where('type', $this->type)
          ->whereNull('deleted_at')
          ->ignore($this->route('taxClassType')),
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
      'dyn_code.string' => 'El código debe ser una cadena de texto.',
      'dyn_code.max' => 'El código no debe exceder los 50 caracteres.',
      'dyn_code.unique' => 'El código ingresado ya existe en los registros.',

      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ingresada ya existe en los registros.',

      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.max' => 'El tipo no debe exceder los 100 caracteres.',

      'status.boolean' => 'El estado debe ser verdadero o falso.',
    ];
  }
}
