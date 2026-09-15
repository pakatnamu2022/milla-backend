<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSignerRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre'             => 'required|string|max:250',
      'file'               => 'nullable|file|extensions:cer,crt,pem|max:512',
      'key'                => 'nullable|file|extensions:key,pem|max:512',
      'firmaimg'           => 'nullable|file|mimes:png,jpg,jpeg|max:1024',
      'password'           => 'nullable|string|max:250',
      'fecha_vencimiento'  => 'nullable|date',
      'persona_id'         => 'nullable|integer|exists:rrhh_persona,id',
      'sucursal_id'        => 'nullable|integer|exists:config_sede,id',
    ];
  }
}
