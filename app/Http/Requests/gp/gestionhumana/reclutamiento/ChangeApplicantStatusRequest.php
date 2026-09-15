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
    $tipo = (int) $this->input('tipo_trabajador_id');
    $isSelected = $tipo === Applicant::TIPO_SELECCIONADO;
    $requiresMotivo = in_array($tipo, [Applicant::TIPO_RECHAZADO, Applicant::TIPO_FUERA_CUPO, Applicant::TIPO_LISTA_NEGRA], true);

    return [
      'tipo_trabajador_id' => ['required', 'integer', Rule::in(Applicant::STATUS_TYPES)],
      'motivo_status'      => [$requiresMotivo ? 'required' : 'nullable', 'string', 'max:500'],
      'jefe_id'            => 'nullable|integer|exists:rrhh_persona,id',
      'fecha_inicio'       => [$isSelected ? 'required' : 'nullable', 'date'],
      'presupuesto'        => [$isSelected ? 'required' : 'nullable', 'numeric'],
    ];
  }

  public function messages(): array
  {
    return [
      'motivo_status.required' => 'El motivo es obligatorio para rechazar, marcar fuera de cupo o lista negra.',
    ];
  }
}
