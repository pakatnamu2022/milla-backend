<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreAssignSalesSeriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'series' => [
        'required',
        'string',
        'max:20',
      ],
      'correlative_start' => [
        'required',
        'integer',
        'min:1',
      ],
      'type_receipt_id' => [
        'required',
        'exists:ap_commercial_masters,id',
      ],
      'type_operation_id' => [
        'required',
        'exists:ap_commercial_masters,id',
      ],
      'sede_id' => [
        'required',
        'exists:config_sede,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'series.required' => 'La serie es obligatoria.',
      'series.string' => 'La serie debe ser una cadena de texto.',
      'series.max' => 'La serie no debe exceder los 20 caracteres.',
      'series.unique' => 'La serie ya existe.',

      'correlative_start.required' => 'El correlativo inicial es obligatorio.',
      'correlative_start.integer' => 'El correlativo inicial debe ser un número entero.',
      'correlative_start.min' => 'El correlativo inicial debe ser al menos 1.',

      'type_receipt_id.required' => 'El tipo de comprobante es obligatorio.',
      'type_receipt_id.exists' => 'El tipo de comprobante seleccionado no es válido.',

      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
