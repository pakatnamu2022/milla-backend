<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApGoalSellOutInRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => [
        'required',
        'integer',
        'min:2000',
        'max:2100',
      ],
      'month' => [
        'required',
        'integer',
        'min:1',
        'max:12',
      ],
      'goal' => [
        'required',
        'numeric',
        'min:1',
      ],
      'type' => [
        'required',
        'string',
        'in:IN,OUT',
      ],
      'brand_id' => [
        'required',
        'integer',
        'exists:ap_vehicle_brand,id',
      ],
      'shop_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'year.required' => 'El campo año es obligatorio.',
      'year.integer' => 'El campo año debe ser un número entero.',
      'year.min' => 'El campo año debe ser mayor o igual a 2000.',
      'year.max' => 'El campo año debe ser menor o igual a 2100.',

      'month.required' => 'El campo mes es obligatorio.',
      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.min' => 'El campo mes debe ser mayor o igual a 1.',
      'month.max' => 'El campo mes debe ser menor o igual a 12.',

      'goal.required' => 'El campo meta es obligatorio.',
      'goal.numeric' => 'El campo meta debe ser un número.',
      'goal.min' => 'El campo meta debe ser mayor o igual a 1.',

      'type.required' => 'El campo tipo es obligatorio.',
      'type.string' => 'El campo tipo debe ser una cadena de texto.',
      'type.in' => 'El campo tipo debe ser IN o OUT.',

      'brand_id.required' => 'El campo marca es obligatorio.',
      'brand_id.integer' => 'El campo marca debe ser un número entero.',
      'brand_id.exists' => 'La marca seleccionada no existe.',

      'shop_id.required' => 'El campo tienda es obligatorio.',
      'shop_id.integer' => 'El campo tienda debe ser un número entero.',
      'shop_id.exists' => 'La tienda seleccionada no existe.',
    ];
  }
}
