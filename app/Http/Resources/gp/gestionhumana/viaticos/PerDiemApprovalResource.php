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
    /**
     * {
     * "id": 37,
     * "per_diem_request_id": 20,
     * "approver_id": 1,
     * "approver_type": 0,
     * "status": "approved",
     * "comments": "Aprobado. Viaje justificado.",
     * "approved_at": "2025-12-10T11:50:02.000000Z",
     * "created_at": "2025-12-17T11:50:02.000000Z",
     * "updated_at": "2025-12-17T11:50:02.000000Z",
     * "deleted_at": null
     * }
     */
    return [
      'id' => $this->id,
      'per_diem_request_id' => $this->per_diem_request_id,
      'approver_id' => $this->approver_id,
      'approver_type' => $this->approver_type,
      'approver' => $this->approver ? $this->approver->nombre_completo : null,
      'status' => $this->status,
      'comments' => $this->comments,
      'approved_at' => $this->approved_at,
    ];
  }
}
