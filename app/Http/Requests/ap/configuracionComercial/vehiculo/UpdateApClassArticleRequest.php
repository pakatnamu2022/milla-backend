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
      'codigo_dyn' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_class_article', 'codigo_dyn')
          ->whereNull('deleted_at')
          ->ignore($this->route('classArticle')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_class_article', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('classArticle')),
      ],
      'cuenta' => [
        'nullable',
        'string',
        'max:150',
        Rule::unique('ap_class_article', 'cuenta')
          ->whereNull('deleted_at')
          ->ignore($this->route('classArticle')),
      ],
      'tipo' => [
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
      'codigo_dyn.string' => 'La codigo_dyn debe ser una cadena de texto.',
      'codigo_dyn.max' => 'La codigo_dyn no debe exceder los 50 caracteres.',
      'codigo_dyn.unique' => 'El campo codigo_dyn ya existe.',

      'descripcion.string' => 'La descripcion debe ser una cadena de texto.',
      'descripcion.max' => 'La codigo_dyn no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'El campo descripcion ya existe.',

      'cuenta.string' => 'La cuenta debe ser una cadena de texto.',
      'cuenta.max' => 'La cuenta no debe exceder los 150 caracteres.',
      'cuenta.unique' => 'El campo cuenta ya existe.',

      'tipo.string' => 'El tipo debe ser una cadena de texto.',
      'tipo.in' => 'El tipo seleccionado no es válido.',
    ];
  }
}
