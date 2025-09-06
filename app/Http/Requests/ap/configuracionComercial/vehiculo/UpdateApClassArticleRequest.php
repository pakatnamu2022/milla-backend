<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApClassArticleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_class_article', 'dyn_code')
          ->whereNull('deleted_at')
          ->ignore($this->route('classArticle')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_class_article', 'description')
          ->whereNull('deleted_at')
          ->ignore($this->route('classArticle')),
      ],
      'account' => [
        'nullable',
        'string',
        'max:150',
        Rule::unique('ap_class_article', 'account')
          ->whereNull('deleted_at')
          ->ignore($this->route('classArticle')),
      ],
      'type' => [
        'nullable',
        'string',
        Rule::in(['POSTVENTA', 'VEHICULO']),
      ],
      'status' => ['nullable', 'boolean']
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.string' => 'El codigo dyn debe ser una cadena de texto.',
      'dyn_code.max' => 'El codigo dyn no debe exceder los 50 caracteres.',
      'dyn_code.unique' => 'El campo codigo dyn ya existe.',

      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'El campo descripción ya existe.',

      'account.string' => 'La cuenta debe ser una cadena de texto.',
      'account.max' => 'La cuenta no debe exceder los 150 caracteres.',
      'account.unique' => 'El campo cuenta ya existe.',

      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.in' => 'El tipo seleccionado no es válido.',
    ];
  }
}
