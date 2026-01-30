<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
  /**
   * @param Request $request
   * @return array
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'role_id' => $this->role_id,
      'user_id' => $this->user_id,
      'role' => $this->role ? new RoleResource($this->role) : null,
      'user' => $this->user ? new UserResource($this->user) : null,
    ];
  }
}
