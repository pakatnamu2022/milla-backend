<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TpTravelPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        
        if ($this->resource instanceof \Illuminate\Http\Resources\MissingValue) {
            return [];
        }

        return [
            'id' => $this->id,
            'dispatch_id' => $this->dispatch_id,
            'driver_id' => $this->driver_id,
            'photo_type' => $this->photo_type,
            'file_name' => $this->digitalFile->name ?? null,
            'path' => $this->path,
            'public_url' => $this->digitalFile->url ?? null,
            'mime_type' => $this->digitalFile->mimeType ?? null,
            'digital_file_id' => $this->digital_file_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'user_agent' => $this->user_agent,
            'operating_system' => $this->operating_system,
            'browser' => $this->browser,
            'device_model' => $this->device_model,



            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'has_geolocation' => !empty($this->latitude) && !empty($this->longitude),
            'formatted_Date' => $this->created_at?->format('d/m/Y H:i'),
            'digital_file' => $this->whenLoaded('digitalFile', function() {
                return [
                    'id' => $this->digitalFile->id,
                    'name' => $this->digitalFile->name,
                    'url' => $this->digitalFile->url,
                    'mime_type' => $this->digitalFile->mimeType,
                    'created_at' => $this->digitalFile->created_at
                ];
            })
        ];
    }
}