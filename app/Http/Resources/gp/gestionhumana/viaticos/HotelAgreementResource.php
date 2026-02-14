<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelAgreementResource extends JsonResource
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
      'ruc' => $this->ruc,
      'city' => $this->city,
      'name' => $this->name,
      'corporate_rate' => $this->corporate_rate,
      'features' => $this->features,
      'includes_breakfast' => $this->includes_breakfast,
      'includes_lunch' => $this->includes_lunch,
      'includes_dinner' => $this->includes_dinner,
      'includes_parking' => $this->includes_parking,
      'email' => $this->email,
      'phone' => $this->phone,
      'address' => $this->address,
      'website' => $this->website,
      'active' => $this->active,
    ];
  }
}
