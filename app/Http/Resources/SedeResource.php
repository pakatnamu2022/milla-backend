<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SedeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'localidad' => $this->localidad,
            'suc_abrev' => $this->company ? $this->company->abbreviation . ' | ' . $this->suc_abrev : $this->abreviatura,
            'abreviatura' => $this->abreviatura,
            'empresa_id' => $this->empresa_id,
            'ruc' => $this->ruc,
            'razon_social' => $this->razon_social,
            'direccion' => $this->direccion,
            'distrito' => $this->distrito,
            'provincia' => $this->provincia,
            'departamento' => $this->departamento,
            'web' => $this->web,
            'email' => $this->email,
            'logo' => $this->logo,
            'ciudad' => $this->ciudad,
            'info_labores' => $this->info_labores,
        ];
    }
}
