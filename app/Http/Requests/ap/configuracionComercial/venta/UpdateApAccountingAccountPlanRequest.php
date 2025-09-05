<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApAccountingAccountPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'cuenta' => [
        'nullable',
        'string',
        'max:20',
        Rule::unique('ap_accounting_account_plan', 'cuenta')
          ->whereNull('deleted_at')
          ->ignore($this->route('accountingAccountPlan'))
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_accounting_account_plan', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('accountingAccountPlan')),
      ],
      'tipo_cta_contable_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'status' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'cuenta.unique' => 'La cuenta ya existe.',
      'cuenta.max' => 'La cuenta no debe ser mayor a 20 caracteres.',

      'descripcion.unique' => 'La descripción ya existe.',
      'descripcion.max' => 'La descripción no debe ser mayor a 255 caracteres.',

      'tipo_cta_contable_id.exists' => 'El tipo de cuenta contable no existe.',
    ];
  }
}
