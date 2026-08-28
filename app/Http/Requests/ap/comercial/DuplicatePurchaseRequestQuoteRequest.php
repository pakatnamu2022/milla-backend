<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class DuplicatePurchaseRequestQuoteRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'copies' => ['required', 'integer', 'min:1', 'max:20'],
    ];
  }

  public function attributes(): array
  {
    return [
      'copies' => 'Cantidad de copias',
    ];
  }

  public function messages(): array
  {
    return [
      'copies.max' => 'Solo puedes generar hasta 20 copias por vez.',
    ];
  }
}
