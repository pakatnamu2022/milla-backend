<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneLineWorkerResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'phone_line_id' => $this->phone_line_id,
      'worker_id' => $this->worker_id,
      'worker_name' => $this->worker?->nombre_completo,
      'assigned_at' => $this->assigned_at,
      'unassigned_at' => $this->unassigned_at,
    ];
  }
}
