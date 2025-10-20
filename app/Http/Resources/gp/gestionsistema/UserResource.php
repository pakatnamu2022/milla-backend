<?php

namespace App\Http\Resources\gp\gestionsistema;

use App\Http\Resources\gp\maestroGeneral\SedeResource;
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
      'email' => $this->person->email,
      'foto_adjunto' => $photoBase64,
      'position' => $this->person?->position?->name,
      'empresa' => $this->person?->sede?->company?->abbreviation,
      'sede' => $this->person?->sede?->suc_abrev,
      'fecha_ingreso' => $this->person?->fecha_inicio,
      'role' => $this->role?->nombre,
      'role_id' => $this->role?->id,
      'subordinates' => $this->person?->subordinates->count() ?? 0,
      'sedes' => SedeResource::collection($this->sedes),
    ];
  }
}
