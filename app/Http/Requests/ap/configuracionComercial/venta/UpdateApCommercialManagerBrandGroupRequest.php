<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class UpdateApCommercialManagerBrandGroupRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'brand_group_id'        => 'required|exists:ap_masters,id',
      'commercial_managers'   => 'nullable|array',
      'commercial_managers.*' => 'integer|exists:rrhh_persona,id',
      'year'                  => 'required|integer|digits:4',
      'month'                 => 'required|integer|min:1|max:12',
    ];
  }

  public function attributes()
  {
    return [
      'brand_group_id'      => 'grupo de marcas',
      'commercial_managers' => 'gerentes comerciales',
      'year'                => 'año',
      'month'               => 'mes',
    ];
  }
}
