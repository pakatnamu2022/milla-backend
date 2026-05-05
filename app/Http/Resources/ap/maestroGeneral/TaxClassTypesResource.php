<?php

namespace App\Http\Resources\ap\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxClassTypesResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'dyn_code' => $this->dyn_code,
      'description' => $this->description,
      'tax_class' => $this->tax_class,
      'igv' => $this->igv,
      'type_detraction_id' => $this->type_detraction_id,
      'type_detraction' => $this->whenLoaded('typeDetraction', fn() => [
        'id' => $this->typeDetraction->id,
        'description' => $this->typeDetraction->description,
        'code_nubefact' => $this->typeDetraction->code_nubefact,
      ]),
      'type' => $this->type,
      'status' => $this->status,
    ];
  }
}
