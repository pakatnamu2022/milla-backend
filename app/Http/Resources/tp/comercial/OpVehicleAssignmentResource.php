<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpVehicleAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'tracto_id' => $this->tracto_id,
            'tractor' => $this->tractor ? $this->tractor->placa : null,
            'driver_id' => $this->conductor_id,
            'driver' => $this->driver ? $this->driver->nombre_completo : null,
            'status_deleted' => $this->status_deleted

        ];
    }
}
