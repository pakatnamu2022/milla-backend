<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneralMasterRequest extends StoreRequest
{
  protected function prepareForValidation(): void
  {
    $this->merge([
      'effective_from' => $this->input('effective_from') ?: null,
      'effective_to' => $this->input('effective_to') ?: null,
    ]);
  }

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
          ->where(function ($query) {
            $from = $this->input('effective_from');
            $from
              ? $query->where('effective_from', $from)
              : $query->whereNull('effective_from');
          })
          ->whereNull('deleted_at'),
      ],
      'description' => 'required|string|max:255',
      'type' => 'required|string|max:255',
      'value' => 'nullable|string|max:255',
      'effective_from' => 'nullable|date',
      'effective_to' => 'nullable|date|after_or_equal:effective_from',
      'status' => 'required|boolean',
    ];
  }
}
