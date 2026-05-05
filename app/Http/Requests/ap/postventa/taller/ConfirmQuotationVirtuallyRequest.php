<?php

namespace App\Http\Requests\ap\postventa\taller;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmQuotationVirtuallyRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   * Este endpoint es público, no requiere autenticación
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'notes' => 'nullable|string|max:1000',
      'confirmed_by_name' => 'nullable|string|max:255',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'notes.string' => 'Las notas deben ser texto.',
      'notes.max' => 'Las notas no pueden exceder los 1000 caracteres.',
      'confirmed_by_name.string' => 'El nombre debe ser texto.',
      'confirmed_by_name.max' => 'El nombre no puede exceder los 255 caracteres.',
    ];
  }
}