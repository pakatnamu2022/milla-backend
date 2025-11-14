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
            'supplier_invoice_number' => 'nullable|string|max:100',
            'supplier_invoice_date' => 'nullable|date',
            'shipping_guide_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'received_by' => 'nullable|exists:users,id',

            // Details
            'details' => 'required|array|min:1',
            'details.*.purchase_order_item_id' => 'nullable|exists:ap_purchase_order_item,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity_received' => 'required|numeric|min:0.01',
            'details.*.quantity_accepted' => 'required|numeric|min:0',
            'details.*.quantity_rejected' => 'nullable|numeric|min:0',
            'details.*.reception_type' => 'required|in:ORDERED,BONUS,GIFT,SAMPLE',
            'details.*.unit_cost' => 'nullable|numeric|min:0',
            'details.*.is_charged' => 'nullable|boolean',
            'details.*.rejection_reason' => 'nullable|in:DAMAGED,DEFECTIVE,EXPIRED,WRONG_PRODUCT,WRONG_QUANTITY,POOR_QUALITY,OTHER',
            'details.*.rejection_notes' => 'nullable|string',
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
            'warehouse_id.required' => 'El almacén es obligatorio.',
            'warehouse_id.exists' => 'El almacén no existe.',
            'details.required' => 'Debe agregar al menos un producto.',
            'details.*.product_id.required' => 'El producto es obligatorio.',
            'details.*.product_id.exists' => 'El producto no existe.',
            'details.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'details.*.quantity_received.min' => 'La cantidad recibida debe ser mayor a 0.',
            'details.*.quantity_accepted.required' => 'La cantidad aceptada es obligatoria.',
            'details.*.reception_type.required' => 'El tipo de recepción es obligatorio.',
            'details.*.reception_type.in' => 'El tipo de recepción debe ser ORDERED, BONUS, GIFT o SAMPLE.',
        ];
    }
}