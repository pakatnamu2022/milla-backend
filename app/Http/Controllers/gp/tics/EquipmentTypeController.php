<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentTypeRequest;
use App\Http\Requests\UpdateEquipmentTypeRequest;
use App\Http\Services\gp\tics\EquipmentTypeService;
use App\Models\gp\tics\EquipmentType;
use Illuminate\Http\Request;

class EquipmentTypeController extends Controller
{
    protected EquipmentTypeService $service;

    public function __construct(EquipmentTypeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->list($request);
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
