<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractTemplateRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre'      => 'required|string|max:250',
      'descripcion' => 'nullable|string|max:250',
      'contenido'   => 'required|string',
    ];
  }
}
