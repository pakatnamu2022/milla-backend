<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneLineResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'line_number' => $this->line_number,
      'company' => $this->telephoneAccount?->company?->name,
      'active_assignment' => $this->activeAssignment ? new PhoneLineWorkerResource($this->activeAssignment) : null,
//      'status' => $this->status,
      'is_active' => (bool)$this->is_active,
      'telephone_account_id' => $this->telephone_account_id,
      'telephone_plan_id' => $this->telephone_plan_id,
      'company_id' => $this->telephoneAccount?->company_id,
    ];
  }
}
