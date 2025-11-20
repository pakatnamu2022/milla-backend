<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateDetailedDevelopmentPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'title' => 'nullable|string|max:255',
      'description' => 'nullable|string|max:500',
      'comment' => 'nullable|string|max:1000',
      'start_date' => 'nullable|date',
      'end_date' => 'nullable|date',
      'worker_id' => 'nullable|integer|exists:rrhh_persona,id',
      'boss_id' => 'nullable|integer|exists:rrhh_persona,id',
      'tasks' => 'nullable|array',
      'tasks.*.id' => 'nullable|integer|exists:development_plan_task,id',
      'tasks.*.description' => 'required|string|max:500',
      'tasks.*.end_date' => 'required|date',
      'tasks.*.fulfilled' => 'nullable|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',
      'worker_id.integer' => 'El ID del trabajador debe ser un número entero.',
      'worker_id.exists' => 'El trabajador especificado no existe.',
      'boss_id.integer' => 'El ID del jefe debe ser un número entero.',
      'boss_id.exists' => 'El jefe especificado no existe.',
      'tasks.array' => 'Las tareas deben ser un arreglo.',
      'tasks.*.id.integer' => 'El ID de la tarea debe ser un número entero.',
      'tasks.*.id.exists' => 'La tarea especificada no existe.',
      'tasks.*.description.required' => 'La descripción de la tarea es obligatoria.',
      'tasks.*.description.string' => 'La descripción de la tarea debe ser una cadena de texto.',
      'tasks.*.description.max' => 'La descripción de la tarea no debe exceder los 500 caracteres.',
      'tasks.*.end_date.required' => 'La fecha de finalización de la tarea es obligatoria.',
      'tasks.*.end_date.date' => 'La fecha de finalización de la tarea debe ser una fecha válida.',
      'tasks.*.fulfilled.boolean' => 'El campo cumplido de la tarea debe ser verdadero o falso.',
    ];
  }
}
