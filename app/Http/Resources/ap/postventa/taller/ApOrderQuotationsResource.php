<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderQuotationsResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'vehicle_id' => $this->vehicle_id,
      'sede_id' => $this->sede_id,
      'plate' => $this->vehicle ? $this->vehicle->plate : "-",
      'vehicle' => new VehiclesResource($this->whenLoaded('vehicle')),
      'quotation_number' => $this->quotation_number,
      'subtotal' => (float)$this->subtotal,
      'discount_percentage' => (float)$this->discount_percentage,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'total_amount' => (float)$this->total_amount,
      'validity_days' => $this->validity_days,
      'quotation_date' => $this->quotation_date,
      'expiration_date' => $this->expiration_date,
      'observations' => $this->observations,
      'currency_id' => $this->currency_id,
      'currency' => $this->currency,
      'details' => ApOrderQuotationDetailsResource::collection($this->details),
      'advances' => ElectronicDocumentResource::collection($this->whenLoaded('advancesOrderQuotation')),
      'created_by' => $this->created_by,
      'created_by_name' => $this->createdBy ? $this->createdBy->name : null,
      'is_take' => (bool)$this->is_take,
      'area_id' => $this->area_id,
    ];
  }
}
