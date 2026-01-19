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
    $data = $request->validated();
    return response()->json($this->service->store($data));
  }

  public function show($id)
  {
    try {
      return response()->json($this->service->find($id));
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }

  public function update(UpdateEquipmentTypeRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
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
}
