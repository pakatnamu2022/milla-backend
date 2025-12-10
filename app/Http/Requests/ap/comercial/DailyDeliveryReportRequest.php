<?php

namespace App\Http\Requests\ap\comercial;

use Illuminate\Foundation\Http\FormRequest;

class DailyDeliveryReportRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'fecha_inicio' => 'required|date|date_format:Y-m-d',
      'fecha_fin' => 'required|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
    ];
  }

  public function messages(): array
  {
    return [
      'fecha_inicio.required' => 'La fecha de inicio es requerida',
      'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
      'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato Y-m-d (ejemplo: 2025-11-29)',
      'fecha_fin.required' => 'La fecha de fin es requerida',
      'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida',
      'fecha_fin.date_format' => 'La fecha de fin debe tener el formato Y-m-d (ejemplo: 2025-11-29)',
      'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
    ];
  }
}
