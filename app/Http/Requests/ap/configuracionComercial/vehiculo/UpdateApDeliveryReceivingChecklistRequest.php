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
          ->where('categoria_id', $this->categoria_id)
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
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.unique' => 'Este código ya existe',
      'codigo_dyn.required' => 'El código DYN es requerido',
      'codigo_dyn.unique' => 'Este código DYN ya existe',
      'nombre.unique' => 'Este nombre ya existe',
      'descripcion.required' => 'La descripción es requerida',
      'descripcion.unique' => 'Esta descripción ya existe',
      'grupo_id.exists' => 'El grupo seleccionado no existe',
      'logo.mimes' => 'El logo debe ser un archivo JPG, PNG o WebP',
      'logo.max' => 'El logo no debe superar los 2MB',
      'logo_min.mimes' => 'El logo min debe ser un archivo JPG, PNG o WebP',
      'logo_min.max' => 'El logo min no debe superar los 2MB',
    ];
  }
}
