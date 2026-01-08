<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class DiscardPotentialBuyersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'comment' => 'required|string',
      'reason_discarding_id' => 'required|exists:ap_masters,id',
    ];
  }

  public function messages(): array
  {
    return [
      'comment.required' => 'El comentario es obligatorio.',
      'comment.string' => 'El comentario debe ser una cadena de texto.',
      'reason_discarding_id.required' => 'La razón de descarte es obligatoria.',
      'reason_discarding_id.exists' => 'La razón de descarte seleccionada no es válida.',
    ];
  }

  public function attributes()
  {
    return [
      'comment' => 'comentario',
      'reason_discarding_id' => 'razón de descarte',
    ];
  }
}
