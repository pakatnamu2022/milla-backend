<?php

namespace App\Http\Services;

use App\Http\Resources\EquipmentResource;
use App\Http\Resources\SedeResource;
use App\Models\Equipment;
use App\Models\Sede;
use Exception;
use Illuminate\Http\Request;

class SedeService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Sede::where('status_deleted', 1)->whereNotNull('empresa_id')->orderBy('empresa_id', 'asc'),
            $request,
            Sede::filters,
            Sede::sorts,
            SedeResource::class,
        );
    }

    public function store($data)
    {
        $sede = Sede::create($data);
        return new SedeResource(Sede::find($sede->id));
    }

    public function find($id)
    {
        $sede = Sede::find($id);
        if (!$sede) {
            throw new Exception('Sede no encontrada');
        }
        return new SedeResource($sede);
    }

    public function update($data)
    {
        $equipment = Sede::find($data['id']);
        if (!$equipment) {
            throw new Exception('Sede no encontrada');
        }
        $equipment->update($data);
        return new EquipmentResource($equipment);
    }
}
