<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpGoalTravelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            //RESOURCE

            'id' => $this->id,
            'date' => $this->fecha,
            'total' => $this->total,
            'driver_goal' => $this->meta_conductor,
            'vehicle_goal' => $this->meta_vehiculo,
            'total_units' => $this->total_unidades,
            'status_deleted' => $this->status_deleted
        ];
    }
}
