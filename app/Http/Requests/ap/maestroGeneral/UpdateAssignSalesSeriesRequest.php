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
        'max:50',
        Rule::unique('assign_sales_series', 'series')
          ->whereNull('deleted_at')
          ->where('status', 1)
          ->ignore($this->route('assignSalesSeries')),
      ],
      'type' => ['nullable', 'in:PURCHASE,SALE'],
      'correlative_start' => ['nullable', 'integer', 'min:1'],
      'type_receipt_id' => ['nullable', 'exists:ap_commercial_masters,id'],
      'type_operation_id' => ['nullable', 'exists:ap_commercial_masters,id'],
      'sede_id' => ['nullable', 'exists:config_sede,id'],
      'status' => ['nullable', 'boolean'],
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
