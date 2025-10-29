<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApDeliveryReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_delivery_receiving_checklist', 'description')
          ->where('type', $this->type)
          ->whereNull('deleted_at'),
      ],
      'type' => [
        'required',
        'string',
        'max:50',
      ],
      'category_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'has_quantity' => [
        'sometimes',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'El campo descripción debe ser una cadena de texto.',
      'description.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'El campo descripción ya existe.',

      'type.required' => 'El campo tipo es obligatorio.',
      'type.string' => 'El campo tipo debe ser una cadena de texto.',
      'type.max' => 'El campo tipo no debe exceder los 255 caracteres.',

      'category_id.required' => 'El campo categoria es obligatorio.',
      'category_id.integer' => 'El campo categoria es obligatorio.',
      'category_id.exists' => 'El grupo seleccionado no existe',
    ];
  }
}
