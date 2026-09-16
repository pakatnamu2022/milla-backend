<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Http\Requests\IndexRequest;

class IndexInterviewRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'proceso_postulacion_id' => 'nullable|integer',
      'persona_id'              => 'nullable|integer',
      'entrevistador_id'        => 'nullable|integer',
      'fase'                    => 'nullable|integer',
    ]);
  }
}
