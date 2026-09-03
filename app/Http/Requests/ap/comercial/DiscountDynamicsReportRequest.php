<?php

namespace App\Http\Requests\ap\comercial;

use Illuminate\Foundation\Http\FormRequest;

class DiscountDynamicsReportRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
      'fecha_fin'    => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
      'sede_id'      => 'nullable|array',
      'sede_id.*'    => 'integer',
    ];
  }

  public function messages(): array
  {
    return [
      'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato Y-m-d (ejemplo: 2025-11-29)',
      'fecha_fin.date_format'    => 'La fecha de fin debe tener el formato Y-m-d (ejemplo: 2025-11-29)',
      'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
    ];
  }
}
