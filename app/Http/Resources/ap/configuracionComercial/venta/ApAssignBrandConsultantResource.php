<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignBrandConsultantResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'year' => $this->year,
      'month' => $this->month,
      'period' => $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT),
      'sales_target' => $this->sales_target,
      'status' => $this->status,
      'brand_id' => $this->brand->id,
      'brand' => $this->brand->name,
//      'company_branch_id' => $this->companyBranch->id,
//      'company_branch' => $this->companyBranch->abbreviation,
      'sede_id' => $this->sede->id,
      'sede' => $this->sede->abreviatura,
      'worker_id' => $this->worker->id,
      'worker' => $this->worker->nombre_completo,
    ];
  }
}
