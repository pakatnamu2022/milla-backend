<?php

namespace App\Http\Controllers;

use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Services\EquipmentService;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    protected EquipmentService $service;

    public function __construct(EquipmentService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->list($request);
    }

    public function store(StoreEquipmentRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->service->store($data));
    }

    public function show($id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateEquipmentRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id; // Add the ID to the data for updating
            return response()->json($this->service->update($data));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            return $this->service->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function useStateGraph()
    {
        return response()->json(
            $this->service->useStateGraph()
        );
    }

    public function sedeGraph()
    {
        return response()->json(
            $this->service->sedeGraph()
        );
    }
}
