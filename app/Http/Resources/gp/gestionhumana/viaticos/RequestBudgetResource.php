<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestBudgetResource extends JsonResource
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
      'per_diem_request_id' => $this->per_diem_request_id,
      'expense_type' => $this->expenseType ? ExpenseTypeResource::make($this->expenseType) : null,
      'daily_amount' => (float)$this->daily_amount,
      'days' => $this->days,
      'total' => (float)$this->total,
    ];
  }
}
