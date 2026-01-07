<?php

namespace App\Http\Resources\GeneralMaster;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralMasterResource extends JsonResource
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
      'code' => $this->code,
      'description' => $this->description,
      'type' => $this->type,
      'value' => $this->value,
      'status' => $this->status,
    ];
  }
}
