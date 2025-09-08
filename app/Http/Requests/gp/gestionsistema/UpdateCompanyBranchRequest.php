<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyBranchRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('company_branch', 'name')
          ->whereNull('deleted_at')
          ->ignore($this->route('companyBranch')),
      ],
      'abbreviation' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('company_branch', 'abbreviation')
          ->whereNull('deleted_at')
          ->ignore($this->route('companyBranch')),
      ],
      'address' => [
        'nullable',
        'string',
        'max:255',
      ],
      'company_id' => [
        'nullable',
        'integer',
        'exists:companies,id',
      ],
      'district_id' => [
        'nullable',
        'integer',
        'exists:district,id',
      ],
      'province_id' => [
        'nullable',
        'integer',
        'exists:province,id',
      ],
      'department_id' => [
        'nullable',
        'integer',
        'exists:department,id',
      ],
      'status' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'name.string' => 'El campo nombre debe ser una cadena de texto.',
      'name.max' => 'El campo nombre no debe exceder los 255 caracteres.',
      'name.unique' => 'El campo nombre ya existe en los registros.',

      'abbreviation.string' => 'El campo abreviatura debe ser una cadena de texto.',
      'abbreviation.max' => 'El campo abreviatura no debe exceder los 50 caracteres.',
      'abbreviation.unique' => 'El campo abreviatura ya existe en los registros.',

      'address.string' => 'El campo dirección debe ser una cadena de texto.',
      'address.max' => 'El campo dirección no debe exceder los 255 caracteres.',

      'company_id.integer' => 'El campo empresa debe ser un número entero.',
      'company_id.exists' => 'La empresa seleccionada no existe.',

      'district_id.integer' => 'El campo distrito debe ser un número entero.',
      'district_id.exists' => 'El distrito seleccionado no existe.',

      'province_id.integer' => 'El campo provincia debe ser un número entero.',
      'province_id.exists' => 'La provincia seleccionada no existe.',

      'department_id.integer' => 'El campo departamento debe ser un número entero.',
      'department_id.exists' => 'El departamento seleccionado no existe.',
    ];
  }
}
