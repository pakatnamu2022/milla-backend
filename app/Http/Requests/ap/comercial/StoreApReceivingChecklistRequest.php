<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreApReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'receiving_ids' => 'required|array|min:1',
      'receiving_ids.*' => 'required|integer|exists:ap_delivery_receiving_checklist,id',
      'shipping_guide_id' => 'required|integer|exists:shipping_guides,id',
      'note' => 'nullable|string|max:250',
    ];
  }

  public function messages(): array
  {
    return [
      'receiving_ids.required' => 'Los IDs de recepción son obligatorios.',
      'receiving_ids.array' => 'Los IDs de recepción deben ser un arreglo.',
      'receiving_ids.min' => 'Debe proporcionar al menos un ID de recepción.',
      'receiving_ids.*.required' => 'Cada ID de recepción es obligatorio.',
      'receiving_ids.*.integer' => 'Cada ID de recepción debe ser un entero válido.',
      'receiving_ids.*.exists' => 'Uno o más IDs de recepción no existen.',
      'shipping_guide_id.required' => 'El ID de la guía de envío es obligatorio.',
      'shipping_guide_id.integer' => 'El ID de la guía de envío debe ser un entero válido.',
      'shipping_guide_id.exists' => 'El ID de la guía de envío no existe.',
      'note.string' => 'La nota debe ser una cadena de texto.',
      'note.max' => 'La nota no puede exceder los 250 caracteres.',
    ];
  }
}
