<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\IndexRequest;
use Illuminate\Foundation\Http\FormRequest;

class IndexGeneralMasterRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'search' => 'sometimes|string',
      'type' => 'sometimes|string',
      'status' => 'sometimes|boolean',
      'sort' => 'sometimes|string',
      'page' => 'sometimes|integer',
      'per_page' => 'sometimes|integer',
    ];
  }
}
