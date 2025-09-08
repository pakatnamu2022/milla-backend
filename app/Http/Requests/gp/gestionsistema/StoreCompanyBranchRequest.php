<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreCompanyBranchRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('company_branch', 'name')
          ->whereNull('deleted_at'),
      ],
      'abbreviation' => [
        'required',
        'string',
        'max:50',
        Rule::unique('company_branch', 'abbreviation')
          ->whereNull('deleted_at'),
      ],
      'address' => [
        'required',
        'string',
        'max:255',
      ],
      'company_id' => [
        'required',
        'integer',
        'exists:companies,id',
      ],
      'district_id' => [
        'required',
        'integer',
        'exists:district,id',
      ],
      'province_id' => [
        'required',
        'integer',
        'exists:province,id',
      ],
      'department_id' => [
        'required',
        'integer',
        'exists:department,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El campo nombre es obligatorio.',
      'name.string' => 'El campo nombre debe ser una cadena de texto.',
      'name.max' => 'El campo nombre no debe exceder los 255 caracteres.',
      'name.unique' => 'El nombre ingresado ya existe en los registros.',

      'abbreviation.required' => 'El campo abreviatura es obligatorio.',
      'abbreviation.string' => 'El campo abreviatura debe ser una cadena de texto.',
      'abbreviation.max' => 'El campo abreviatura no debe exceder los 50 caracteres.',
      'abbreviation.unique' => 'La abreviatura ingresada ya existe en los registros.',

      'address.required' => 'El campo dirección es obligatorio.',
      'address.string' => 'El campo dirección debe ser una cadena de texto.',
      'address.max' => 'El campo dirección no debe exceder los 255 caracteres.',

      'company_id.required' => 'El campo empresa es obligatorio.',
      'company_id.integer' => 'El campo empresa debe ser un número entero.',
      'company_id.exists' => 'La empresa seleccionada no existe.',

      'district_id.required' => 'El campo distrito es obligatorio.',
      'district_id.integer' => 'El campo distrito debe ser un número entero.',
      'district_id.exists' => 'El distrito seleccionado no existe.',

      'province_id.required' => 'El campo provincia es obligatorio.',
      'province_id.integer' => 'El campo provincia debe ser un número entero.',
      'province_id.exists' => 'La provincia seleccionada no existe.',

      'department_id.required' => 'El campo departamento es obligatorio.',
      'department_id.integer' => 'El campo departamento debe ser un número entero.',
      'department_id.exists' => 'El departamento seleccionado no existe.',
    ];
  }
}
