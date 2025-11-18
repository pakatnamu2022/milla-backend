<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApVehicleBrandRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_vehicle_brand', 'code')->whereNull('deleted_at')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehicleBrand')),
      ],
      'dyn_code' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_vehicle_brand', 'dyn_code')->whereNull('deleted_at')
          ->whereNull('deleted_at')
          ->where('is_commercial', true)
          ->ignore($this->route('vehicleBrand')),
      ],
      'name' => [
        'nullable',
        'string',
        'max:150',
        Rule::unique('ap_vehicle_brand', 'name')->whereNull('deleted_at')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehicleBrand')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:250'
      ],
      'group_id' => [
        'nullable',
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
      'is_commercial' => [
        'nullable',
        'boolean',
      ],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'code.max' => 'El código no debe exceder 100 caracteres',
      'code.unique' => 'Este código ya existe',

      'dyn_code.max' => 'El dyn_code no debe exceder 100 caracteres',
      'dyn_code.unique' => 'Este código DYN ya existe',

      'name.max' => 'El name no debe exceder 150 caracteres',
      'name.unique' => 'Este name ya existe',

      'description.max' => 'El description no debe exceder 255 caracteres',
      'description.unique' => 'Esta descripción ya existe',

      'group_id.integer' => 'El campo grupo es obligatorio.',
      'group_id.exists' => 'El grupo seleccionado no existe',

      'logo.mimes' => 'El logo debe ser un archivo JPG, PNG o WebP',
      'logo.max' => 'El logo no debe superar los 2MB',
      'logo_min.mimes' => 'El logo min debe ser un archivo JPG, PNG o WebP',
      'logo_min.max' => 'El logo min no debe superar los 2MB',
    ];
  }
}
