<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpFreightResource extends JsonResource
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
            'customer_id' => $this->cliente_id,
            'customer' => $this->customer ? $this->customer->nombre_completo : null,
            'startPoint_id' => $this->idorigen,
            'startPoint' => $this->startPoint ? $this->startPoint->descripcion : null,
            'endPoint' => $this->endPoint ? $this->endPoint->descripcion : null,
            'endPoint_id' => $this->iddestino,
            'tipo_flete' => $this->tipo_flete,
            'freight' => $this->flete,
            'status_deleted' => $this->status_deleted
        ];
    }
}
