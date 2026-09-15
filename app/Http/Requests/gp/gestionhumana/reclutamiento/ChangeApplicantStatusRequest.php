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
    $isSelected = (int) $this->input('tipo_trabajador_id') === Applicant::TIPO_SELECCIONADO;

    return [
      'tipo_trabajador_id' => ['required', 'integer', Rule::in(Applicant::STATUS_TYPES)],
      'motivo_status'      => 'nullable|string',
      'jefe_id'            => 'nullable|integer|exists:rrhh_persona,id',
      'fecha_inicio'       => [$isSelected ? 'required' : 'nullable', 'date'],
      'presupuesto'        => [$isSelected ? 'required' : 'nullable', 'numeric'],
    ];
  }
}
