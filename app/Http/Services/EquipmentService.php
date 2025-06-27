<?php

namespace App\Http\Services;

use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Exception;
use Illuminate\Http\Request;

class EquipmentService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Equipment::where('status_deleted', 1),
            $request,
            Equipment::filters,
            Equipment::sorts,
            EquipmentResource::class,
        );
    }

    public function store($data)
    {
        $equipment = Equipment::create($data);
        return new EquipmentResource(Equipment::find($equipment->id));
    }

    public function find($id)
    {
        $equipment = Equipment::where('id', $id)
            ->where('status_deleted', 1)->first();
        if (!$equipment) {
            throw new Exception('Equipo no encontrado');
        }
        return $equipment;
    }

    public function show($id)
    {
        return new EquipmentResource($this->find($id));
    }

    public function update($data)
    {
        $equipment = $this->find($data['id']);
        $equipment->update($data);
        return new EquipmentResource($equipment);
    }

    public function destroy($id)
    {
        $equipment = $this->find($id);
        $equipment->status_deleted = 0;
        $equipment->save();
        return response()->json(['message' => 'Equipo eliminado correctamente']);
    }

//    GRAFICOS

    public function useStateGraph()
    {
        return Equipment::query()
            ->selectRaw("estado_uso, COUNT(*) as total")
            ->where('status_deleted', 1)
            ->groupBy('estado_uso')
            ->get();
    }

    public function sedeGraph()
    {
        return Equipment::selectRaw('companies.abbreviation as sede, COUNT(*) as total')
            ->join('config_sede', 'config_sede.id', '=', 'help_equipos.sede_id')
            ->join('companies', 'companies.id', '=', 'config_sede.empresa_id')
            ->where('help_equipos.status_deleted', 1)
            ->groupBy('companies.abbreviation')
            ->orderByDesc('total')
            ->get();
    }
}
