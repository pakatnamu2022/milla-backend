<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
    ];
  }
}
