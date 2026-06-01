<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\tp\comercial\DriverService;
use App\Models\tp\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DriverController extends Controller
{
    protected $service;

    public function __construct(DriverService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            return $this->service->index($request);
        } catch(Throwable $th) {
             Log::error('DriverController@index error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            return $this->service->show($driver);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function stats()
    {
        try {
            return $this->service->stats();
        } catch(Throwable $th) {
             Log::error('DriverController@stats error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function byDeviceId($deviceId)
    {
        try {
            return $this->service->byDeviceId($deviceId);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function assignDevice(Request $request, $id)
    {
        try {
            $driver = Driver::findOrFail($id);
            $request->validate(['device_id' => 'required|string']);
            return $this->service->assignDevice($driver, $request->device_id);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function removeDevice($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            return $this->service->removeDevice($driver);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function refreshStatus($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            return $this->service->refreshStatus($driver);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}