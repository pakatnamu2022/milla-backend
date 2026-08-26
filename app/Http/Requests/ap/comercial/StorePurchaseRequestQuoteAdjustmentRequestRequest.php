<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentItem;

class StorePurchaseRequestQuoteAdjustmentRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'purchase_request_quote_id' => ['required', 'integer', 'exists:purchase_request_quote,id'],
      'reason' => ['nullable', 'string', 'max:1000'],
      'items' => ['required', 'array', 'min:1'],
      'items.*.action' => ['required', 'string', 'in:' . implode(',', PurchaseRequestQuoteAdjustmentItem::getActions())],
      'items.*.discount_coupon_id' => ['nullable', 'integer', 'exists:discount_coupons,id'],
      'items.*.concept_code_id' => [
        'required_unless:items.*.action,' . PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE,
        'nullable', 'integer', 'exists:ap_masters,id',
      ],
      'items.*.type' => [
        'required_unless:items.*.action,' . PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE,
        'nullable', 'string', 'in:FIJO,PORCENTAJE',
      ],
      'items.*.value' => [
        'required_unless:items.*.action,' . PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE,
        'nullable', 'numeric', 'min:0',
      ],
      'items.*.has_retention' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'purchase_request_quote_id.required' => 'La solicitud/cotización es obligatoria.',
      'purchase_request_quote_id.exists' => 'La solicitud/cotización especificada no existe.',
      'items.required' => 'Debe agregar al menos una línea de cambio.',
      'items.min' => 'Debe agregar al menos una línea de cambio.',
      'items.*.action.required' => 'El tipo de cambio de cada línea es obligatorio.',
      'items.*.action.in' => 'El tipo de cambio de cada línea no es válido.',
      'items.*.concept_code_id.required_unless' => 'El concepto es obligatorio para agregar o editar un bono/descuento.',
      'items.*.type.required_unless' => 'El tipo (fijo/porcentaje) es obligatorio para agregar o editar un bono/descuento.',
      'items.*.value.required_unless' => 'El valor es obligatorio para agregar o editar un bono/descuento.',
    ];
  }
}
