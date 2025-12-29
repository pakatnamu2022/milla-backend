<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerDiemExpenseResource extends JsonResource
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
      'expense_type_id' => $this->expense_type_id,
      'expense_date' => $this->expense_date,
      'receipt_amount' => (float)$this->receipt_amount,
      'company_amount' => (float)$this->company_amount,
      'employee_amount' => (float)$this->employee_amount,
      'receipt_type' => $this->receipt_type,
      'receipt_number' => $this->receipt_number,
      'receipt_path' => $this->receipt_path,
      'notes' => $this->notes,
      'business_name' => $this->business_name,
      'ruc' => $this->ruc,
      'is_company_expense' => (bool)$this->is_company_expense,
      'validated' => (bool)$this->validated,
      'validated_at' => $this->validated_at,
      'rejected' => (bool)$this->rejected,
      'rejected_at' => $this->rejected_at,
      'rejection_reason' => $this->rejection_reason,

      // Relations
      'expense_type' => $this->expenseType ? new ExpenseTypeResource($this->expenseType) : null,
      'validated_by' => $this->validator?->name ?? $this->validator?->fullname ?? null,
      'rejected_by' => $this->rejector?->nombre_completo ?? null,
    ];
  }
}
