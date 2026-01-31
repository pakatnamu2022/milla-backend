<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentItemAssigmentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'asig_equipo_id' => $this->asig_equipo_id,
      'equipo_id' => $this->equipo_id,
      'equipment' => $this->equipment ? [
        'id' => $this->equipment->id,
        'equipo' => $this->equipment->equipo,
        'marca' => $this->equipment->marca,
        'modelo' => $this->equipment->modelo,
        'serie' => $this->equipment->serie,
        'tipo_equipo_id' => $this->equipment->tipo_equipo_id,
        'equipment_type' => $this->equipment->equipmentType?->name,
      ] : null,
      'observacion' => $this->observacion,
      'status_id' => $this->status_id,
      'observacion_dev' => $this->observacion_dev,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
