<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrCreateConceptObjectiveMasterPvRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'id' => 'nullable|integer|exists:concept_objective_master_pv,id',
      'area_id' => 'required|integer|exists:ap_masters,id',
      'description' => 'required|string',
      'is_vehicular_crossing' => 'nullable|boolean',
      'status' => 'nullable|boolean',
      'order' => 'nullable|integer',
      'type_planning_ids' => 'nullable|array',
      'type_planning_ids.*' => 'integer|exists:type_planning_work_order,id',
    ];
  }

  public function messages(): array
  {
    return [
      'id.integer' => 'El ID debe ser un número entero.',
      'id.exists' => 'El concepto objetivo especificado no existe.',

      'area_id.required' => 'El campo área es obligatorio.',
      'area_id.integer' => 'El área debe ser un número entero.',
      'area_id.exists' => 'El área seleccionada no existe.',

      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'La descripción debe ser una cadena de texto.',

      'is_vehicular_crossing.boolean' => 'El campo de cruce vehicular debe ser un valor booleano.',

      'status.boolean' => 'El estado debe ser un valor booleano.',

      'order.integer' => 'El orden debe ser un número entero.',

      'type_planning_ids.array' => 'Los tipos de planificación deben ser un arreglo.',
      'type_planning_ids.*.integer' => 'Cada tipo de planificación debe ser un número entero.',
      'type_planning_ids.*.exists' => 'Uno o más tipos de planificación seleccionados no existen.',
    ];
  }
}
