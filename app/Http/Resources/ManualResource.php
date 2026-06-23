<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'vista_id'     => $this->vista_id,
            'company_slug' => $this->company_slug,
            'module_slug'  => $this->module_slug,
            'title'        => $this->title,
            'description'  => $this->description,
            's3_url'       => $this->digitalFile?->url,
            'order'        => $this->order,
            'created_at'   => $this->created_at,
        ];
    }
}
