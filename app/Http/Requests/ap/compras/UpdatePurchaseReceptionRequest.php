<?php

namespace App\Http\Requests\ap\compras;


use App\Http\Requests\StoreRequest;

class UpdatePurchaseReceptionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'reception_date' => 'sometimes|required|date',
      'warehouse_id' => 'sometimes|required|exists:warehouse,id',
      'shipping_guide_number' => 'nullable|string|max:100',
      'freight_cost' => 'nullable|numeric|min:0',
      'carrier_id' => 'nullable|exists:business_partners,id',
      'notes' => 'nullable|string',
      'received_by' => 'nullable|exists:users,id',
    ];
  }

  public function messages(): array
  {
    return [
      'reception_date.required' => 'La fecha de recepción es obligatoria.',
      'reception_date.date' => 'La fecha de recepción no es una fecha válida.',
      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.exists' => 'El almacén no existe.',
    ];
  }
}
