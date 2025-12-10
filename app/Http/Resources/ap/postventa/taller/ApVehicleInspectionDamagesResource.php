<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleInspectionDamagesResource extends JsonResource
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
            'vehicle_inspection_id' => $this->vehicle_inspection_id,
            'damage_type' => $this->damage_type,
            'x_coordinate' => $this->x_coordinate,
            'y_coordinate' => $this->y_coordinate,
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}