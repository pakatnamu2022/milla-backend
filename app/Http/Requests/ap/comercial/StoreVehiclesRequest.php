<?php

namespace App\Http\Requests\ap\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehiclesRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'vin' => 'required|string|max:17|min:17|unique:ap_vehicles,vin',
      'year' => 'required|integer|min:1900|max:' . (date('Y') + 2),
      'engine_number' => 'required|string|max:50|unique:ap_vehicles,engine_number',
      'ap_models_vn_id' => 'required|integer|exists:ap_models_vn,id',
      'vehicle_color_id' => 'required|integer|exists:ap_commercial_masters,id',
      'supplier_order_type_id' => 'sometimes|nullable|integer|exists:ap_commercial_masters,id',
      'engine_type_id' => 'required|integer|exists:ap_commercial_masters,id',
      'ap_vehicle_status_id' => 'sometimes|integer|exists:ap_vehicle_status,id',
      'sede_id' => 'required|integer|exists:sede,id',
      'warehouse_physical_id' => 'sometimes|nullable|integer|exists:warehouse,id',
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array
   */
  public function messages(): array
  {
    return [
      'vin.required' => 'El VIN es requerido',
      'vin.unique' => 'El VIN ya existe en el sistema',
      'vin.min' => 'El VIN debe tener exactamente 17 caracteres',
      'vin.max' => 'El VIN debe tener exactamente 17 caracteres',
      'year.required' => 'El año es requerido',
      'year.integer' => 'El año debe ser un número entero',
      'year.min' => 'El año debe ser mayor a 1900',
      'year.max' => 'El año no puede ser mayor a ' . (date('Y') + 2),
      'engine_number.required' => 'El número de motor es requerido',
      'engine_number.unique' => 'El número de motor ya existe en el sistema',
      'ap_models_vn_id.required' => 'El modelo es requerido',
      'ap_models_vn_id.exists' => 'El modelo seleccionado no existe',
      'vehicle_color_id.required' => 'El color es requerido',
      'vehicle_color_id.exists' => 'El color seleccionado no existe',
      'engine_type_id.required' => 'El tipo de motor es requerido',
      'engine_type_id.exists' => 'El tipo de motor seleccionado no existe',
      'sede_id.required' => 'La sede es requerida',
      'sede_id.exists' => 'La sede seleccionada no existe',
    ];
  }
}
