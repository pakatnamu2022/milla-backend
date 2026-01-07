<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneralMasterRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:255',
        Rule::unique('general_masters', 'code')
          ->where('status', 1)
          ->whereNull('deleted_at'),
      ],
      'description' => 'required|string|max:255',
      'type' => 'required|string|max:255',
      'value' => 'nullable|string|max:255',
      'status' => 'required|boolean',
    ];
  }
}
