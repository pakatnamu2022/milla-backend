<?php

namespace App\Http\Requests\gp\gestionhumana\permiso;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrabajadorPermisoRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'partner_id'  => ['required', 'integer', 'exists:rrhh_persona,id'],
      'fecha_inicio' => ['required', 'date'],
      'fecha_fin'   => ['required', 'date', 'after_or_equal:fecha_inicio'],
      'c_motivo'    => ['nullable', 'string', 'max:255'],
      'sin_goce'    => ['nullable', 'integer', 'in:0,1'],
      'sucursal_id' => ['nullable', 'integer'],
    ];
  }
}
