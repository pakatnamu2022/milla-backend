<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreOpportunityActionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'opportunity_id' => 'required|integer|exists:ap_opportunity,id',
      'action_type_id' => 'required|integer|exists:ap_masters,id',
      'action_contact_type_id' => 'required|integer|exists:ap_masters,id',
      'description' => 'nullable|string',
      'result' => 'required|boolean',
    ];
  }

  public function attributes(): array
  {
    return [
      'opportunity_id' => 'oportunidad',
      'action_type_id' => 'tipo de acción',
      'action_contact_type_id' => 'tipo de contacto',
      'description' => 'descripción',
      'result' => 'resultado',
    ];
  }
}
