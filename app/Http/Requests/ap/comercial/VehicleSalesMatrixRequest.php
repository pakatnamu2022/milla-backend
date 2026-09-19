<?php

namespace App\Http\Requests\ap\comercial;

use Illuminate\Foundation\Http\FormRequest;

class VehicleSalesMatrixRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'year'      => 'required|integer|min:2000|max:2100',
      'shop_id'   => 'nullable|array',
      'shop_id.*' => 'integer',
      'sede_id'   => 'nullable|array',
      'sede_id.*' => 'integer',
    ];
  }

  public function messages(): array
  {
    return [
      'year.required' => 'El año es requerido',
      'year.integer'  => 'El año debe ser un número',
    ];
  }
}
