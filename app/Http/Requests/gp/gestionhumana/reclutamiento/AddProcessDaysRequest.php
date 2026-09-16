<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud de tiempo adicional (previa coordinación con jefatura), a uno o
 * varios procesos a la vez.
 */
class AddProcessDaysRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'proceso_postulacion_ids'   => 'required|array|min:1',
      'proceso_postulacion_ids.*' => 'integer|exists:rrhh_proceso_postulacion,id',
      'dias'                       => 'required|integer|min:1|max:60',
      'motivo'                     => 'required|string|max:250',
    ];
  }
}
