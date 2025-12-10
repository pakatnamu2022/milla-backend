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
            'expense_date' => $this->expense_date,
            'concept' => $this->concept,
            'receipt_amount' => (float) $this->receipt_amount,
            'company_amount' => (float) $this->company_amount,
            'employee_amount' => (float) $this->employee_amount,
            'receipt_type' => $this->receipt_type,
            'receipt_number' => $this->receipt_number,
            'receipt_path' => $this->receipt_path,
            'notes' => $this->notes,
            'validated' => (bool) $this->validated,
            'validated_at' => $this->validated_at,

            // Relations
            'expense_type' => $this->whenLoaded('expenseType', function () {
                return new ExpenseTypeResource($this->expenseType);
            }),

            'validated_by' => $this->whenLoaded('validator', function () {
                return $this->validator ? [
                    'id' => $this->validator->id,
                    'name' => $this->validator->name ?? $this->validator->fullname ?? null,
                ] : null;
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
