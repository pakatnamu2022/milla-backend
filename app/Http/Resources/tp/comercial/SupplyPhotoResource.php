<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supply_control_id' => $this->supply_control_id,
            'digital_file_id' => $this->digital_file_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'uploaded_at' => $this->uploaded_at?->toISOString(),
            'uploaded_by' => $this->uploaded_by,
            'url' => $this->digitalFile?->url ?? ($this->file_path ? asset('/storage/'.$this->file_path) : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
