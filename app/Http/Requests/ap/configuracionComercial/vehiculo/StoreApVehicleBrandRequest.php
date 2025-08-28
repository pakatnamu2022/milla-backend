<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApVehicleBrandRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_vehicle_brand', 'codigo')->whereNull('deleted_at'),
      ],
      'codigo_dyn' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_vehicle_brand', 'codigo_dyn')->whereNull('deleted_at'),
      ],
      'nombre' => [
        'required',
        'string',
        'max:150',
        Rule::unique('ap_vehicle_brand', 'nombre')->whereNull('deleted_at'),
      ],
      'descripcion' => [
        'required',
        'string',
        'max:250'
      ],
      'grupo_id' => [
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
      'codigo.required' => 'El código es requerido',
      'codigo.unique' => 'Este código ya existe',
      'codigo.max' => 'El código no debe exceder 100 caracteres',
      'codigo_dyn.required' => 'El código DYN es requerido',
      'codigo_dyn.unique' => 'Este código DYN ya existe',
      'codigo_dyn.max' => 'El codigo_dyn no debe exceder 100 caracteres',
      'nombre.required' => 'El nombre es requerido',
      'nombre.unique' => 'Este nombre ya existe',
      'nombre.max' => 'El nombre no debe exceder 150 caracteres',
      'descripcion.required' => 'La descripción es requerida',
      'descripcion.unique' => 'Esta descripción ya existe',
      'descripcion.max' => 'El descripcion no debe exceder 255 caracteres',
      'grupo_id.required' => 'Debe seleccionar un grupo',
      'grupo_id.integer' => 'El campo grupo es obligatorio.',
      'grupo_id.exists' => 'El grupo seleccionado no existe',
      'logo.mimes' => 'El logo debe ser un archivo JPG, PNG o WebP',
      'logo.max' => 'El logo no debe superar los 2MB',
      'logo_min.mimes' => 'El logo min debe ser un archivo JPG, PNG o WebP',
      'logo_min.max' => 'El logo min no debe superar los 2MB',
    ];
  }
}
