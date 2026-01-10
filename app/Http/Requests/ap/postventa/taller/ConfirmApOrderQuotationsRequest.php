<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class ConfirmApOrderQuotationsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'customer_signature' => ['required', 'string', 'regex:/^data:image\/[a-z+]+;base64,/'],
    ];
  }

  public function messages(): array
  {
    return [
      'customer_signature.required' => 'La firma del cliente es requerida.',
      'customer_signature.string' => 'La firma del cliente debe ser un texto válido.',
      'customer_signature.regex' => 'La firma del cliente debe ser una imagen en formato base64 válido.',
    ];
  }
}