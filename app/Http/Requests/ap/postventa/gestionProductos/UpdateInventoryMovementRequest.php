<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class UpdateInventoryMovementRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'movement_date' => 'required|date',
      'notes' => 'nullable|string|max:1000',
    ];
  }

  public function messages(): array
  {
    return [
      'movement_date.required' => 'La fecha de movimiento es requerida',
      'movement_date.date' => 'La fecha de movimiento debe ser una fecha válida',
      'notes.string' => 'Las notas deben ser texto',
      'notes.max' => 'Las notas no pueden exceder 1000 caracteres',
    ];
  }
}
