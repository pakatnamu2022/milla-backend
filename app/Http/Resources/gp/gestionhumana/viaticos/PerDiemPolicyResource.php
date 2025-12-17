<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerDiemPolicyResource extends JsonResource
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
      'version' => $this->version,
      'name' => $this->name,
      'description' => $this->description,
      'effective_from' => $this->effective_from,
      'effective_to' => $this->effective_to,
      'is_current' => (bool)$this->is_current,
      'rates_count' => $this->when(isset($this->rates_count), $this->rates_count),
      'document_path' => $this->document_path,
      'notes' => $this->notes,
    ];
  }
}
