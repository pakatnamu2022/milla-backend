<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Http\Requests\IndexRequest;

class IndexRecruitmentProcessRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'search'             => 'nullable|string',
      'nombre_postulacion' => 'nullable|string',
      'status_id'          => 'nullable|integer',
      'sede_id'            => 'nullable|integer',
      'area_id'            => 'nullable|integer',
      'cargo_id'           => 'nullable|integer',
      'fecha_inicio'       => 'nullable|array',
    ]);
  }
}
