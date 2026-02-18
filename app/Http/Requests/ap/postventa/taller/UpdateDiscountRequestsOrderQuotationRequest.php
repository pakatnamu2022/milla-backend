<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateDiscountRequestsOrderQuotationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'requested_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
      'requested_discount_amount' => ['nullable', 'numeric', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'requested_discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'requested_discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'requested_discount_percentage.max' => 'El porcentaje de descuento no puede superar el 100%.',
      'requested_discount_amount.numeric' => 'El monto de descuento debe ser un número.',
      'requested_discount_amount.min' => 'El monto de descuento no puede ser negativo.',
    ];
  }
}