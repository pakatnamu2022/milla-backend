<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreDriverLocationRequest;
use App\Http\Requests\tp\comercial\IndexDriverLocationRequest;
use App\Http\Services\tp\comercial\DriverLocationService;
use Throwable;

class DriverLocationController extends Controller
{
    protected $service;

    public function __construct(DriverLocationService $service)
    {
        $this->service = $service;
    }

    public function index(IndexDriverLocationRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreDriverLocationRequest $request)
    {
        try {
            $location = $this->service->registerLocation($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Ubicacion registrada correctamente',
                'data' => $location
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'success'=> false,
                'message'=> $th->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        try{
            return $this->service->show($id);
        }catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function latest()
    {
        try{
            return $this->service->latest();
        }catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }


}