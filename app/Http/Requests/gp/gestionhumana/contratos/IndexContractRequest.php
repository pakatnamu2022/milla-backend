<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use App\Http\Requests\IndexRequest;

class IndexContractRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'search'                => 'nullable|string',
      'empleado_id'           => 'nullable|integer',
      'tipo_contrato_id'      => 'nullable|integer',
      'sede_id'               => 'nullable|integer',
      'fecha_inicio_contrato' => 'nullable|array',
      'fecha_fin_contrato'    => 'nullable|array',
    ]);
  }
}
