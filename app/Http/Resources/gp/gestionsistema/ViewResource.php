<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submodule' => (bool)$this->submodule,
            'descripcion' => $this->descripcion,
            'slug' => $this->slug,
            'route' => $this->route,
            'ruta' => $this->ruta,
            'icono' => $this->icono,
            'icon' => $this->icon,
            'parent' => $this->parent?->descripcion,
            'company' => $this->company?->name,
            'padre' => $this->padre?->descripcion,
            'subPadre' => $this->subPadre?->descripcion,
            'hijo' => $this->hijo?->descripcion,
            'parent_id' => $this->parent_id,
            'company_id' => $this->company_id,
            'idPadre' => $this->idPadre,
            'idSubPadre' => $this->idSubPadre,
            'idHijo' => $this->idHijo,
        ];
    }
}
