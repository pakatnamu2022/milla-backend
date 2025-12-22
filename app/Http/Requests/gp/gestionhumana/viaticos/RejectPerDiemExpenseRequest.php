<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class RejectPerDiemExpenseRequest extends StoreRequest
{

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'rejection_reason' => 'required|string|max:1000',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'rejection_reason.required' => 'Debe proporcionar una razón para el rechazo',
      'rejection_reason.string' => 'La razón de rechazo debe ser texto',
      'rejection_reason.max' => 'La razón de rechazo no puede exceder 1000 caracteres',
    ];
  }
}
