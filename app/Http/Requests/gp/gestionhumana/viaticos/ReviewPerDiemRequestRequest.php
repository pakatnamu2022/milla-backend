<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;

class ReviewPerDiemRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'status' => ['required', 'in:' . PerDiemApproval::APPROVED . ',' . PerDiemApproval::REJECTED],
      'comments' => ['nullable', 'string', 'max:500'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'status.required' => 'El estado es requerido.',
      'status.in' => 'El estado debe ser aprobado o rechazado.',
      'comments.max' => 'Los comentarios no pueden tener más de 500 caracteres.',
    ];
  }
}