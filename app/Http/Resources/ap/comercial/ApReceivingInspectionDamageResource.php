<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApReceivingInspectionDamageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'receiving_inspection_id' => $this->receiving_inspection_id,
            'damage_type'            => $this->damage_type,
            'x_coordinate'           => $this->x_coordinate,
            'y_coordinate'           => $this->y_coordinate,
            'description'            => $this->description,
            'photo_url'              => $this->photo_url,
            'created_at'             => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
