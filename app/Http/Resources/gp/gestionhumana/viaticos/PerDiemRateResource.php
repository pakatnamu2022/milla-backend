<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionsistema\DistrictResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerDiemRateResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'per_diem_policy_id' => $this->per_diem_policy_id,
      'per_diem_policy_name' => $this->policy?->name,
      'district_id' => $this->district_id,
      'district_name' => $this->district?->name,
      'per_diem_category_id' => $this->per_diem_category_id,
      'per_diem_category_name' => $this->category?->name,
      'expense_type_id' => $this->expense_type_id,
      'expense_type_name' => $this->expenseType?->name,
      'daily_amount' => $this->daily_amount ?? 0,
      'active' => $this->active,
    ];
  }
}
