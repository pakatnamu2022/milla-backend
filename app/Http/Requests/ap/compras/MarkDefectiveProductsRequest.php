<?php

namespace App\Http\Requests\ap\compras;

use Illuminate\Foundation\Http\FormRequest;

class MarkDefectiveProductsRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'items' => 'required|array|min:1',
      'items.*.reception_detail_id' => 'required|integer|exists:purchase_reception_details,id',
      'items.*.defective_quantity' => 'required|numeric|min:0.01',
      'items.*.reason_observation' => 'required|string|max:500',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'items.required' => 'Debe proporcionar al menos un producto',
      'items.*.reception_detail_id.required' => 'El ID del detalle de recepción es requerido',
      'items.*.reception_detail_id.exists' => 'El detalle de recepción no existe',
      'items.*.defective_quantity.required' => 'La cantidad defectuosa es requerida',
      'items.*.defective_quantity.min' => 'La cantidad defectuosa debe ser mayor a 0',
      'items.*.reason_observation.required' => 'La razón de la observación es requerida',
      'items.*.reason_observation.max' => 'La razón de la observación no debe exceder 500 caracteres',
    ];
  }
}
