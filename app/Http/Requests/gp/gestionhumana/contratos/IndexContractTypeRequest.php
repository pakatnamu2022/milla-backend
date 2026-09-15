<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use App\Http\Requests\IndexRequest;

class IndexContractTypeRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'search'      => 'nullable|string',
      'descripcion' => 'nullable|string',
    ]);
  }
}
