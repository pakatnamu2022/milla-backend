<?php

namespace App\Http\Resources\gp\gestionsistema;

use App\Http\Resources\gp\gestionhumana\personal\WorkerCompleteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCompleteResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'partner_id' => $this->partner_id,
      'username' => $this->username,
      'role' => $this->role?->nombre,
      ...WorkerCompleteResource::profile($this->person),
    ];
  }
}
