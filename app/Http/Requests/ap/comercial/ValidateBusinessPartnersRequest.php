<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateBusinessPartnersRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'lead_id' => ['required', 'integer', Rule::exists('potential_buyers', 'id')->whereNull('deleted_at')],
    ];
  }
}
