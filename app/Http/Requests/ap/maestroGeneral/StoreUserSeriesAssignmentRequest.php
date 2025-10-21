<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;

class StoreUserSeriesAssignmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => 'required|integer|exists:usr_users,id',
      'vouchers' => 'required|array',
      'vouchers.*' => 'integer|exists:assign_sales_series,id'
    ];
  }

  public function messages(): array
  {
    return [
      'worker_id.required' => 'El campo worker_id es obligatorio.',
      'worker_id.integer' => 'El campo worker_id debe ser un número entero.',
      'worker_id.exists' => 'El worker_id proporcionado no existe.',

      'vouchers.required' => 'El campo vouchers es obligatorio.',
      'vouchers.array' => 'El campo vouchers debe ser un arreglo.',
      'vouchers.*.integer' => 'Cada valor en vouchers debe ser un número entero.',
      'vouchers.*.exists' => 'Uno o más vouchers proporcionados no existen.'
    ];
  }
}
