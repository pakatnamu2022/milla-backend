<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelephoneAccountResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'company_id' => $this->company_id,
      'company' => $this->company->name,
      'account_number' => $this->account_number,
      'operator' => $this->operator,
    ];
  }
}
