<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApDeliveryReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_delivery_receiving_checklist', 'descripcion')
          ->where('tipo', $this->tipo)
          ->whereNull('deleted_at')
          ->ignore($this->route('deliveryReceivingChecklist')),
      ],
      'tipo' => [
        'nullable',
        'string',
        'max:50',
      ],
      'categoria_id' => [
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
      'descripcion.unique' => 'Esta descripción ya existe',
      'descripcion.max' => 'El código no debe exceder 255 caracteres',
      'tipo.unique' => 'Esta tipo ya existe',
      'tipo.max' => 'El código no debe exceder 50 caracteres',
      'categoria_id.integer' => 'El campo categoria es obligatorio.',
      'categoria_id.exists' => 'El categoria seleccionado no existe',
    ];
  }
}
