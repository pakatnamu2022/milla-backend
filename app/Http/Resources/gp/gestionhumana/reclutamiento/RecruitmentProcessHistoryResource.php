<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentProcessHistoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'             => $this->id,
      'accion'         => $this->accion,
      'detalle'        => $this->detalle,
      'dias_agregados' => $this->dias_agregados,
      'usuario'        => $this->whenLoaded('user', fn() => $this->user?->name),
      'created_at'     => $this->created_at,
    ];
  }
}
