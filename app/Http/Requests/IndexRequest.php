<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class IndexRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'all' => 'nullable|in:true,false',
      'sort' => 'nullable|string',
      'direction' => 'nullable|string',
      'page' => 'nullable|integer|min:1',
      'per_page' => 'nullable|integer|min:1|max:100',
    ];
  }

  public function failedValidation(Validator $validator)
  {
    $response = response()->json([
      'message' => $validator->errors()->first(),
    ], 422);

    throw new ValidationException($validator, $response);
  }
}
