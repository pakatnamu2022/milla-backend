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
      'full_name' => $this->full_name,
      'phone' => $this->phone ?? null,
      'email' => $this->email ?? null,
      'campaign' => $this->campaign,
      'worker_id' => $this->worker_id,
      'worker' => $this->worker->nombre_completo ?? null,
      'sede_id' => $this->sede_id,
      'vehicle_brand_id' => $this->vehicle_brand_id,
      'document_type_id' => $this->document_type_id,
      'income_sector_id' => $this->income_sector_id,
      'type' => $this->type,
      'sede' => $this->sede->abreviatura,
      'vehicle_brand' => $this->vehicleBrand->name,
      'document_type' => $this->documentType->description,
      'income_sector' => $this->incomeSector->description,
      'area_id' => $this->area_id,
      'district' => $this->sede->district->name ?? null,
      'status_num_doc' => $this->status_num_doc,
    ];
  }
}
