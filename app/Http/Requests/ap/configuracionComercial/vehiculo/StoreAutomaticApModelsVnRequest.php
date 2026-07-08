<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;

class StoreAutomaticApModelsVnRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'brand_id' => [
        'required',
        'integer',
        'exists:ap_vehicle_brand,id',
      ],
      'class_id' => [
        'required',
        'integer',
        'exists:ap_class_article,id',
      ],
      'type_operation_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'version' => [
        'required',
        'string',
        'max:255',
      ],
    ];
  }

  public function attributes()
  {
    return [
      'brand_id' => 'marca',
      'class_id' => 'clase',
      'type_operation_id' => 'tipo de operación',
      'version' => 'versión',
    ];
  }
}