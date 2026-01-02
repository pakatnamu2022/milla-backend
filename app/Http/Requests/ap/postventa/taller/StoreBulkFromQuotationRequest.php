<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreBulkFromQuotationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'quotation_id' => 'required|exists:ap_order_quotations,id',
      'work_order_id' => 'required|exists:ap_work_orders,id',
      'warehouse_id' => 'required|exists:warehouse,id',
      'group_number' => 'required|integer',
      'quotation_detail_ids' => 'required|array|min:1',
      'quotation_detail_ids.*' => 'required|exists:ap_order_quotation_details,id',
    ];
  }

  public function messages(): array
  {
    return [
      'quotation_id.required' => 'El campo cotización es obligatorio.',
      'quotation_id.exists' => 'La cotización seleccionada no existe.',
      'work_order_id.required' => 'El campo orden de trabajo es obligatorio.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no existe.',
      'warehouse_id.required' => 'El campo almacén es obligatorio.',
      'warehouse_id.exists' => 'El almacén seleccionado no existe.',
      'group_number.required' => 'El campo número de grupo es obligatorio.',
      'group_number.integer' => 'El campo número de grupo debe ser un número entero.',
      'quotation_detail_ids.required' => 'Debe seleccionar al menos un producto.',
      'quotation_detail_ids.array' => 'Los productos seleccionados deben ser un arreglo.',
      'quotation_detail_ids.min' => 'Debe seleccionar al menos un producto.',
      'quotation_detail_ids.*.exists' => 'Uno o más productos seleccionados no existen.',
    ];
  }
}
