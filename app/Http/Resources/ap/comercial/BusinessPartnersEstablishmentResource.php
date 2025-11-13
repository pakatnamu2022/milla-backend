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
    $districtData = $this->getDistrictData();

    return [
      'id' => $this->id,
      'code' => $this->code,
      'description' => $this->description,
      'type' => $this->type,
      'activity_economic' => $this->activity_economic,
      'address' => $this->address,
      'full_address' => $this->full_address,
      'ubigeo' => $this->ubigeo,
      'location' => $districtData['location'] ?? null,
      'district_id' => $districtData['district_id'] ?? null,
      'province_id' => $districtData['province_id'] ?? null,
      'department_id' => $districtData['department_id'] ?? null,
      'business_partner_id' => $this->business_partner_id,
      'sede_id' => $this->sede_id ?? "",
      'sede' => $this->sede->abreviatura ?? "",
      'status' => $this->status,
    ];
  }

  private function getDistrictData(): array
  {
    if (!$this->ubigeo) {
      return [];
    }

    $district = District::where('ubigeo', $this->ubigeo)
      ->with(['province.department'])
      ->first();

    if (!$district) {
      return [];
    }

    $location = implode(' - ', array_filter([
      $district->name,
      $district->province->name ?? null,
      $district->province->department->name ?? null,
    ]));

    return [
      'location' => $location,
      'district_id' => $district->id,
      'province_id' => $district->province->id ?? null,
      'department_id' => $district->province->department->id ?? null,
    ];
  }
}
