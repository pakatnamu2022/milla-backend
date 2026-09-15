<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractTemplateRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre'      => 'sometimes|required|string|max:250',
      'descripcion' => 'nullable|string|max:250',
      'contenido'   => 'sometimes|required|string',
    ];
  }
}
