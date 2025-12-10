<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\IndexRequest;
use Illuminate\Foundation\Http\FormRequest;

class IndexMyWarehouseRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'model_vn_id' => ['required', 'integer', 'exists:ap_models_vn,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
      'is_received' => ['sometimes', 'integer', 'in:0,1'],
    ];
  }

  public function messages(): array
  {
    return [
      'model_vn_id.required' => 'El modelo de vehículo es requerido',
      'model_vn_id.integer' => 'El modelo de vehículo debe ser un número entero',
      'model_vn_id.exists' => 'El modelo de vehículo seleccionado no existe',
      'sede_id.required' => 'La sede es requerida',
      'sede_id.integer' => 'La sede debe ser un número entero',
      'sede_id.exists' => 'La sede seleccionada no existe',
      'is_received.integer' => 'El campo is_received debe ser un número entero',
      'is_received.in' => 'El campo is_received debe ser 0 o 1',
    ];
  }
}
