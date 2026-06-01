<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollLoanExtraDiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'loan_id'      => $this->loan_id,
            'loan'         => $this->loan?->reason,
            'concept_type' => $this->concept_type,
            'amount'       => $this->amount,
            'month_number' => $this->month_number,
            'applied'      => $this->applied,
            'status'       => $this->status,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}