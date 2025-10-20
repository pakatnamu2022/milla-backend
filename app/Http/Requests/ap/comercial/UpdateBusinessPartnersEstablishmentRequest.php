<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateBusinessPartnersEstablishmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => 'nullable|string|max:255',
      'status' => 'nullable|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'description.string' => 'La descripción debe ser texto',
      'description.max' => 'La descripción no puede exceder 255 caracteres',
      'status.boolean' => 'El estado debe ser verdadero o falso',
    ];
  }
}
