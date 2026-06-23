<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexManualRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'vista_id' => 'nullable|integer|exists:config_vista,id',
      'search'   => 'nullable|string|max:100',
      'per_page' => 'nullable|integer|min:1|max:200',
      'sort'     => 'nullable|string',
      'order'    => 'nullable|in:asc,desc',
    ];
  }
}
