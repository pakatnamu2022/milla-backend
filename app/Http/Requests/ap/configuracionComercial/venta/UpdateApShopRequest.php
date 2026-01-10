<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApShopRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'sometimes',
        'string',
        'max:100',
        Rule::unique('ap_masters', 'description')
          ->where('type', $this->type)
          ->whereNull('deleted_at')
          ->ignore($this->route('shop')),
      ],
      'type' => [
        'sometimes',
        'string',
        'in:TIENDA'
      ],
      'sedes' => [
        'sometimes',
        'array',
        'min:1'
      ],
      'sedes.*' => [
        'integer',
        'exists:config_sede,id'
      ],
      'status' => [
        'sometimes',
        'boolean'
      ]
    ];
  }

  public function messages(): array
  {
    return [
      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 100 caracteres.',
      'description.unique' => 'Ya existe una tienda con esta descripción.',

      'type.required' => 'El tipo es obligatorio.',
      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.in' => 'El tipo debe ser "TIENDA".',

      'sedes.required' => 'Debe asociar al menos una sede a la tienda.',
      'sedes.array' => 'Las sedes deben ser un arreglo.',
      'sedes.min' => 'Debe asociar al menos una sede a la tienda.',

      'sedes.*.integer' => 'Cada ID de sede debe ser un número entero.',
      'sedes.*.exists' => 'Una o más sedes seleccionadas no existen.',
    ];
  }
}
