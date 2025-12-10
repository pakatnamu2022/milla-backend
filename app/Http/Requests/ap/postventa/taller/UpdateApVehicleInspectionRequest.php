<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateApVehicleInspectionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'id' => 'required|exists:ap_vehicle_inspection,id',
      'work_order_id' => 'sometimes|required|exists:ap_work_orders,id',
      'inspection_date' => 'sometimes|required|date',
      'mileage' => 'nullable|numeric|min:0',
      'fuel_level' => 'nullable|numeric|min:0|max:100',
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

      // Damages array
      'damages' => 'nullable|array',
      'damages.*.damage_type' => 'required_with:damages|string|max:100',
      'damages.*.x_coordinate' => 'nullable|numeric',
      'damages.*.y_coordinate' => 'nullable|numeric',
      'damages.*.description' => 'nullable|string',
      'damages.*.photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'id.required' => 'El ID es requerido',
      'id.exists' => 'La inspección vehicular no existe',
      'work_order_id.required' => 'La orden de trabajo es requerida',
      'work_order_id.exists' => 'La orden de trabajo no existe',
      'inspection_date.required' => 'La fecha de inspección es requerida',
      'inspection_date.date' => 'La fecha de inspección no es una fecha válida',
      'mileage.numeric' => 'El kilometraje debe ser un número',
      'mileage.min' => 'El kilometraje no puede ser negativo',
      'fuel_level.numeric' => 'El nivel de combustible debe ser un número',
      'fuel_level.min' => 'El nivel de combustible no puede ser menor a 0',
      'fuel_level.max' => 'El nivel de combustible no puede ser mayor a 100',
      'damages.array' => 'Los daños deben ser un arreglo',
      'damages.*.damage_type.required_with' => 'El tipo de daño es requerido',
      'damages.*.photo.image' => 'El archivo debe ser una imagen',
      'damages.*.photo.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif',
      'damages.*.photo.max' => 'La imagen no puede pesar más de 5MB',
    ];
  }

  /**
   * Prepare the data for validation.
   */
  protected function prepareForValidation(): void
  {
    // Add the ID from the route parameter if not already present
    if (!$this->has('id') && $this->route('id')) {
      $this->merge(['id' => $this->route('id')]);
    }
  }
}
