<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApModelsVnSyncLogResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'model_vn_id'      => $this->model_vn_id,
      'model'            => $this->whenLoaded('model', fn() => [
        'id'         => $this->model->id,
        'version'    => $this->model->version,
        'model_year' => $this->model->model_year,
        'fuel_id'    => $this->model->fuel_id,
      ]),
      'code'             => $this->code,
      'status'           => $this->status,
      'proceso_estado'   => $this->proceso_estado,
      'dynamics_payload' => $this->dynamics_payload,
      'error_message'    => $this->error_message,
      'attempts'         => $this->attempts,
      'last_attempt_at'  => $this->last_attempt_at,
      'completed_at'     => $this->completed_at,
      'created_at'       => $this->created_at,
      'updated_at'       => $this->updated_at,
    ];
  }
}
