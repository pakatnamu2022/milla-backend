<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollLoanExtraDiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'loan_id'        => $this->loan_id,
            'scheduled_date' => $this->scheduled_date,
            'concept_type'   => $this->concept_type,
            'amount'         => $this->amount,
            'month_number'   => $this->month_number,
            'applied'        => $this->applied,
            'confirmed_by'   => $this->confirmed_by,
            'confirmed_by_name' => $this->confirmedBy?->name,
            'confirmed_at'   => $this->confirmed_at,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}