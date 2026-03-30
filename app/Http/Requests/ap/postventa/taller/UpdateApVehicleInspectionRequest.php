<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateApVehicleInspectionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_work_order_id' => 'sometimes|required|exists:ap_work_orders,id',
      'inspection_date' => 'sometimes|required|date',
      'mileage' => 'nullable|numeric|min:0',
      'fuel_level' => 'nullable|string|min:0|max:100',
      'oil_level' => 'nullable|string|max:50',
      'dirty_unit' => 'nullable|boolean',
      'unit_ok' => 'nullable|boolean',
      'title_deed' => 'nullable|boolean',
      'soat' => 'nullable|boolean',
      'moon_permits' => 'nullable|boolean',
      'service_card' => 'nullable|boolean',
      'owner_manual' => 'nullable|boolean',
      'key_ring' => 'nullable|boolean',
      'wheel_lock' => 'nullable|boolean',
      'safe_glasses' => 'nullable|boolean',
      'radio_mask' => 'nullable|boolean',
      'lighter' => 'nullable|boolean',
      'floors' => 'nullable|boolean',
      'seat_cover' => 'nullable|boolean',
      'quills' => 'nullable|boolean',
      'antenna' => 'nullable|boolean',
      'glasses_wheel' => 'nullable|boolean',
      'emblems' => 'nullable|boolean',
      'spare_tire' => 'nullable|boolean',
      'fluid_caps' => 'nullable|boolean',
      'tool_kit' => 'nullable|boolean',
      'jack_and_lever' => 'nullable|boolean',
      'general_observations' => 'nullable|string',
      'washed' => 'nullable|boolean',
      // Detalles de trabajo
      'oil_change' => 'nullable|boolean',
      'check_level_lights' => 'nullable|boolean',
      'general_lubrication' => 'nullable|boolean',
      'rotation_inspection_cleaning' => 'nullable|boolean',
      'insp_filter_basic_checks' => 'nullable|boolean',
      'tire_pressure_inflation_check' => 'nullable|boolean',
      'alignment_balancing' => 'nullable|boolean',
      'pad_replace_disc_resurface' => 'nullable|boolean',
      'other_work_details' => 'nullable|string',
      // Requerimiento del cliente
      'customer_requirement' => 'nullable|string',
      // Explicación de resultados
      'explanation_work_performed' => 'nullable|boolean',
      'price_explanation' => 'nullable|boolean',
      'confirm_additional_work' => 'nullable|boolean',
      'clarification_customer_concerns' => 'nullable|boolean',
      'exterior_cleaning' => 'nullable|boolean',
      'interior_cleaning' => 'nullable|boolean',
      'keeps_spare_parts' => 'nullable|boolean',
      'valuable_objects' => 'nullable|boolean',
      // Items de cortesía
      'courtesy_seat_cover' => 'nullable|boolean',
      'paper_floor' => 'nullable|boolean',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'ap_work_order_id.required' => 'La orden de trabajo es requerida',
      'ap_work_order_id.exists' => 'La orden de trabajo no existe',
      'inspection_date.required' => 'La fecha de recepción es requerida',
      'inspection_date.date' => 'La fecha de recepción no es una fecha válida',
      'mileage.numeric' => 'El kilometraje debe ser un número',
      'mileage.min' => 'El kilometraje no puede ser negativo',
      'fuel_level.min' => 'El nivel de combustible no puede ser negativo',
      'fuel_level.string' => 'El nivel de combustible debe ser una cadena de texto',
      'fuel_level.max' => 'El nivel de combustible no puede exceder 100 caracteres',
      'oil_level.string' => 'El nivel de aceite debe ser una cadena de texto',
      'oil_level.max' => 'El nivel de aceite no puede exceder 50 caracteres',
    ];
  }
}
