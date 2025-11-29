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
      'date' => 'required|date|date_format:Y-m-d',
    ];
  }

  public function messages(): array
  {
    return [
      'date.required' => 'La fecha es requerida',
      'date.date' => 'La fecha debe ser una fecha válida',
      'date.date_format' => 'La fecha debe tener el formato Y-m-d (ejemplo: 2025-11-29)',
    ];
  }
}
