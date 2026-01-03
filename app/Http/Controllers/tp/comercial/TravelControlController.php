<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\ChangeStateRequest;
use App\Http\Requests\tp\comercial\EndRouteRequest;
use App\Http\Requests\tp\comercial\FuelRecordRequest;
use App\Http\Requests\tp\comercial\StartRouteRequest;
use App\Http\Requests\tp\comercial\StoreTravelControlRequest;
use App\Http\Requests\tp\comercial\UpdateTravelControlRequest;
use App\Http\Services\tp\comercial\TravelControlService;
use Illuminate\Http\Request;

class TravelControlController extends Controller
{
    
    protected TravelControlService $service;

    public function __construct(TravelControlService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            return response()->json($this->service->list($request));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de viajes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreTravelControlRequest $request)
    {
        try {
            $data = $request->validated();
            return response()->json($this->service->store($data), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el viaje',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try{
            return response()->json($this->service->show($id));

        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(UpdateTravelControlRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;
            return response()->json($this->service->update($data));

        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try{
            return response()->json($this->service->destroy($id));

        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function startRoute(StartRouteRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;

            return response()->json($this->service->startRoute($data));
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function endRoute(EndRouteRequest $request, $id)
    {
        try{

            $data = $request->validated();
            $data['id'] = $id;
            return response()->json($this->service->endRoute($data));

        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function fuelRecord(FuelRecordRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;
            return response()->json($this->service->fuelRecord($data));

        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function changeState(ChangeStateRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;

            return response()->json($this->service->changeState($data));

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function driverRecords($id)
    {
        try{
            return response()->json([
                'data' => $this->service->driverRecords($id)
            ]);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al obtener los registros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //validar datos
    public function validateMileage(Request $request, $vehicle_id)
    {
        try{
            return response()->json([
                'data' => $this->service->validateMileage($vehicle_id)
            ]);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al validar kilometraje',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function availableStates()
    {
        return response()->json([
            'data' => $this->service->availableStates()
        ]);
    }

}