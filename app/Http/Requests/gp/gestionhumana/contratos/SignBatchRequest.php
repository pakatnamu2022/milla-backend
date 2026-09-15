<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class SignBatchRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'lote'        => 'required|string|max:150',
      'firmante_id' => 'required|integer|exists:rrhh_firmante,id',
    ];
  }
}
