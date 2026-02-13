<?php

namespace App\Http\Resources\gp\gestionsistema;

use App\Http\Resources\gp\maestroGeneral\SedeResource;
use App\Models\GeneralMaster;
use App\Models\gp\gestionsistema\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $photoBase64 = null;

    if ($this->person?->foto_adjunto) {
      $path = $this->person->foto_adjunto;
      if (Storage::disk('general')->exists($path)) {
        $mime = Storage::disk('general')->mimeType($path);
        $content = Storage::disk('general')->get($path);
        $photoBase64 = "data:$mime;base64," . base64_encode($content);
      }
    }

    return [
      'id' => $this->id,
      'partner_id' => $this->partner_id,
      'name' => $this->name,
      'username' => $this->username,
      'email' => $this->person?->email,
      'foto_adjunto' => $photoBase64,
      'position' => $this->person?->position?->name,
      'empresa' => $this->person?->sede?->company?->abbreviation,
      'sede' => $this->person?->sede?->suc_abrev,
      'sede_id' => $this->person?->sede?->id,
      'shop_id' => $this->person?->sede?->shop_id,
      'fecha_ingreso' => $this->person?->fecha_inicio,
      'role' => $this->role?->nombre,
      'role_id' => $this->role?->id,
      'subordinates' => $this->person?->subordinates->count() ?? 0,
      'sedes' => SedeResource::collection($this->sedes),
      'verified_at' => $this->verified_at,
      'discount_percentage' => $this->getDiscountPercentageByPosition($this->person?->cargo_id),
    ];
  }

  /**
   * Obtener porcentaje de descuento según el cargo
   *
   * @param int|null $positionId
   * @return float|null
   */
  private function getDiscountPercentageByPosition(?int $positionId): ?float
  {
    if (!$positionId) {
      return null;
    }

    $generalMasterId = null;

    // Determinar qué general master usar según el cargo
    if (in_array($positionId, Position::POSITION_GERENTE_PV_IDS)) {
      $generalMasterId = GeneralMaster::MANAGER_DISCOUNT_PERCENTAGE_PV_ID;
    } elseif (in_array($positionId, Position::POSITION_JEFE_PV_IDS)) {
      $generalMasterId = GeneralMaster::BOSS_DISCOUNT_PERCENTAGE_PV_ID;
    } elseif (in_array($positionId, Position::ASESOR_SERVICIO_PV_IDS)) {
      $generalMasterId = GeneralMaster::ADVISOR_DISCOUNT_PERCENTAGE_PV_ID;
    }

    // Si no corresponde a ningún cargo, retornar null
    if (!$generalMasterId) {
      return null;
    }

    // Buscar el porcentaje en GeneralMaster
    $generalMaster = GeneralMaster::find($generalMasterId);

    return $generalMaster ? (float) $generalMaster->value : null;
  }
}
