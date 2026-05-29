<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollLiquidationBbssResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'worker_id'  => $this->worker_id,
            'worker'     => $this->worker?->nombre_completo,
            'period_id'  => $this->period_id,
            'period'     => $this->period?->name,
            'amount'     => $this->amount,
            'type_id'    => $this->type_id,
            'type'       => $this->type?->description,
            'status'     => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}