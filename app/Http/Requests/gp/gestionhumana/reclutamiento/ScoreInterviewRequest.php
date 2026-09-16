<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class ScoreInterviewRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'scores'                      => 'required|array|min:1',
      'scores.*.sub_competencia_id' => 'required|integer|exists:gh_config_subcompetencias,id',
      'scores.*.puntaje'            => 'required|numeric|min:0|max:5',
    ];
  }
}
