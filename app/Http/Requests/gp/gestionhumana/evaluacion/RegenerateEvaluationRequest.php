<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class RegenerateEvaluationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'mode' => 'required|in:full_reset,sync_with_cycle,add_missing_only',
      'reset_progress' => 'boolean',
      'force' => 'boolean',
    ];
  }

  /**
   * Get custom attributes for validator errors.
   */
  public function attributes(): array
  {
    return [
      'mode' => 'modo de regeneración',
      'reset_progress' => 'resetear progreso',
      'force' => 'forzar regeneración',
    ];
  }

  /**
   * Prepare the data for validation.
   */
  protected function prepareForValidation(): void
  {
    $this->merge([
      'reset_progress' => $this->boolean('reset_progress', false),
      'force' => $this->boolean('force', false),
    ]);
  }
}
