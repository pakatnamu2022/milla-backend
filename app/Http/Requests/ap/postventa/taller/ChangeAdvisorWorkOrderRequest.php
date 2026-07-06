<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class ChangeAdvisorWorkOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'advisor_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
    ];
  }

  public function messages(): array
  {
    return [
      'advisor_id.required' => 'El asesor de servicio es obligatorio.',
      'advisor_id.integer' => 'El asesor de servicio debe ser un número válido.',
      'advisor_id.exists' => 'El asesor de servicio seleccionado no existe.',
    ];
  }
}
