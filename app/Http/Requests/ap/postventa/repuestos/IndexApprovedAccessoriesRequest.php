<?php

namespace App\Http\Requests\ap\postventa\repuestos;

use App\Http\Requests\IndexRequest;

class IndexApprovedAccessoriesRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'body_type_id' => 'sometimes|integer|exists:ap_masters,id',
      'type_operation_id' => 'sometimes|integer',
      'status' => 'sometimes|in:0,1,true,false',
    ];
  }
}
