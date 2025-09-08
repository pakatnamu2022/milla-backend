<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyBranchResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'abbreviation' => $this->abbreviation,
      'address' => $this->address,
      'company_id' => $this->company_id,
      'district_id' => $this->district_id,
      'province_id' => $this->province_id,
      'department_id' => $this->department_id,
      'status' => $this->status,
      'company' => $this->company->name,
      'district' => $this->district->name,
      'province' => $this->province->name,
      'department' => $this->department->name,
    ];
  }
}
