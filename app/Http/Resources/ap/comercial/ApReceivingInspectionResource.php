<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApReceivingInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'shipping_guide_id'    => $this->shipping_guide_id,
            'photo_front_url'      => $this->photo_front_url,
            'photo_back_url'       => $this->photo_back_url,
            'photo_left_url'       => $this->photo_left_url,
            'photo_right_url'      => $this->photo_right_url,
            'general_observations' => $this->general_observations,
            'inspected_by'         => $this->inspected_by,
            'inspected_by_name'    => $this->inspectedBy?->name,
            'created_at'           => $this->created_at?->format('Y-m-d H:i:s'),
            'damages'              => ApReceivingInspectionDamageResource::collection($this->whenLoaded('damages')),
        ];
    }
}
