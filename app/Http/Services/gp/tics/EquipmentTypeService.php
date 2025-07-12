<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\EquipmentResource;
use App\Http\Resources\gp\tics\EquipmentTypeResource;
use App\Http\Services\BaseService;
use App\Models\gp\tics\EquipmentType;
use Exception;
use Illuminate\Http\Request;

class EquipmentTypeService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EquipmentType::where('status_deleted', 1),
            $request,
            EquipmentType::filters,
            EquipmentType::sorts,
            EquipmentTypeResource::class,
        );
    }

    public function store($data)
    {
        $equipmentType = EquipmentType::create($data);
        return new EquipmentTypeResource(EquipmentType::find($equipmentType->id));
    }

    public function find($id)
    {
        $equipmentType = EquipmentType::find($id);
        if (!$equipmentType) {
            throw new Exception('Tipo de Equipo no encontrado');
        }
        return new EquipmentTypeResource($equipmentType);
    }

    public function update($data)
    {
        $equipment = EquipmentType::find($data['id']);
        if (!$equipment) {
            throw new Exception('Equipo no encontrado');
        }
        $equipment->update($data);
        return new EquipmentResource($equipment);
    }
}
