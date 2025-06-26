<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
//            RESOURCE
            'id' => $this->id,
            'equipo' => $this->equipo,
            'tipo_equipo' => $this->equipmentType ? $this->equipmentType->name : null,
            'marca_modelo' => $this->marca_modelo,
            'serie' => $this->serie,
            'status' => $this->status ? $this->status->estado : null,
            'estado_uso' => $this->estado_uso,
            'detalle' => $this->detalle,

//            DETALLES
            'ram' => $this->ram,
            'almacenamiento' => $this->almacenamiento,
            'procesador' => $this->procesador,
            'stock_actual' => $this->stock_actual,
            'pertenece_sede' => $this->pertenece_sede,

//            FOREIGN KEYS
            'tipo_equipo_id' => $this->tipo_equipo_id,
            'sede_id' => $this->sede_id,
            'status_id' => $this->status_id,
            'status_deleted' => $this->status_deleted,


        ];
    }
}
