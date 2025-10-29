<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateApReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'items_receiving' => 'nullable|array',
      'items_receiving.*' => 'nullable|integer',
      'shipping_guide_id' => 'required|integer|exists:shipping_guides,id',
      'note' => 'nullable|string|max:250',
    ];
  }

  public function messages(): array
  {
    return [
      'items_receiving.array' => 'Los items de recepción deben ser un objeto.',
      'items_receiving.*.integer' => 'Cada cantidad debe ser un número entero.',
      'shipping_guide_id.required' => 'El ID de la guía de envío es obligatorio.',
      'shipping_guide_id.integer' => 'El ID de la guía de envío debe ser un entero válido.',
      'shipping_guide_id.exists' => 'El ID de la guía de envío no existe.',
      'note.string' => 'La nota debe ser una cadena de texto.',
      'note.max' => 'La nota no puede exceder los 250 caracteres.',
    ];
  }
}
