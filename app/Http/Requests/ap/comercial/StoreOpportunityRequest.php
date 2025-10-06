<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreOpportunityRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'client_id' => 'required|integer|exists:business_partners,id',
      'family_id' => 'required|integer|exists:ap_families,id',
      'opportunity_type_id' => 'required|integer|exists:ap_commercial_masters,id',
      'client_status_id' => 'required|integer|exists:ap_commercial_masters,id',
      'opportunity_status_id' => 'required|integer|exists:ap_commercial_masters,id',
      'lead_id' => 'required|integer|exists:potential_buyers,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'client_id' => 'Cliente',
      'family_id' => 'Familia',
      'opportunity_type_id' => 'Tipo de oportunidad',
      'client_status_id' => 'Estado del cliente',
      'opportunity_status_id' => 'Estado de la oportunidad',
    ];
  }
}
