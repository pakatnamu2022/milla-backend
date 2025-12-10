<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateApOrderQuotationsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => ['sometimes', 'required', 'integer', 'exists:ap_work_orders,id'],
      'quotation_number' => ['sometimes', 'required', 'string', 'max:50'],
      'subtotal' => ['sometimes', 'required', 'numeric', 'min:0'],
      'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
      'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'total_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
      'validity_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
      'quotation_date' => ['sometimes', 'required', 'date'],
      'expiration_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:quotation_date'],
      'observations' => ['sometimes', 'nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es obligatoria.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no es válida.',
      'quotation_number.required' => 'El número de cotización es obligatorio.',
      'subtotal.required' => 'El subtotal es obligatorio.',
      'total_amount.required' => 'El total es obligatorio.',
      'quotation_date.required' => 'La fecha de cotización es obligatoria.',
      'expiration_date.after_or_equal' => 'La fecha de expiración debe ser posterior o igual a la fecha de cotización.',
    ];
  }
}
