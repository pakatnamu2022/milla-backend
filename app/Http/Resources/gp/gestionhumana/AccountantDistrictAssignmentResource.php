<?php

namespace App\Http\Resources\gp\gestionhumana;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Resources\gp\gestionsistema\DistrictResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountantDistrictAssignmentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'worker' => $this->worker ? new WorkerResource($this->worker) : null,
      'district' => $this->district ? new DistrictResource($this->district) : null,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
