<?php

namespace App\Http\Requests\gp\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'abreviatura' => [
        'required',
        'string',
        'max:100',
        Rule::unique('config_sede', 'abreviatura')
          ->whereNull('deleted_at'),
      ],
      'dyn_code' => [
        'required',
        'string',
        'max:100',
      ],
      'direccion' => [
        'required',
        'string',
        'max:255',
      ],
      'empresa_id' => [
        'required',
        'integer',
        'exists:companies,id',
      ],
      'department_id' => [
        'required',
        'integer',
        'exists:department,id',
      ],
      'province_id' => [
        'required',
        'integer',
        'exists:province,id',
      ],
      'district_id' => [
        'required',
        'integer',
        'exists:district,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'abreviatura.required' => 'La abreviatura es obligatoria.',
      'abreviatura.string' => 'La abreviatura debe ser una cadena de texto.',
      'abreviatura.max' => 'La abreviatura no debe exceder los 100 caracteres.',
      'abreviatura.unique' => 'La abreviatura ya está en uso.',

      'direccion.required' => 'La dirección es obligatoria.',
      'direccion.string' => 'La dirección debe ser una cadena de texto.',
      'direccion.max' => 'La dirección no debe exceder los 255 caracteres.',

      'empresa_id.required' => 'El ID de la empresa es obligatorio.',
      'empresa_id.integer' => 'El ID de la empresa debe ser un número entero.',
      'empresa_id.exists' => 'El ID de la empresa no existe en la base de datos.',

      'department_id.required' => 'El departamento es obligatorio.',
      'department_id.integer' => 'El departamento debe ser un número entero.',
      'department_id.exists' => 'El departamento no existe en la base de datos.',

      'province_id.required' => 'La provincia es obligatoria.',
      'province_id.integer' => 'La provincia debe ser un número entero.',
      'province_id.exists' => 'La provincia no existe en la base de datos.',

      'district_id.required' => 'El distrito es obligatorio.',
      'district_id.integer' => 'El distrito debe ser un número entero.',
      'district_id.exists' => 'El distrito no existe en la base de datos.',
    ];
  }
}
