<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralMasterRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => 'sometimes|string|max:255',
      'description' => 'sometimes|string|max:255',
      'type' => 'sometimes|string|max:255',
      'value' => 'nullable|string|max:255',
      'status' => 'sometimes|boolean',
    ];
  }
}
