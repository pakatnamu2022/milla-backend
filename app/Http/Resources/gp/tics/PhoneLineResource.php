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
      'number' => $this->number,
      'status' => $this->status,
      'is_active' => $this->is_active,
      'telephone_account_id' => $this->telephone_account_id,
      'telephone_plan_id' => $this->telephone_plan_id,
      'company_id' => $this->company_id,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
