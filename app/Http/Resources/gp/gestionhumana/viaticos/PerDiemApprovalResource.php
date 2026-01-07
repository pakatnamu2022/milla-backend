<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerDiemApprovalResource extends JsonResource
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
      'per_diem_request_id' => $this->per_diem_request_id,
      'approver_id' => $this->approver_id,
      'approver' => $this->approver ? [
        'id' => $this->approver->id,
        'full_name' => $this->approver->nombre_completo,
        'position' => $this->approver->position ? [
          'name' => $this->approver->position->name,
        ] : null,
      ] : null,
      'status' => $this->status,
      'comments' => $this->comments,
      'approved_at' => $this->approved_at,
    ];
  }
}
