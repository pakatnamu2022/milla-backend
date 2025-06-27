<?php

namespace App\Http\Services;

use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
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
}
