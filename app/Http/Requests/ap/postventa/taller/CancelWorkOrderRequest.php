<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class CancelWorkOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'discard_reason_id' => ['required', 'integer', 'exists:ap_masters,id'],
      'discarded_note' => ['nullable', 'string', 'max:500'],
    ];
  }

  public function messages(): array
  {
    return [
      'discard_reason_id.required' => 'El motivo de cancelación es obligatorio.',
      'discard_reason_id.integer' => 'El motivo de cancelación debe ser un número válido.',
      'discard_reason_id.exists' => 'El motivo de cancelación seleccionado no es válido.',
      'discarded_note.string' => 'La nota debe ser un texto válido.',
      'discarded_note.max' => 'La nota no puede exceder los 500 caracteres.',
    ];
  }
}
