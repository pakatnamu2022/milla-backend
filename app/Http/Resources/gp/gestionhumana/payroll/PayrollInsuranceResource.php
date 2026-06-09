<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollInsuranceResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'worker_id' => $this->worker_id,
      'worker' => $this->worker?->nombre_completo,
      'period_id' => $this->period_id,
      'period' => $this->period?->name,
      'business_partner_id' => $this->business_partner_id,
      'business_partner' => $this->businessPartner?->name,
      'doc_number_affiliate' => $this->doc_number_affiliate,
      'rate_with_tax' => $this->rate_with_tax,
      'contracting_name' => $this->contracting_name,
      'num_doc_contracting' => $this->num_doc_contracting,
    ];
  }
}
