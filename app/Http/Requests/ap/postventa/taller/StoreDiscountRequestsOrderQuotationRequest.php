<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;

class  StoreDiscountRequestsOrderQuotationRequest extends StoreRequest
{
  public function rules(): array
  {
    $type = $this->input('type');

    return [
      'type' => ['required', 'string', 'in:GLOBAL,PARTIAL'],
      'requested_discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
      'requested_discount_amount' => ['required', 'numeric', 'min:0'],
      'ap_order_quotation_id' => [
        $type === DiscountRequestsOrderQuotation::TYPE_GLOBAL ? 'required' : 'nullable',
        'integer',
        'exists:ap_order_quotations,id',
      ],
      'ap_order_quotation_detail_id' => [
        $type === DiscountRequestsOrderQuotation::TYPE_PARTIAL ? 'required' : 'nullable',
        'integer',
        'exists:ap_order_quotation_details,id',
      ],
      'item_type' => ['required', 'string', 'in:PRODUCT,LABOR'],
    ];
  }

  public function messages(): array
  {
    return [
      'type.required' => 'El tipo de descuento es obligatorio.',
      'type.in' => 'El tipo de descuento debe ser GLOBAL o PARTIAL.',
      'requested_discount_percentage.required' => 'El porcentaje de descuento es obligatorio.',
      'requested_discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'requested_discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'requested_discount_percentage.max' => 'El porcentaje de descuento no puede superar el 100%.',
      'requested_discount_amount.required' => 'El monto de descuento es obligatorio.',
      'requested_discount_amount.numeric' => 'El monto de descuento debe ser un número.',
      'requested_discount_amount.min' => 'El monto de descuento no puede ser negativo.',
      'ap_order_quotation_id.required' => 'La cotización es obligatoria para descuentos de tipo GLOBAL.',
      'ap_order_quotation_id.exists' => 'La cotización especificada no existe.',
      'ap_order_quotation_detail_id.required' => 'El detalle de cotización es obligatorio para descuentos de tipo PARTIAL.',
      'ap_order_quotation_detail_id.exists' => 'El detalle de cotización especificado no existe.',
      'item_type.required' => 'El tipo de ítem es obligatorio.',
      'item_type.in' => 'El tipo de ítem debe ser PRODUCT o LABOR.',
    ];
  }
}
