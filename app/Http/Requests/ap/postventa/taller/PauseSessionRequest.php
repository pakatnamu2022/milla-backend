<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class PauseSessionRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'pause_reason' => [
        'nullable',
        'string',
        'max:500',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'pause_reason.max' => 'La razón de pausa no debe exceder 500 caracteres.',
    ];
  }
}