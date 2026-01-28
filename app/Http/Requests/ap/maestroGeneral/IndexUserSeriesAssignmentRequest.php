<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\IndexRequest;

class IndexUserSeriesAssignmentRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'type_receipt_id' => 'nullable|integer|exists:ap_masters,id',
      'type_operation_id' => 'nullable|integer|exists:ap_masters,id',
      'sede_id' => 'nullable|integer|exists:config_sedes,id',
    ];
  }
}
