<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PotentialBuyersResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'registration_date' => $this->registration_date,
      'model' => $this->model,
      'version' => $this->version,
      'num_doc' => $this->num_doc,
      'name' => $this->name,
      'surnames' => $this->surnames,
      'phone' => $this->phone,
      'email' => $this->email,
      'campaign' => $this->campaign,
      'sede' => $this->sede->abreviatura,
      'vehicle_brand' => $this->vehicleBrand->name,
      'document_type' => $this->documentType->description,
      'income_sector' => $this->incomeSector->description,
    ];
  }
}
