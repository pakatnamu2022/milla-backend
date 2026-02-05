<?php

namespace App\Http\Requests;

class LogRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'day' => 'required|date|date_format:Y-m-d',
      'type' => 'nullable|string|in:DEBUG,INFO,NOTICE,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY',
      'environment' => 'nullable|string',
      'search' => 'nullable|string',
      'page' => 'nullable|integer|min:1',
      'per_page' => 'nullable|integer|min:1|max:200',
    ];
  }

  public function messages(): array
  {
    return [
      'day.required' => 'El campo día es obligatorio',
      'day.date_format' => 'El día debe tener el formato Y-m-d (ejemplo: 2026-02-03)',
      'type.in' => 'El tipo de log debe ser uno de: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY',
      'per_page.max' => 'El máximo de registros por página es 200',
    ];
  }
}
