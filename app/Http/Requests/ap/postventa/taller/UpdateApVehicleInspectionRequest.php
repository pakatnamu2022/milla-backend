<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateApVehicleInspectionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => 'sometimes|required|exists:ap_work_orders,id',
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
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es requerida',
      'work_order_id.exists' => 'La orden de trabajo no existe',
      'inspection_date.required' => 'La fecha de inspección es requerida',
      'inspection_date.date' => 'La fecha de inspección no es una fecha válida',
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
