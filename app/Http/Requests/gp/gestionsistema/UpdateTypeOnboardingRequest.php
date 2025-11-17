<?php

namespace App\Http\Requests\gp\gestionsistema;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeOnboardingRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => 'sometimes|required|string|max:255',
      'status_deleted' => 'nullable|in:0,1',
    ];
  }
}
