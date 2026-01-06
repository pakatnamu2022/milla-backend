<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class ResendPerDiemRequestEmailsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'email_type' => ['required', 'string', 'in:created,approved,settlement,settled,cancelled,in_progress'],
      'send_to_employee' => ['nullable', 'boolean'],
      'send_to_boss' => ['nullable', 'boolean'],
      'send_to_accounting' => ['nullable', 'boolean'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'email_type.required' => 'El tipo de correo es requerido.',
      'email_type.in' => 'El tipo de correo debe ser uno de los siguientes: created, approved, settlement, settled, cancelled, in_progress.',
      'send_to_employee.boolean' => 'El campo enviar al empleado debe ser verdadero o falso.',
      'send_to_boss.boolean' => 'El campo enviar al jefe debe ser verdadero o falso.',
      'send_to_accounting.boolean' => 'El campo enviar a contabilidad debe ser verdadero o falso.',
    ];
  }
}
