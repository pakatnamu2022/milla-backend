<?php

namespace App\Http\Requests;


use Sabberworm\CSS\Rule\Rule;

class UpdateCompanyRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'email' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('companies', 'email')
          ->whereNull('deleted_at')
          ->ignore($this->route('company')),
      ],
      'website' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('companies', 'website')
          ->whereNull('deleted_at')
          ->ignore($this->route('company')),
      ],
      'phone' => [
        'nullable',
        'string',
        'max:20',
      ],
      'address' => [
        'nullable',
        'string',
        'max:200',
      ],
      'detraction_amount' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'billing_detraction_type_id' => [
        'nullable',
        'integer',
        'exists:sunat_concepts,id',
      ],
    ];
  }
}
