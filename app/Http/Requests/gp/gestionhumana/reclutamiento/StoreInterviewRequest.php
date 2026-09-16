<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\Interview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInterviewRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'proceso_postulacion_id' => 'required|integer|exists:rrhh_proceso_postulacion,id',
      'persona_id'              => 'required|integer|exists:rrhh_persona,id',
      'fase'                    => ['required', 'integer', Rule::in([Interview::FASE_RRHH, Interview::FASE_JEFE])],
      'entrevistador_id'        => 'nullable|integer|exists:usr_users,id',
      'fecha_entrevista'        => 'nullable|date',
      'observaciones'           => 'nullable|string',
    ];
  }
}
