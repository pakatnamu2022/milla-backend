<?php

namespace App\Http\Resources\ap\comercial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleDeliveryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'vehicle_id' => $this->vehicle_id,
      'vin' => $this->vehicle->vin,
      'scheduled_delivery_date' => $this->scheduled_delivery_date ? Carbon::parse($this->scheduled_delivery_date)->format('Y-m-d') : null,
      'wash_date' => $this->wash_date ? Carbon::parse($this->wash_date)->format('Y-m-d') : null,
      'observations' => $this->observations,
      'advisor_id' => $this->advisor_id,
      'advisor_name' => $this->advisor ? $this->advisor->nombre_completo : null,
      'sede_id' => $this->sede_id,
      'sede_name' => $this->sede ? $this->sede->abreviatura : null,
      'status_wash' => $this->translateStatus($this->status_wash),
      'status_delivery' => $this->translateStatus($this->status_delivery),
      'client_name' => $this->client->full_name,
      'shipping_guide' => $this->whenLoaded('ShippingGuide', function () {
        return new ShippingGuidesResource($this->ShippingGuide);
      }),
    ];
  }

  private function translateStatus($status)
  {
    $translations = [
      'pending' => 'Pendiente',
      'completed' => 'Completado',
    ];

    return $translations[strtolower($status)] ?? $status;
  }
}
