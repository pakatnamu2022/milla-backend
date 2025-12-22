<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionsistema\DistrictResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerDiemRateResource extends JsonResource
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
      'per_diem_policy_id' => $this->per_diem_policy_id,
      'district_id' => $this->district_id,
      'per_diem_category_id' => $this->per_diem_category_id,
      'expense_type_id' => $this->expense_type_id,
      'daily_amount' => $this->daily_amount,
      'active' => $this->active,
      'policy' => new PerDiemPolicyResource($this->policy),
      'district' => new DistrictResource($this->district),
      'category' => new PerDiemCategoryResource($this->category),
      'expense_type' => new ExpenseTypeResource($this->expenseType),
    ];
  }
}
