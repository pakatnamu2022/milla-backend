<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessStageMessageRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'asunto'    => 'required|string|max:200',
      'contenido' => 'required|string',
      'activo'    => 'nullable|boolean',
    ];
  }
}
