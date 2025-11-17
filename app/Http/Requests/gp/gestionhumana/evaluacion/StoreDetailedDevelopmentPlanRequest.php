<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreDetailedDevelopmentPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => 'required|string|max:500',
      'boss_confirms' => 'nullable|boolean',
      'worker_confirms' => 'nullable|boolean',
      'boss_confirms_completion' => 'nullable|boolean',
      'worker_confirms_completion' => 'nullable|boolean',
      'worker_id' => 'required|integer|exists:rrhh_persona,id',
      'boss_id' => 'required|integer|exists:rrhh_persona,id',
      'gh_evaluation_id' => 'required|exists:gh_evaluation,id',
    ];
  }

  public function messages(): array
  {
    return [
      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',
      'boss_confirms.boolean' => 'El campo de confirmación del jefe debe ser verdadero o falso.',
      'worker_confirms.boolean' => 'El campo de confirmación del trabajador debe ser verdadero o falso.',
      'boss_confirms_completion.boolean' => 'El campo de confirmación de finalización del jefe debe ser verdadero o falso.',
      'worker_confirms_completion.boolean' => 'El campo de confirmación de finalización del trabajador debe ser verdadero o falso.',
      'worker_id.required' => 'El ID del trabajador es obligatorio.',
      'worker_id.integer' => 'El ID del trabajador debe ser un número entero.',
      'worker_id.exists' => 'El ID del trabajador no existe.',
      'boss_id.required' => 'El ID del jefe es obligatorio.',
      'boss_id.integer' => 'El ID del jefe debe ser un número entero.',
      'boss_id.exists' => 'El ID del jefe no existe.',
      'gh_evaluation_id.required' => 'El ID de la evaluación es obligatorio.',
      'gh_evaluation_id.exists' => 'El ID de la evaluación no existe.',
    ];
  }
}
