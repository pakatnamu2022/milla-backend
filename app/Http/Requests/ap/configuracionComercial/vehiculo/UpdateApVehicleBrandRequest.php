<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApCommercialMasters;
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
          ->where('type_operation_id', ApCommercialMasters::TIPO_OPERACION_COMERCIAL)
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
      'type_operation_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'type_class_id' => [
        'nullable',
        'integer',
        Rule::exists('ap_commercial_masters', 'id')
          ->where('type', 'CLASS_TYPE')
          ->where('status', 1)
          ->whereNull('deleted_at'),
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

      'type_operation_id.integer' => 'El campo tipo de operación es obligatorio.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no existe',

      'type_class_id.integer' => 'El campo tipo de clase debe ser un número.',
      'type_class_id.exists' => 'El tipo de clase seleccionado no es válido',
    ];
  }
}
