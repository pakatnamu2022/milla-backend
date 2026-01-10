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
use Throwable;

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
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTravelControlRequest $request)
  {
    try {
      $data = $request->validated();
      return response()->json($this->service->store($data), 201);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return response()->json($this->service->show($id));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateTravelControlRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->update($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return response()->json($this->service->destroy($id));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function startRoute(StartRouteRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      return response()->json($this->service->startRoute($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function endRoute(EndRouteRequest $request, $id)
  {
    try {

      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->endRoute($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function fuelRecord(FuelRecordRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->fuelRecord($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function changeState(ChangeStateRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      return response()->json($this->service->changeState($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function driverRecords($id)
  {
    try {
      return response()->json([
        'data' => $this->service->driverRecords($id)
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  //validar datos
  public function validateMileage(Request $request, $vehicle_id)
  {
    try {
      return response()->json([
        'data' => $this->service->validateMileage($vehicle_id)
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function availableStates()
  {
    try {
      return response()->json([
        'data' => $this->service->availableStates()
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }

  }

}
