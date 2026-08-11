<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateObjectiveSedePeriodPvRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $id = $this->route('id');

    return [
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id',
        Rule::unique('objective_sede_period_pv')
          ->where('year', $this->year)
          ->where('month', $this->month)
          ->ignore($id)
      ],
      'year' => 'required|integer|min:2000|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'amount' => 'required|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
      'sede_id.unique' => 'Ya existe un objetivo para esta sede en el período seleccionado.',

      'year.required' => 'El campo año es obligatorio.',
      'year.integer' => 'El año debe ser un número entero.',
      'year.min' => 'El año debe ser mayor o igual a 2000.',
      'year.max' => 'El año debe ser menor o igual a 2100.',

      'month.required' => 'El campo mes es obligatorio.',
      'month.integer' => 'El mes debe ser un número entero.',
      'month.min' => 'El mes debe estar entre 1 y 12.',
      'month.max' => 'El mes debe estar entre 1 y 12.',

      'amount.required' => 'El campo monto es obligatorio.',
      'amount.numeric' => 'El monto debe ser un valor numérico.',
      'amount.min' => 'El monto debe ser mayor o igual a 0.',
    ];
  }
}
