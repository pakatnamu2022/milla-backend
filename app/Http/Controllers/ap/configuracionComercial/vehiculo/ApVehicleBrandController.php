<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApVehicleBrandRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApVehicleBrandRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApVehicleBrandRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApVehicleBrandService;

class ApVehicleBrandController extends Controller
{
    protected ApVehicleBrandService $service;

    public function __construct(ApVehicleBrandService $service)
    {
        $this->service = $service;
    }

    public function index(IndexApVehicleBrandRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreApVehicleBrandRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            return $this->success($this->service->show($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateApVehicleBrandRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            return $this->service->destroy($id);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
