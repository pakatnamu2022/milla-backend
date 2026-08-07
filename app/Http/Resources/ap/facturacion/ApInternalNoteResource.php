<?php

namespace App\Http\Resources\ap\facturacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApInternalNoteResource extends JsonResource
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
      'number' => $this->number,
      'work_order_id' => $this->work_order_id,
      'created_date' => $this->created_date?->format('Y-m-d'),
      'closed_date' => $this->closed_date?->format('Y-m-d'),
      'status' => $this->status,
      'dyn_series_in' => $this->dyn_series_in,
      'dyn_series_out' => $this->dyn_series_out,
      'is_accounted_in' => $this->is_accounted_in,
      'is_accounted_out' => $this->is_accounted_out,
      'migration_status' => $this->migration_status,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}