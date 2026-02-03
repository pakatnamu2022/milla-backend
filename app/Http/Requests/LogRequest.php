<?php

namespace App\Http\Requests;

class LogRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'type' => 'nullable|string|in:DEBUG,INFO,NOTICE,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY',
      'environment' => 'nullable|string',
      'date_from' => 'nullable|date|date_format:Y-m-d',
      'date_to' => 'nullable|date|date_format:Y-m-d|after_or_equal:date_from',
      'search' => 'nullable|string',
      'page' => 'nullable|integer|min:1',
      'per_page' => 'nullable|integer|min:1|max:200',
    ];
  }

  public function messages(): array
  {
    return [
      'type.in' => 'El tipo de log debe ser uno de: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY',
      'date_from.date_format' => 'La fecha de inicio debe tener el formato Y-m-d (ejemplo: 2026-02-03)',
      'date_to.date_format' => 'La fecha de fin debe tener el formato Y-m-d (ejemplo: 2026-02-03)',
      'date_to.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
      'per_page.max' => 'El máximo de registros por página es 200',
    ];
  }
}
