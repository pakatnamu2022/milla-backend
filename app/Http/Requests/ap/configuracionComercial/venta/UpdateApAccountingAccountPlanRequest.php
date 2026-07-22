<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApAccountingAccountPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'account' => [
        'nullable',
        'string',
        'max:20',
      ],
      'code_dynamics' => [
        'nullable',
        'string',
        'max:50',
      ],
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_accounting_account_plan', 'description')
          ->whereNull('deleted_at')
          ->ignore($this->route('accountingAccountPlan')),
      ],
      'is_detraction' => [
        'nullable',
        'boolean',
      ],
      'detraction_percentage' => [
        'nullable',
        'integer',
        'in:10,12',
        'required_if:is_detraction,true',
      ],
      'status' => [
        'nullable',
        'boolean',
      ],
      'enable_commercial' => [
        'nullable',
        'boolean',
      ],
      'enable_after_sales' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'account.max' => 'La cuenta no debe ser mayor a 20 caracteres.',

      'description.unique' => 'La descripción ya existe.',
      'description.max' => 'La descripción no debe ser mayor a 255 caracteres.',

      'is_detraction.boolean' => 'El campo detracción debe ser verdadero o falso.',

      'detraction_percentage.integer' => 'El porcentaje de detracción debe ser un número entero.',
      'detraction_percentage.in' => 'El porcentaje de detracción debe ser 10 o 12.',
      'detraction_percentage.required_if' => 'El porcentaje de detracción es obligatorio cuando se marca como detracción.',

      'enable_commercial.boolean' => 'El campo habilitar comercial debe ser verdadero o falso.',
      'enable_after_sales.boolean' => 'El campo habilitar post venta debe ser verdadero o falso.',
    ];
  }
}
