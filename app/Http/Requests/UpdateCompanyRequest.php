<?php

namespace App\Http\Requests;


use Illuminate\Validation\Rule;

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
          ->ignore($this->route('company')),
      ],
      'website' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('companies', 'website')
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
    ];
  }

  public function messages(): array
  {
    return [
      'email.unique' => 'El correo electrónico ya está en uso por otra empresa.',
      'website.unique' => 'El sitio web ya está en uso por otra empresa.',
    ];
  }
}
