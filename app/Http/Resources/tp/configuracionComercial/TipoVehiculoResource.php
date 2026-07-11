<?php

namespace App\Http\Resources\tp\configuracionComercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoVehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'status_deleted' => $this->status_deleted,
            'write_id' => $this->write_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Información adicional opcional
            'creator_name' => $this->whenLoaded('creator', function() {
                return $this->creator ? $this->creator->name : null;
            }),
        ];
    }
}