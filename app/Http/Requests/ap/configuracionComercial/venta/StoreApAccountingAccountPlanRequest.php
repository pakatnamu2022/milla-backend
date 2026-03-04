<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApAccountingAccountPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'account' => [
        'required',
        'string',
        'max:20',
      ],
      'code_dynamics' => [
        'required',
        'string',
        'max:50',
      ],
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_accounting_account_plan', 'description')
          ->whereNull('deleted_at'),
      ],
      'accounting_type_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'account.required' => 'La cuenta es obligatoria.',
      'account.string' => 'La cuenta debe ser una cadena de texto.',
      'account.max' => 'La cuenta no debe exceder los 20 caracteres.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ingresada ya existe en los registros.',

      'accounting_type_id.required' => 'Debe seleccionar un tipo de account contable',
      'accounting_type_id.integer' => 'El campo tipo de account contable es obligatorio.',
      'accounting_type_id.exists' => 'El tipo de account contable seleccionado no existe',
    ];
  }
}
