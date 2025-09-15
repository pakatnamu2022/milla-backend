<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'ubigeo' => $this->ubigeo,
      'province_id' => $this->province_id,
      'province' => $this->province->name,
      'department_id' => $this->province->department_id,
      'department' => $this->province->department->name,
    ];
  }
}
