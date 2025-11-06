<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\IndexRequest;

class IndexWarehousesByCompanyRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'my' => ['required', 'boolean'],
      'is_received' => ['required', 'boolean'],
      'ap_class_article_id' => ['required', 'integer', 'exists:ap_class_article,id'],
      'empresa_id' => ['required', 'integer', 'exists:companies,id'],
    ];
  }
}
