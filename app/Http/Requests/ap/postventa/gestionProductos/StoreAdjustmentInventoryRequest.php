<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class StoreAdjustmentInventoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'movement_type' => 'required|in:ADJUSTMENT_IN,ADJUSTMENT_OUT',
      'warehouse_id' => 'required|exists:warehouse,id',
      'reason_in_out_id' => 'nullable|exists:ap_post_venta_masters,id',
      'movement_date' => 'nullable|date',
      'notes' => 'nullable|string|max:1000',
      'details' => 'required|array|min:1',
      'details.*.product_id' => 'required|exists:products,id',
      'details.*.quantity' => 'required|numeric|min:0.01',
      'details.*.unit_cost' => 'nullable|numeric|min:0',
      'details.*.batch_number' => 'nullable|string|max:100',
      'details.*.expiration_date' => 'nullable|date',
      'details.*.notes' => 'nullable|string|max:500',
    ];
  }

  public function messages(): array
  {
    return [
      'movement_type.required' => 'El tipo de movimiento es obligatorio.',
      'movement_type.in' => 'El tipo de movimiento debe ser ADJUSTMENT_IN o ADJUSTMENT_OUT.',
      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.exists' => 'El almacén no existe.',
      'details.required' => 'Debe agregar al menos un producto.',
      'details.*.product_id.required' => 'El producto es obligatorio.',
      'details.*.product_id.exists' => 'El producto no existe.',
      'details.*.quantity.required' => 'La cantidad es obligatoria.',
      'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
    ];
  }
}
