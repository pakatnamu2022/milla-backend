<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\SedeResource;
use App\Http\Resources\gp\tics\EquipmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Sede;
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
