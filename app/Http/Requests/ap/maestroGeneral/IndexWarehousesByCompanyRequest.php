<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\IndexRequest;

class IndexWarehousesByCompanyRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'my' => ['required', 'boolean'],
      'is_received' => ['required', 'boolean'],
      'ap_class_article_id' => ['required', 'integer', 'exists:ap_class_article,id'],
      'empresa_id' => ['required', 'integer', 'exists:companies,id'],
      'type_operation_id' => ['required', 'integer', 'exists:ap_commercial_masters,id'],
    ];
  }

  public function messages(): array
  {
    return [
      'empresa_id.required' => 'La empresa es obligatoria.',
      'empresa_id.integer' => 'La empresa debe ser un número entero.',
      'empresa_id.exists' => 'La empresa no existe en la base de datos.',

      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación no existe en la base de datos.',
    ];
  }
}
