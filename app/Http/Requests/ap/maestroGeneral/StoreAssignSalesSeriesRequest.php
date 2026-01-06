<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StoreAssignSalesSeriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'series' => [
        'required',
        'string',
        'max:4',
        Rule::unique('assign_sales_series', 'series')
          ->whereNull('deleted_at')
          ->where('status', 1),
      ],
      'correlative_start' => ['required', 'integer', 'min:1'],
      'type_receipt_id' => ['required', 'exists:ap_commercial_masters,id'],
      'type_operation_id' => ['required', 'exists:ap_commercial_masters,id'],
      'sede_id' => ['required', 'exists:config_sede,id'],
      'type' => ['required', 'in:PURCHASE,SALE,OTHERS'],
      'is_advance' => ['required', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'series.required' => 'La serie es obligatoria.',
      'series.string' => 'La serie debe ser una cadena de texto.',
      'series.max' => 'La serie no debe exceder los 4 caracteres.',
      'series.unique' => 'La serie ya está en uso.',

      'correlative_start.required' => 'El correlativo inicial es obligatorio.',
      'correlative_start.integer' => 'El correlativo inicial debe ser un número entero.',
      'correlative_start.min' => 'El correlativo inicial debe ser al menos 1.',

      'type_receipt_id.required' => 'El tipo de comprobante es obligatorio.',
      'type_receipt_id.exists' => 'El tipo de comprobante seleccionado no es válido.',

      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',

      'type.required' => 'El tipo es obligatorio.',
      'type.in' => 'El tipo seleccionado no es válido.',

      'is_advance.required' => 'El campo es anticipo es obligatorio.',
      'is_advance.boolean' => 'El campo es anticipo debe ser verdadero o falso.',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {

      if ($this->type === 'PURCHASE') {
        $exists = DB::table('assign_sales_series')
          ->where('type', 'PURCHASE')
          ->where('type_receipt_id', $this->type_receipt_id)
          ->where('type_operation_id', $this->type_operation_id)
          ->where('sede_id', $this->sede_id)
          ->where('status', 1)
          ->whereNull('deleted_at')
          ->exists();

        if ($exists) {
          $validator->errors()->add(
            'type',
            'Ya existe una serie de compra para esta sede.'
          );
        }
      }

    });
  }

  public function attributes(): array
  {
    return [
      'series' => 'serie',
      'correlative_start' => 'correlativo inicial',
      'type_receipt_id' => 'tipo de comprobante',
      'type_operation_id' => 'tipo de operación',
      'sede_id' => 'sede',
      'type' => 'tipo',
      'is_advance' => 'es anticipo',
    ];
  }
}
