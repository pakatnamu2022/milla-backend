<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreDeductibleWorkOrderRequest extends StoreRequest
{
  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'work_order_id' => [
        'required',
        'integer',
        Rule::exists('ap_work_orders', 'id')->whereNull('deleted_at'),
      ],
      'electronic_document_id' => [
        'required',
        'integer',
        Rule::exists('ap_billing_electronic_documents', 'id')->whereNull('deleted_at'),
      ],
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es obligatoria',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no existe',
      'electronic_document_id.required' => 'El comprobante electrónico es obligatorio',
      'electronic_document_id.exists' => 'El comprobante electrónico seleccionado no existe',
    ];
  }
}