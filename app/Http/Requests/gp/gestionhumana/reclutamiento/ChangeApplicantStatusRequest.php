<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeApplicantStatusRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'tipo_trabajador_id' => ['required', 'integer', Rule::in(Applicant::STATUS_TYPES)],
      'motivo_status'      => 'nullable|string',
      'jefe_id'            => 'nullable|integer|exists:rrhh_persona,id',
    ];
  }
}
