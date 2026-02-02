<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexTelephonePlanRequest;
use App\Http\Requests\gp\tics\StoreTelephonePlanRequest;
use App\Http\Requests\gp\tics\UpdateTelephonePlanRequest;
use App\Http\Services\gp\tics\TelephonePlanService;

class TelephonePlanController extends Controller
{
    protected TelephonePlanService $service;

    public function __construct(TelephonePlanService $service)
    {
        $this->service = $service;
    }

    public function index(IndexTelephonePlanRequest $request)
    {
        return $this->service->list($request);
    }

    public function store(StoreTelephonePlanRequest $request)
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

    public function update(UpdateTelephonePlanRequest $request, $id)
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
