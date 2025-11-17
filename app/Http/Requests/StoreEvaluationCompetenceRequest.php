<?php

namespace App\Http\Requests;

class StoreEvaluationCompetenceRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'nombre' => 'required|string|max:255',
      'subCompetences' => 'array|required|min:1',
      'subCompetences.*.nombre' => 'required|string|max:255',
      'subCompetences.*.definicion' => 'nullable|string|max:500',
      'subCompetences.*.level1' => 'nullable|string|max:255',
      'subCompetences.*.level2' => 'nullable|string|max:255',
      'subCompetences.*.level3' => 'nullable|string|max:255',
      'subCompetences.*.level4' => 'nullable|string|max:255',
      'subCompetences.*.level5' => 'nullable|string|max:255',
    ];
  }
}
