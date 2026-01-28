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
        Rule::unique('ap_accounting_account_plan', 'account')
          ->whereNull('deleted_at')
          ->ignore($this->route('accountingAccountPlan'))
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
      'accounting_type_id' => [
        'nullable',
        'integer',
        'exists:ap_masters,id',
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
      'account.unique' => 'La cuenta ya existe.',
      'account.max' => 'La cuenta no debe ser mayor a 20 caracteres.',

      'description.unique' => 'La descripción ya existe.',
      'description.max' => 'La descripción no debe ser mayor a 255 caracteres.',

      'accounting_type_id.exists' => 'El tipo de account contable no existe.',
    ];
  }
}
