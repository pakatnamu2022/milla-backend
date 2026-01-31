<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelephonePlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
