<?php

namespace App\Http\Requests\dp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuentaPorCobrarComentarioRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'comment' => ['required', 'string', 'max:2000'],
    ];
  }

  public function messages(): array
  {
    return [
      'comment.required' => 'El comentario es obligatorio.',
      'comment.max'      => 'El comentario no puede superar los 2000 caracteres.',
    ];
  }
}
