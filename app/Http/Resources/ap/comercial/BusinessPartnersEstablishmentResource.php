<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\gp\gestionsistema\District;

class BusinessPartnersEstablishmentResource extends JsonResource
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
      'code' => $this->code,
      'type' => $this->type,
      'activity_economic' => $this->activity_economic,
      'address' => $this->address,
      'full_address' => $this->full_address,
      'ubigeo' => $this->ubigeo,
      'location' => $this->getLocation(),
      'business_partner_id' => $this->business_partner_id,
    ];
  }

  private function getLocation(): ?string
  {
    if (!$this->ubigeo) {
      return null;
    }

    $district = District::where('ubigeo', $this->ubigeo)->first();

    if (!$district) {
      return null;
    }

    return implode(' - ', array_filter([
      $district->name,
      $district->province->name ?? null,
      $district->province->department->name ?? null,
    ]));
  }
}
