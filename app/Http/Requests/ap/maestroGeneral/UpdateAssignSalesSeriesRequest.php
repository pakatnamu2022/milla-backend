<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateAssignSalesSeriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'series' => [
        'nullable',
        'string',
        'max:50',
      ],
      'correlative_start' => [
        'nullable',
        'integer',
        'min:1',
      ],
      'type_receipt_id' => [
        'nullable',
        'exists:ap_commercial_masters,id',
      ],
      'type_operation_id' => [
        'nullable',
        'exists:ap_commercial_masters,id',
      ],
      'sede_id' => [
        'nullable',
        'exists:config_sede,id',
      ],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'series.string' => 'La serie debe ser una cadena de texto.',
      'series.max' => 'La serie no debe exceder los 50 caracteres.',
      'series.unique' => 'La serie ya existe.',

      'correlative_start.integer' => 'El correlativo inicial debe ser un número entero.',
      'correlative_start.min' => 'El correlativo inicial debe ser al menos 1.',

      'type_receipt_id.exists' => 'El tipo de comprobante seleccionado no es válido.',

      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
