<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityActionResource extends JsonResource
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
            'opportunity_id' => $this->opportunity_id,
            'action_type_id' => $this->action_type_id,
            'action_contact_type_id' => $this->action_contact_type_id,
            'datetime' => $this->datetime?->format('Y-m-d H:i:s'),
            'description' => $this->description,
            'result' => $this->result,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relaciones
            'action_type' => $this->actionType?->description,
            'action_contact_type' => $this->actionContactType?->description,
            'opportunity' => $this->when($this->relationLoaded('opportunity'), function () {
                return [
                    'id' => $this->opportunity->id,
                    'client' => $this->opportunity->client?->full_name,
                ];
            }),
        ];
    }
}
