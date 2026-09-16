<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterviewRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'entrevistador_id' => 'nullable|integer|exists:usr_users,id',
      'fecha_entrevista'  => 'nullable|date',
      'observaciones'     => 'nullable|string',
    ];
  }
}
