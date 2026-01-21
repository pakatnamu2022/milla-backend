<?php

namespace App\Http\Resources\gp\gestionhumana;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountantDistrictAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Worker (accountant) data
            'worker' => $this->worker ? [
                'id' => $this->worker->id,
                'full_name' => $this->worker->nombre_completo,
                'vat' => $this->worker->vat,
                'email' => $this->worker->email,
                'position' => $this->worker->position ? [
                    'id' => $this->worker->position->id,
                    'name' => $this->worker->position->name,
                ] : null,
                'sede' => $this->worker->sede ? [
                    'id' => $this->worker->sede->id,
                    'name' => $this->worker->sede->abreviatura,
                ] : null,
            ] : null,

            // District data
            'district' => $this->district ? [
                'id' => $this->district->id,
                'name' => $this->district->name,
                'ubigeo' => $this->district->ubigeo,
                'province' => $this->district->province ? [
                    'id' => $this->district->province->id,
                    'name' => $this->district->province->name,
                    'department' => $this->district->province->department ? [
                        'id' => $this->district->province->department->id,
                        'name' => $this->district->province->department->name,
                    ] : null,
                ] : null,
            ] : null,

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
