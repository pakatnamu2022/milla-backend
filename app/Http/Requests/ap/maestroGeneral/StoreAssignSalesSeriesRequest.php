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
        'max:20',
        Rule::unique('assign_sales_series', 'series')
          ->whereNull('deleted_at')
          ->where('status', 1),
      ],
      'correlative_start' => ['required', 'integer', 'min:1'],
      'type_receipt_id' => ['required', 'exists:ap_commercial_masters,id'],
      'type_operation_id' => ['required', 'exists:ap_commercial_masters,id'],
      'sede_id' => ['required', 'exists:config_sede,id'],
      'type' => ['required', 'in:PURCHASE,SALE'],
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
    ];
  }
}
