<?php

namespace App\Http\Requests;

class StoreEquipmentTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'nullable|string|max:255',
    ];
  }

  public function attributes(): array
  {
    return [
      'name' => 'nombre',
    ];
  }
}
