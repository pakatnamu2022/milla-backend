<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSelectedWorkerProfileRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'jefe_id'                       => 'nullable|integer|exists:rrhh_persona,id',
      'supervisor_id'                 => 'nullable|integer|exists:rrhh_persona,id',
      'sueldo'                        => 'nullable|numeric',
      'cuenta_interbancaria_cts'      => 'nullable|string|max:50',
      'cuenta_interbancaria_haberes'  => 'nullable|string|max:50',
      'cta_haberes'                   => 'nullable|string|max:50',
      'entidad_haberes'               => 'nullable|string|max:100',
      'cta_cts'                       => 'nullable|string|max:50',
      'entidad_cts'                   => 'nullable|string|max:100',
      'vidaley'                       => 'nullable|string|max:100',
      'estado_sctr'                   => 'nullable|string|max:100',
      'entidad_sctr'                  => 'nullable|string|max:100',
      'essaludvida'                   => 'nullable|string|max:100',
      'escolaridad'                   => 'nullable|string|max:100',
      'monto_escolaridad'             => 'nullable|numeric',
      'asignacion'                    => 'nullable|numeric',
      'sis_pensiones_id'              => 'nullable|integer',
      'cuspp'                         => 'nullable|string|max:50',
      'fecha_ingreso_afp_snp'         => 'nullable|date',
    ];
  }
}
