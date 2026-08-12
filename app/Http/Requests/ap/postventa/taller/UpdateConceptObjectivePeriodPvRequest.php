<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConceptObjectivePeriodPvRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'objective_sede_period_pv_id' => 'required|integer|exists:objective_sede_period_pv,id',
      'area_id' => 'required|integer|exists:ap_masters,id',
      'description' => 'required|string',
      'is_vehicular_crossing' => 'nullable|boolean',
      'status' => 'nullable|boolean',
      'sub_amount' => 'required|numeric|min:0',
      'order' => 'required|integer|min:0',
      'type_planning_ids' => 'nullable|array',
      'type_planning_ids.*' => 'integer|exists:type_planning_work_order,id',
      'advisors' => 'nullable|array',
      'advisors.*.id' => 'nullable|integer|exists:objective_advisors_period_pv,id',
      'advisors.*.worker_id' => 'required|integer|exists:rrhh_persona,id',
      'advisors.*.amount' => 'required|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'objective_sede_period_pv_id.required' => 'El campo objetivo sede período es obligatorio.',
      'objective_sede_period_pv_id.integer' => 'El objetivo sede período debe ser un número entero.',
      'objective_sede_period_pv_id.exists' => 'El objetivo sede período seleccionado no existe.',

      'area_id.required' => 'El campo área es obligatorio.',
      'area_id.integer' => 'El área debe ser un número entero.',
      'area_id.exists' => 'El área seleccionada no existe.',

      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'La descripción debe ser una cadena de texto.',

      'is_vehicular_crossing.boolean' => 'El campo de cruce vehicular debe ser un valor booleano.',

      'status.boolean' => 'El estado debe ser un valor booleano.',

      'sub_amount.required' => 'El campo submonto es obligatorio.',
      'sub_amount.numeric' => 'El submonto debe ser un valor numérico.',
      'sub_amount.min' => 'El submonto debe ser mayor o igual a 0.',

      'order.required' => 'El campo orden es obligatorio.',
      'order.integer' => 'El orden debe ser un número entero.',
      'order.min' => 'El orden debe ser mayor o igual a 0.',

      'type_planning_ids.array' => 'Los tipos de planificación deben ser un arreglo.',
      'type_planning_ids.*.integer' => 'Cada tipo de planificación debe ser un número entero.',
      'type_planning_ids.*.exists' => 'Uno o más tipos de planificación seleccionados no existen.',

      'advisors.array' => 'Los asesores deben ser un arreglo.',
      'advisors.*.id.integer' => 'El ID del asesor debe ser un número entero.',
      'advisors.*.id.exists' => 'Uno o más asesores seleccionados no existen.',
      'advisors.*.worker_id.required' => 'El ID del trabajador es obligatorio.',
      'advisors.*.worker_id.integer' => 'El ID del trabajador debe ser un número entero.',
      'advisors.*.worker_id.exists' => 'Uno o más trabajadores seleccionados no existen.',
      'advisors.*.amount.required' => 'El monto del asesor es obligatorio.',
      'advisors.*.amount.numeric' => 'El monto del asesor debe ser un valor numérico.',
      'advisors.*.amount.min' => 'El monto del asesor debe ser mayor o igual a 0.',
    ];
  }
}
