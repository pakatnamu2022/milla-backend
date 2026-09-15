<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class StoreRelativeRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre_completo'  => 'required|string|max:250',
      'dni'              => 'required|string|max:20',
      'fecha_nacimiento' => 'nullable|date',
      'sexo'             => 'nullable|string|max:1',
      'parentesco'       => 'required|string|max:50',
    ];
  }
}
