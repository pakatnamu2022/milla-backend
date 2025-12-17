<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelReservationResource extends JsonResource
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
      'hotel_name' => $this->hotel_name,
      'address' => $this->address,
      'phone' => $this->phone,
      'checkin_date' => $this->checkin_date,
      'checkout_date' => $this->checkout_date,
      'nights_count' => $this->nights_count,
      'total_cost' => (float)$this->total_cost,
      'receipt_path' => $this->receipt_path,
      'notes' => $this->notes,
      'attended' => $this->attended,
      'penalty' => (float)$this->penalty,

      // Relations
      'hotel_agreement' => $this->whenLoaded('hotelAgreement', function () {
        return $this->hotelAgreement ? [
          'id' => $this->hotelAgreement->id,
          'hotel_name' => $this->hotelAgreement->hotel_name,
          'category' => $this->hotelAgreement->category,
        ] : null;
      }),

      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
