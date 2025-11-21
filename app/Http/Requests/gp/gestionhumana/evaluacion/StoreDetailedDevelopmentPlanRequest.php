<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreDetailedDevelopmentPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'title' => 'required|string|max:255',
      'description' => 'required|string|max:500',
      'start_date' => 'required|date',
      'end_date' => 'required|date',
      'worker_id' => 'required|integer|exists:rrhh_persona,id',
      'boss_id' => 'required|integer|exists:rrhh_persona,id',
      'tasks' => 'nullable|array',
      'tasks.*.description' => 'required|string|max:500',
      'tasks.*.end_date' => 'required|date',
      'tasks.*.fulfilled' => 'nullable|boolean',
      'objectives_competences' => 'nullable|array',
      'objectives_competences.*.objective_detail_id' => 'nullable|integer|exists:gh_evaluation_person_cycle_detail,id',
      'objectives_competences.*.competence_detail_id' => 'nullable|integer|exists:gh_evaluation_person_competence_detail,id',
    ];
  }

  public function messages(): array
  {
    return [
      'title.required' => 'El título es obligatorio.',
      'title.string' => 'El título debe ser una cadena de texto.',
      'title.max' => 'El título no debe exceder los 255 caracteres.',
      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',
      'start_date' => 'La fecha de inicio es obligatoria.',
      'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
      'end_date.required' => 'La fecha de finalización es obligatoria.',
      'end_date.date' => 'La fecha de finalización debe ser una fecha válida.',
      'worker_id.required' => 'El ID del trabajador es obligatorio.',
      'worker_id.integer' => 'El ID del trabajador debe ser un número entero.',
      'worker_id.exists' => 'El ID del trabajador no existe.',
      'boss_id.required' => 'El ID del jefe es obligatorio.',
      'boss_id.integer' => 'El ID del jefe debe ser un número entero.',
      'boss_id.exists' => 'El ID del jefe no existe.',
      'tasks.array' => 'Las tareas deben ser un arreglo.',
      'tasks.*.description.required' => 'La descripción de la tarea es obligatoria.',
      'tasks.*.description.string' => 'La descripción de la tarea debe ser una cadena de texto.',
      'tasks.*.description.max' => 'La descripción de la tarea no debe exceder los 500 caracteres.',
      'tasks.*.end_date.required' => 'La fecha de finalización de la tarea es obligatoria.',
      'tasks.*.end_date.date' => 'La fecha de finalización de la tarea debe ser una fecha válida.',
      'tasks.*.fulfilled.boolean' => 'El campo cumplido de la tarea debe ser verdadero o falso.',
    ];
  }
}
