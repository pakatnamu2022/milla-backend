<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApDeliveryReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_delivery_receiving_checklist', 'description')
          ->where('type', $this->type)
          ->whereNull('deleted_at')
          ->ignore($this->route('deliveryReceivingChecklist')),
      ],
      'type' => [
        'nullable',
        'string',
        'max:50',
      ],
      'category_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'description.unique' => 'Esta descripción ya existe',
      'description.max' => 'El código no debe exceder 255 caracteres',

      'type.string' => 'El campo tipo debe ser una cadena de texto',
      'type.max' => 'El tipo no debe exceder 50 caracteres',

      'category_id.integer' => 'El campo categoria es obligatorio.',
      'category_id.exists' => 'El categoria seleccionado no existe',
    ];
  }
}
