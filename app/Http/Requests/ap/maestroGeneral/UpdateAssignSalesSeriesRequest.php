<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateAssignSalesSeriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'series' => [
        'nullable',
        'string',
        'max:4',
        Rule::unique('assign_sales_series', 'series')
          ->whereNull('deleted_at')
          ->where('status', 1)
          ->ignore($this->route('assignSalesSeries')),
      ],
      'type' => ['nullable', 'in:PURCHASE,SALE,OTHERS'],
      'correlative_start' => ['nullable', 'integer', 'min:1'],
      'type_receipt_id' => ['nullable', 'exists:ap_masters,id'],
      'type_operation_id' => ['nullable', 'exists:ap_masters,id'],
      'sede_id' => ['nullable', 'exists:config_sede,id'],
      'status' => ['nullable', 'boolean'],
      'is_advance' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'series.string' => 'La serie debe ser una cadena de texto.',
      'series.max' => 'La serie no debe exceder los 4 caracteres.',
      'series.unique' => 'La serie ya está en uso.',

      'type.in' => 'El tipo seleccionado no es válido.',

      'correlative_start.integer' => 'El correlativo inicial debe ser un número entero.',
      'correlative_start.min' => 'El correlativo inicial debe ser al menos 1.',

      'type_receipt_id.exists' => 'El tipo de comprobante seleccionado no es válido.',

      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'sede_id.exists' => 'La sede seleccionada no es válida.',

      'status.boolean' => 'El estado debe ser verdadero o falso.',

      'is_advance.boolean' => 'El campo es anticipo debe ser verdadero o falso.',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {

      if ($this->type === 'PURCHASE') {
        $ignoreId = $this->route('assignSalesSeries');
        if ($ignoreId) {
          $ignoreId = is_object($ignoreId) ? $ignoreId->id : $ignoreId;
        }

        $query = DB::table('assign_sales_series')
          ->where('type', 'PURCHASE')
          ->where('type_receipt_id', $this->type_receipt_id)
          ->where('type_operation_id', $this->type_operation_id)
          ->where('sede_id', $this->sede_id)
          ->where('status', 1)
          ->whereNull('deleted_at');

        if (!empty($ignoreId)) {
          $query->where('id', '<>', $ignoreId);
        }

        $exists = $query->exists();

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
    ];
  }
}
