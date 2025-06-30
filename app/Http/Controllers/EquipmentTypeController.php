<?php

namespace App\Http\Controllers;

use App\Http\Services\EquipmentTypeService;
use App\Models\EquipmentType;
use App\Http\Requests\StoreEquipmentTypeRequest;
use App\Http\Requests\UpdateEquipmentTypeRequest;
use Illuminate\Http\Request;

class EquipmentTypeController extends Controller
{
    protected EquipmentTypeService $equipmentTypeService;

    public function __construct(EquipmentTypeService $equipmentTypeService)
    {
        $this->equipmentTypeService = $equipmentTypeService;
    }

    public function index(Request $request)
    {
        return $this->equipmentTypeService->list($request);
    }

    public function store(StoreEquipmentTypeRequest $request)
    {

    }

    public function show(EquipmentType $equipmentType)
    {
        //
    }

    public function edit(EquipmentType $equipmentType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEquipmentTypeRequest $request, EquipmentType $equipmentType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EquipmentType $equipmentType)
    {
        //
    }
}
