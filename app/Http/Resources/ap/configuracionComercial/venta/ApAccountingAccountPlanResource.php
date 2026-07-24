<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use App\Http\Resources\gp\maestroGeneral\SunatConceptsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAccountingAccountPlanResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                               => $this->id,
      'account'                          => $this->account,
      'code_dynamics'                    => $this->code_dynamics,
      'description'                      => $this->description,
      'is_detraction'                    => $this->is_detraction,
      'detraction_percentage'            => $this->detraction_percentage,
      'sunat_concept_detraction_type_id' => $this->sunat_concept_detraction_type_id,
      'detraction_type'                  => SunatConceptsResource::make($this->detractionType),
      'status'                           => $this->status,
      'enable_commercial'                => $this->enable_commercial,
      'enable_after_sales'               => $this->enable_after_sales,
    ];
  }
}
