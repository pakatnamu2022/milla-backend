<?php

namespace App\Http\Resources\ap\comercial;

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
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relaciones
            'worker' => $this->worker ? [
                'id' => $this->worker->id,
                'name' => $this->worker->nombre . ' ' . $this->worker->apellido_paterno . ' ' . $this->worker->apellido_materno,
            ] : null,
            'client' => $this->client ? [
                'id' => $this->client->id,
                'full_name' => $this->client->full_name,
                'num_doc' => $this->client->num_doc,
            ] : null,
            'family' => $this->family?->description,
            'opportunity_type' => $this->opportunityType?->description,
            'client_status' => $this->clientStatus?->description,
            'opportunity_status' => $this->opportunityStatus?->description,
            'actions' => OpportunityActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
