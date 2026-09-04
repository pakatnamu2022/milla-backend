<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Http\Requests\IndexRequest;

class IndexApplicantRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'search'                 => 'nullable|string',
      'nombre_completo'        => 'nullable|string',
      'vat'                    => 'nullable|string',
      'proceso_postulacion_id' => 'nullable|integer',
      'sede_id'                => 'nullable|integer',
      'area_id'                => 'nullable|integer',
      'cargo_id'               => 'nullable|integer',
      'tipo_trabajador_id'     => 'nullable',
    ]);
  }
}
