<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLoanExtraDiscountResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollLoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'concept_id'          => $this->concept_id,
            'concept'             => $this->concept?->description,
            'worker_id'           => $this->worker_id,
            'worker'              => $this->worker?->nombre_completo,
            'delivery_date'       => $this->delivery_date,
            'reason'              => $this->reason,
            'payment_start'       => $this->payment_start,
            'loan_amount'         => $this->loan_amount,
            'installments_count'  => $this->installments_count,
            'installment_amount'  => $this->installment_amount,
            'status'              => $this->status,
            'extra_discounts'     => PayrollLoanExtraDiscountResource::collection($this->whenLoaded('extraDiscounts')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}