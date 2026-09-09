<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyControlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle' => [
                'id' => $this->vehicle?->id,
                'placa' => $this->vehicle?->placa,
                'modelo' => $this->vehicle?->modelo,
                'marca' => $this->vehicle?->marca,
            ],
            'driver' => [
                'id' => $this->driver?->id,
                'nombre_completo' => $this->driver?->nombre_completo,
                'vat' => $this->driver?->vat,
            ],
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'mileage' => (float) $this->mileage,
            'gallons' => (float) $this->gallons,
            'is_base' => (bool) $this->is_base,
            'is_base_label' => $this->is_base ? 'En Base' : 'Fuera de Base',
            'photo' => new SupplyPhotoResource($this->whenLoaded('photo')),
            'photo_id' => $this->photo_id,
            'recorded_at' => $this->recorded_at?->toISOString(),
            'recorded_at_formatted' => $this->recorded_at?->format('d/m/Y H:i'),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
