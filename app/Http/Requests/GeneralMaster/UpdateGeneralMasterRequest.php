<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralMasterRequest extends StoreRequest
{
  protected function prepareForValidation(): void
  {
    if ($this->has('effective_from') || $this->has('effective_to')) {
      $this->merge([
        'effective_from' => $this->input('effective_from') ?: null,
        'effective_to' => $this->input('effective_to') ?: null,
      ]);
    }
  }

  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'required',
        'string',
        'max:255',
        Rule::unique('general_masters', 'code')
          ->where('status', 1)
          ->where('type', $this->input('type'))
          ->where(function ($query) {
            $from = $this->input('effective_from');
            $from
              ? $query->where('effective_from', $from)
              : $query->whereNull('effective_from');
          })
          ->whereNull('deleted_at')
          ->ignore($this->route('generalMaster')),
      ],
      'description' => 'sometimes|string|max:255',
      'type' => 'sometimes|string|max:255',
      'value' => 'nullable|string|max:255',
      'effective_from' => 'nullable|date',
      'effective_to' => 'nullable|date|after_or_equal:effective_from',
      'status' => 'sometimes|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El campo código es obligatorio.',
      'code.string' => 'El campo código debe ser una cadena de texto.',
      'code.max' => 'El campo código no debe exceder los 255 caracteres.',
      'code.unique' => 'El código ya existe para el mismo tipo.',
      'description.string' => 'El campo descripción debe ser una cadena de texto.',
      'description.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'type.string' => 'El campo tipo debe ser una cadena de texto.',
      'type.max' => 'El campo tipo no debe exceder los 255 caracteres.',
      'value.string' => 'El campo valor debe ser una cadena de texto.',
      'value.max' => 'El campo valor no debe exceder los 255 caracteres.',
      'effective_from.date' => 'El campo vigente desde debe ser una fecha válida.',
      'effective_to.date' => 'El campo vigente hasta debe ser una fecha válida.',
      'effective_to.after_or_equal' => 'La fecha de fin de vigencia no puede ser anterior a la de inicio.',
      'status.boolean' => 'El campo estado debe ser un valor booleano.',
    ];
  }
}
