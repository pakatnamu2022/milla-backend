<?php

namespace App\Http\Requests\ap\compras;

use Illuminate\Foundation\Http\FormRequest;

class UnmarkDefectiveProductRequest extends FormRequest
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
      'reception_detail_id' => 'required|integer|exists:purchase_reception_details,id',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'reception_detail_id.required' => 'El ID del detalle de recepción es requerido',
      'reception_detail_id.exists' => 'El detalle de recepción no existe',
    ];
  }
}
