<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApVehicleBrandRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_vehicle_brand', 'code')->whereNull('deleted_at'),
      ],
      'dyn_code' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_vehicle_brand', 'dyn_code')->whereNull('deleted_at'),
      ],
      'name' => [
        'required',
        'string',
        'max:150',
        Rule::unique('ap_vehicle_brand', 'name')->whereNull('deleted_at'),
      ],
      'description' => [
        'required',
        'string',
        'max:250'
      ],
      'group_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'logo' => [
        'nullable',
        'file',
        'mimes:jpeg,png,webp,jpg',
        'max:2048',
      ],
      'logo_min' => [
        'nullable',
        'file',
        'mimes:jpeg,png,webp,jpg',
        'max:2048',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El código es requerido',
      'code.unique' => 'Este código ya existe',
      'code.max' => 'El código no debe exceder 100 caracteres',
      'dyn_code.required' => 'El código DYN es requerido',
      'dyn_code.unique' => 'Este código DYN ya existe',
      'dyn_code.max' => 'El dyn_code no debe exceder 100 caracteres',
      'name.required' => 'El nombre es requerido',
      'name.unique' => 'Este nombre ya existe',
      'name.max' => 'El nombre no debe exceder 150 caracteres',
      'description.required' => 'La descripción es requerida',
      'description.unique' => 'Esta descripción ya existe',
      'description.max' => 'El descripción no debe exceder 255 caracteres',
      'group_id.required' => 'Debe seleccionar un grupo',
      'group_id.integer' => 'El campo grupo es obligatorio.',
      'group_id.exists' => 'El grupo seleccionado no existe',
      'logo.mimes' => 'El logo debe ser un archivo JPG, PNG o WebP',
      'logo.max' => 'El logo no debe superar los 2MB',
      'logo_min.mimes' => 'El logo min debe ser un archivo JPG, PNG o WebP',
      'logo_min.max' => 'El logo min no debe superar los 2MB',
    ];
  }
}
