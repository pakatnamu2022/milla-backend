<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractTypeRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'descripcion'      => 'required|string|max:200',
      'anios'            => 'nullable|integer|min:0',
      'dias_vacaciones'  => 'nullable|numeric|min:0',
    ];
  }
}
