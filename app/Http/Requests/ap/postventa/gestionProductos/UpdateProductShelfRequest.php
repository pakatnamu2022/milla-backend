<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class UpdateProductShelfRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'warehouse_id' => [
        'sometimes',
        'integer',
        'exists:warehouse,id',
      ],
      'label' => [
        'sometimes',
        'string',
        'max:255',
      ],
      'notes' => [
        'nullable',
        'string',
      ],
      'status' => [
        'sometimes',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'warehouse_id.integer' => 'El almacén debe ser un número entero.',
      'warehouse_id.exists' => 'El almacén seleccionado no existe.',

      'label.string' => 'La etiqueta debe ser una cadena de texto.',
      'label.max' => 'La etiqueta no debe exceder los 255 caracteres.',

      'notes.string' => 'Las notas deben ser una cadena de texto.',

      'status.boolean' => 'El estado debe ser verdadero o falso.',
    ];
  }
}
