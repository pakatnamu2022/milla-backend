<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreDriverLocationConfigurationRequest;
use App\Http\Requests\tp\comercial\UpdateDriverLocationConfigurationRequest;
use App\Http\Services\tp\comercial\DriverLocationConfigurationService;
use Throwable;

class DriverLocationConfigurationController extends Controller
{
    protected $service;

    public function __construct(DriverLocationConfigurationService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return $this->service->list();
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreDriverLocationConfigurationRequest $request)
    {
        try {
            $result = $this->service->store($request->validated());
            return response()->json($result, 201);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $result = $this->service->show($id);
            return response()->json($result);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateDriverLocationConfigurationRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            $result = $this->service->update($data);
            return response()->json($result);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->service->destroy($id);
            return response()->json($result);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function getPublic($key)
    {
        try {
            $result = $this->service->getPublic($key);
            return response()->json($result);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}