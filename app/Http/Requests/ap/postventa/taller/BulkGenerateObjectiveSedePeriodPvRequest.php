<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class BulkGenerateObjectiveSedePeriodPvRequest extends FormRequest
{
  public function authorize()
  {
    return true;
  }

  public function rules()
  {
    return [
      'year' => 'required|integer|min:2020|max:2100',
      'month' => 'required|integer|min:1|max:12',
    ];
  }

  public function messages()
  {
    return [
      'year.required' => 'El año es obligatorio',
      'year.integer' => 'El año debe ser un número entero',
      'year.min' => 'El año debe ser mayor o igual a 2020',
      'year.max' => 'El año debe ser menor o igual a 2100',
      'month.required' => 'El mes es obligatorio',
      'month.integer' => 'El mes debe ser un número entero',
      'month.min' => 'El mes debe estar entre 1 y 12',
      'month.max' => 'El mes debe estar entre 1 y 12',
    ];
  }
}