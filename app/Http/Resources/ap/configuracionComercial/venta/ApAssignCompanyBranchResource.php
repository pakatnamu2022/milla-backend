<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignCompanyBranchResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'company_branch_id' => $this->id,
      'abbreviation' => $this->abbreviation,
      'workers' => $this->workers->map(fn($worker) => [
        'id' => $worker->id,
        'name' => $worker->nombre_completo,
      ]),
    ];
  }
}
