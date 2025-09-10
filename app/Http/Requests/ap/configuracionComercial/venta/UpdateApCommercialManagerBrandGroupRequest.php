<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class UpdateApCommercialManagerBrandGroupRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'brand_group_id' => 'required|exists:ap_commercial_masters,id',
      'commercial_managers' => 'required|array|min:1',
      'commercial_managers.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'brand_group_id.required' => 'El campo brand_group_id es obligatorio.',
      'brand_group_id.exists' => 'El grupo de marca proporcionado no existe.',

      'commercial_managers.required' => 'El campo commercial_managers es obligatorio.',
      'commercial_managers.array' => 'El campo gerente comercial debe ser un arreglo.',
      'commercial_managers.min' => 'Debe proporcionar al menos un gerente comercial.',

      'commercial_managers.*.integer' => 'Cada gerente comercial debe ser un ID entero válido.',
      'commercial_managers.*.exists' => 'Uno o más IDs de gerente comercial proporcionados no existen.',
    ];
  }
}
