<?php

namespace App\Http\Resources\ap\comercial;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
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
      'worker_id' => $this->worker_id,
      'client_id' => $this->client_id,
      'family_id' => $this->family_id,
      'opportunity_type_id' => $this->opportunity_type_id,
      'client_status_id' => $this->client_status_id,
      'opportunity_status_id' => $this->opportunity_status_id,
      'is_closed' => $this->is_closed,
      'comment' => $this->comment,

      // Relaciones
      'worker' => new WorkerResource($this->worker),
      'client' => new BusinessPartnersResource($this->client),
      'family' => new ApFamiliesResource($this->family),
      'opportunity_type' => $this->opportunityType?->description,
      'client_status' => $this->clientStatus?->description,
      'opportunity_status' => $this->opportunityStatus?->description,
      'actions' => OpportunityActionResource::collection($this->actions),
    ];
  }
}
