<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollFoodCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'period_id' => $this->period_id,
            'amount' => $this->amount,
            'applies' => $this->applies,
            'num_doc' => $this->num_doc,
            'full_name' => $this->full_name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'worker' => [
                'id' => $this->worker->id ?? null,
                'nombre_completo' => $this->worker->nombre_completo ?? null,
                'vat' => $this->worker->vat ?? null,
            ],
            'period' => [
                'id' => $this->period->id ?? null,
                'code' => $this->period->code ?? null,
                'description' => $this->period->description ?? null,
            ],
        ];
    }
}