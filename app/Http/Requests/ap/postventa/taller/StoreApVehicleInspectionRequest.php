<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\taller\ApWorkOrder;

class StoreApVehicleInspectionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_work_order_id' => 'required|exists:ap_work_orders,id',
      'inspection_date' => 'required|date',
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
      'customer_signature' => 'required|string|regex:/^data:image\/[a-z+]+;base64,/',
      'signer_type' => 'required|string|in:OWNER,CONTACT',
      'washed' => 'nullable|boolean',
      'photo_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
      'photo_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
      'photo_left' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
      'photo_right' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
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
      // Explicaciones de resultados
      'explanation_work_performed' => 'nullable|boolean',
      'price_explanation' => 'nullable|boolean',
      'confirm_additional_work' => 'nullable|boolean',
      'clarification_customer_concerns' => 'nullable|boolean',
      'exterior_cleaning' => 'nullable|boolean',
      'interior_cleaning' => 'nullable|boolean',
      'keeps_spare_parts' => 'nullable|boolean',
      'valuable_objects' => 'nullable|boolean',
      //Items de cortesía
      'courtesy_seat_cover' => 'nullable|boolean',
      'paper_floor' => 'nullable|boolean',
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
      'ap_work_order_id.required' => 'La orden de trabajo es requerida',
      'ap_work_order_id.exists' => 'La orden de trabajo no existe',
      'inspection_date.required' => 'La fecha de recepción es requerida',
      'inspection_date.date' => 'La fecha de recepción no es una fecha válida',
      'customer_signature.required' => 'La firma del cliente es requerido',
      'customer_signature.regex' => 'La firma del cliente debe ser una imagen en formato base64 válido',
      'mileage.numeric' => 'El kilometraje debe ser un número',
      'mileage.min' => 'El kilometraje no puede ser negativo',
      'fuel_level.string' => 'El nivel de combustible debe ser una cadena de texto',
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
   * Configure the validator instance.
   */
  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      $workOrderId = $this->input('ap_work_order_id');

      if ($workOrderId) {
        $existingWorkOrder = ApWorkOrder::find($workOrderId);

        if (!$existingWorkOrder) {
          $validator->errors()->add(
            'ap_work_order_id',
            'La orden de trabajo no existe o ya fue eliminada.'
          );
          return;
        }

        if ($existingWorkOrder->vehicleInspection) {
          $validator->errors()->add(
            'ap_work_order_id',
            'Esta orden de trabajo ya tiene una recepción vehicular registrada.'
          );
        }
      }
    });
  }
}
