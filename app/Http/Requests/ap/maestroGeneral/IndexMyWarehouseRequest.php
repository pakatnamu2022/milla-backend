<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\IndexRequest;
use Illuminate\Foundation\Http\FormRequest;

class IndexMyWarehouseRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'model_vn_id' => ['required', 'integer', 'exists:ap_models_vn,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
    ];
  }
}
