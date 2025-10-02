<?php

namespace App\Http\Requests\gp\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'abreviatura' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('config_sede', 'abreviatura')
          ->whereNull('deleted_at')
          ->ignore($this->route('sede')),
      ],
      'dyn_code' => [
        'nullable',
        'string',
        'max:100',
      ],
      'direccion' => [
        'nullable',
        'string',
        'max:255',
      ],
      'empresa_id' => [
        'nullable',
        'integer',
        'exists:companies,id',
      ],
      'department_id' => [
        'nullable',
        'integer',
        'exists:department,id',
      ],
      'province_id' => [
        'nullable',
        'integer',
        'exists:province,id',
      ],
      'district_id' => [
        'nullable',
        'integer',
        'exists:district,id',
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
      'abreviatura.string' => 'La abreviatura debe ser una cadena de texto.',
      'abreviatura.max' => 'La abreviatura no debe exceder los 100 caracteres.',
      'abreviatura.unique' => 'La abreviatura ya está en uso.',

      'direccion.string' => 'La dirección debe ser una cadena de texto.',
      'direccion.max' => 'La dirección no debe exceder los 255 caracteres.',

      'empresa_id.integer' => 'El ID de la empresa debe ser un número entero.',
      'empresa_id.exists' => 'El ID de la empresa no existe.',

      'department_id.integer' => 'El ID del departamento debe ser un número entero.',
      'department_id.exists' => 'El ID del departamento no existe.',

      'province_id.integer' => 'El ID de la provincia debe ser un número entero.',
      'province_id.exists' => 'El ID de la provincia no existe.',

      'district_id.integer' => 'El ID del distrito debe ser un número entero.',
      'district_id.exists' => 'El ID del distrito no existe.',
    ];
  }
}
