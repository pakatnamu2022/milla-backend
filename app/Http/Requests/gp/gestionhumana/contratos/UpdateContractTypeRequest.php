<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractTypeRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'descripcion'     => 'sometimes|required|string|max:200',
      'anios'           => 'nullable|integer|min:0',
      'dias_vacaciones' => 'nullable|numeric|min:0',
    ];
  }
}
