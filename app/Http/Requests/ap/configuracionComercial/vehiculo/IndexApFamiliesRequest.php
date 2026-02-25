<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\IndexRequest;
use Illuminate\Foundation\Http\FormRequest;

class IndexApFamiliesRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'per_page' => 'integer|nullable|min:1',
      'search' => 'string|nullable|max:255',
      'brand_id' => 'integer|nullable|exists:ap_models_vn,id',
    ];
  }
}
