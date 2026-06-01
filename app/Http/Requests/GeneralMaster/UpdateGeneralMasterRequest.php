<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralMasterRequest extends StoreRequest
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
          ->where('type', $this->input('type'))
          ->whereNull('deleted_at')
          ->ignore($this->route('generalMaster')),
      ],
      'description' => 'sometimes|string|max:255',
      'type' => 'sometimes|string|max:255',
      'value' => 'nullable|string|max:255',
      'status' => 'sometimes|boolean',
    ];
  }
}
