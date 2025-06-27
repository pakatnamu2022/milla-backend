<?php

namespace App\Http\Controllers;

use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Services\EquipmentService;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    public function index(Request $request)
    {
        return $this->equipmentService->list($request);
    }

    public function store(StoreEquipmentRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->equipmentService->store($data));
    }

    public function show($id)
    {
        // Logic to show specific equipment
    }

    public function update(Request $request, $id)
    {
        // Logic to update specific equipment
    }

    public function destroy($id)
    {
        // Logic to delete specific equipment
    }
}
