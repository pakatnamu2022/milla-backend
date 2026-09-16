<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class SyncProcessCompetencesRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'sub_competencias'               => 'required|array|min:1|max:5',
      'sub_competencias.*.id'          => 'required|integer|exists:gh_config_subcompetencias,id',
      'sub_competencias.*.orden'       => 'nullable|integer|min:1',
    ];
  }
}
