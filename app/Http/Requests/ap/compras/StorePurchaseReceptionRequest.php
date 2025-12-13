<?php

namespace App\Http\Requests\ap\compras;

use App\Http\Requests\StoreRequest;

class StorePurchaseReceptionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'purchase_order_id' => 'required|exists:ap_purchase_order,id',
      'reception_date' => 'required|date',
      'warehouse_id' => 'required|exists:warehouse,id',
      'shipping_guide_number' => 'required|string|max:100',
      'freight_cost' => 'required|numeric|min:0',
      'carrier_id' => 'required|exists:business_partners,id',
      'notes' => 'nullable|string',

      // Details
      'details' => 'required|array|min:1',
      'details.*.purchase_order_item_id' => 'nullable|exists:ap_purchase_order_item,id',
      'details.*.product_id' => 'required|exists:products,id',
      'details.*.quantity_received' => 'required|numeric|min:0.01',
      'details.*.observed_quantity' => 'nullable|numeric|min:0',
      'details.*.reception_type' => 'required|in:ORDERED,BONUS,GIFT,SAMPLE',
      'details.*.reason_observation' => 'nullable|in:DAMAGED,DEFECTIVE,EXPIRED,WRONG_PRODUCT,WRONG_QUANTITY,POOR_QUALITY,OTHER',
      'details.*.observation_notes' => 'nullable|string',
      'details.*.bonus_reason' => 'nullable|string|max:255',
      'details.*.batch_number' => 'nullable|string|max:100',
      'details.*.expiration_date' => 'nullable|date|after:today',
      'details.*.notes' => 'nullable|string',
    ];
  }

  public function messages(): array
  {
    return [
      'purchase_order_id.required' => 'La orden de compra es obligatoria.',
      'purchase_order_id.exists' => 'La orden de compra no existe.',
      'reception_date.required' => 'La fecha de recepción es obligatoria.',
      'reception_date.date' => 'La fecha de recepción no es una fecha válida.',
      'shipping_guide_number.required' => 'El número de guía de remisión es obligatorio.',
      'shipping_guide_number.string' => 'El número de guía de remisión debe ser una cadena de texto.',
      'shipping_guide_number.max' => 'El número de guía de remisión no debe exceder los 100 caracteres.',
      'freight_cost.required' => 'El costo de flete es obligatorio.',
      'freight_cost.numeric' => 'El costo de flete debe ser un número.',
      'freight_cost.min' => 'El costo de flete no puede ser negativo.',
      'carrier_id.required' => 'El transportista es obligatorio.',
      'carrier_id.exists' => 'El transportista no existe.',
      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.exists' => 'El almacén no existe.',
      'details.required' => 'Debe agregar al menos un producto.',
      'details.*.product_id.required' => 'El producto es obligatorio.',
      'details.*.product_id.exists' => 'El producto no existe.',
      'details.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
      'details.*.quantity_received.min' => 'La cantidad recibida debe ser mayor a 0.',
      'details.*.reception_type.required' => 'El tipo de recepción es obligatorio.',
      'details.*.reception_type.in' => 'El tipo de recepción debe ser ORDERED, BONUS, GIFT o SAMPLE.',
    ];
  }
}
