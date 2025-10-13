<?php

namespace App\Http\Resources\gp\gestionsistema;

use App\Http\Resources\gp\maestroGeneral\SedeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSedeResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'sede_id' => $this->sede_id,
      'status' => $this->status,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'user' => new UserResource($this->whenLoaded('user')),
      'sede' => new SedeResource($this->whenLoaded('sede')),
    ];
  }
}
