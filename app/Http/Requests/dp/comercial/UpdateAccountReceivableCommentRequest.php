<?php

namespace App\Http\Requests\dp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountReceivableCommentRequest extends FormRequest
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
      'comment.required' => 'Comment is required.',
      'comment.max'      => 'Comment cannot exceed 2000 characters.',
    ];
  }
}
