<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequestsWorkOrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'ap_work_order_id' => 'required|integer|exists:ap_work_orders,id',
      'type' => 'required|in:GLOBAL,PARTIAL',
      'part_labour_id' => 'nullable|integer|required_if:type,PARTIAL',
      'part_labour_model' => 'required|string|in:PART,LABOUR',
      'requested_discount_percentage' => 'required|numeric|min:0|max:100',
      'requested_discount_amount' => 'required|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'ap_work_order_id.required' => 'La orden de trabajo es requerida.',
      'ap_work_order_id.exists' => 'La orden de trabajo no existe.',
      'type.required' => 'El tipo de descuento es requerido.',
      'type.in' => 'El tipo de descuento debe ser GLOBAL o PARTIAL.',
      'part_labour_id.required_if' => 'El ítem (parte o labor) es requerido para descuentos parciales.',
      'part_labour_model.required_if' => 'El modelo del ítem es requerido para descuentos parciales.',
      'part_labour_model.in' => 'El modelo del ítem debe ser PART o LABOUR.',
      'requested_discount_percentage.required' => 'El porcentaje de descuento es requerido.',
      'requested_discount_percentage.min' => 'El porcentaje de descuento debe ser mayor o igual a 0.',
      'requested_discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
      'requested_discount_amount.required' => 'El monto de descuento es requerido.',
      'requested_discount_amount.min' => 'El monto de descuento debe ser mayor o igual a 0.',
    ];
  }
}
