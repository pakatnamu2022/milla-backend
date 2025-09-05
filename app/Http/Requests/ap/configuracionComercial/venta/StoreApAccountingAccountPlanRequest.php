<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApAccountingAccountPlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'cuenta' => [
        'required',
        'string',
        'max:20',
        Rule::unique('ap_accounting_account_plan', 'cuenta')
          ->whereNull('deleted_at'),
      ],
      'descripcion' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_accounting_account_plan', 'descripcion')
          ->whereNull('deleted_at'),
      ],
      'tipo_cta_contable_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'cuenta.required' => 'La cuenta es obligatoria.',
      'cuenta.string' => 'La cuenta debe ser una cadena de texto.',
      'cuenta.max' => 'La cuenta no debe exceder los 20 caracteres.',
      'cuenta.unique' => 'La cuenta ingresada ya existe en los registros.',

      'descripcion.required' => 'La descripción es obligatoria.',
      'descripcion.string' => 'La descripción debe ser una cadena de texto.',
      'descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ingresada ya existe en los registros.',

      'tipo_cta_contable_id.required' => 'Debe seleccionar un tipo de cuenta contable',
      'tipo_cta_contable_id.integer' => 'El campo tipo de cuenta contable es obligatorio.',
      'tipo_cta_contable_id.exists' => 'El tipo de cuenta contable seleccionado no existe',
    ];
  }
}
