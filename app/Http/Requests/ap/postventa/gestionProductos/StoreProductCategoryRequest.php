<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class StoreProductCategoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
      'status' => 'required|boolean',
      'type_id' => 'required|exists:ap_post_venta_masters,id',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El nombre es obligatorio.',
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 255 caracteres.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 1000 caracteres.',
      'status.required' => 'El estado es obligatorio.',
      'status.boolean' => 'El estado debe ser verdadero o falso.',
      'type_id.required' => 'El tipo es obligatorio.',
      'type_id.exists' => 'El tipo seleccionado no es válido.',
    ];
  }
}
