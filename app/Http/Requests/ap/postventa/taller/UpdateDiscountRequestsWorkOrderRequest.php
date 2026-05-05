<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequestsWorkOrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'requested_discount_percentage' => 'sometimes|numeric|min:0|max:100',
      'requested_discount_amount' => 'sometimes|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'requested_discount_percentage.min' => 'El porcentaje de descuento debe ser mayor o igual a 0.',
      'requested_discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
      'requested_discount_amount.min' => 'El monto de descuento debe ser mayor o igual a 0.',
    ];
  }
}