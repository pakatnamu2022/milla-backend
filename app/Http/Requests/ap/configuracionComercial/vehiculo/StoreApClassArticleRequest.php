<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApClassArticleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'required',
        'string',
        'max:50',
        Rule::unique('ap_class_article', 'dyn_code')
          ->whereNull('deleted_at'),
      ],
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_class_article', 'description')
          ->whereNull('deleted_at'),
      ],
      'inventory_account' => [
        'required',
        'string',
        'max:20',
      ],
      'counterparty_account' => [
        'nullable',
        'string',
        'max:20',
      ],
      'account_sales' => [
        'nullable',
        'string',
        'max:20',
      ],
      'type_operation_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.required' => 'El campo codigo dyn es obligatorio.',
      'dyn_code.string' => 'El codigo dyn debe ser una cadena de texto.',
      'dyn_code.max' => 'El codigo dyn no debe exceder los 50 caracteres.',
      'dyn_code.unique' => 'El campo codigo dyn ya existe.',

      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'El campo descripción ya existe.',

      'inventory_account.required' => 'El campo cuenta es obligatorio.',
      'inventory_account.string' => 'La cuenta debe ser una cadena de texto.',
      'inventory_account.max' => 'La cuenta no debe exceder los 20 caracteres.',

      'counterparty_account.string' => 'La cuenta contrapartida debe ser una cadena de texto.',
      'counterparty_account.max' => 'La cuenta contrapartida no debe exceder los 20 caracteres.',

      'account_sales.string' => 'La cuenta de ventas debe ser una cadena de texto.',
      'account_sales.max' => 'La cuenta de ventas no debe exceder los 20 caracteres.',

      'type_operation_id.required' => 'El campo tipo de operación es obligatorio.',
      'type_operation_id.integer' => 'El campo tipo de operación debe ser un entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',
    ];
  }
}
