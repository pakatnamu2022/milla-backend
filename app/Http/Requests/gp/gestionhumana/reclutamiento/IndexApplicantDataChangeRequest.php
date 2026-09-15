<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Http\Requests\IndexRequest;

class IndexApplicantDataChangeRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'empleado_id' => 'nullable|integer',
      'status_id'   => 'nullable|integer',
    ]);
  }
}
