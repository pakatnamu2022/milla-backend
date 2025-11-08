<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\StoreApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\UpdateApVehicleDeliveryRequest;
use App\Http\Services\ap\comercial\ApVehicleDeliveryService;

class ApVehicleDeliveryController extends Controller
{
  protected ApVehicleDeliveryService $service;

  public function __construct(ApVehicleDeliveryService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleDeliveryRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleDeliveryRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
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

  public function update(UpdateApVehicleDeliveryRequest $request, $id)
  {
    try {
      $data = $request->all();
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

  /**
   * Envía la guía de remisión a SUNAT mediante Nubefact
   */
  public function sendToNubefact($id)
  {
    try {
      return $this->service->sendToNubefact($id);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Consulta el estado de la guía en Nubefact/SUNAT
   */
  public function queryFromNubefact($id)
  {
    try {
      return $this->service->queryFromNubefact($id);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Obtiene la información necesaria para crear la guía de remisión de entrega al cliente
   */
  public function getShippingGuideInfo($vehicleId)
  {
    try {
      return $this->service->getShippingGuideInfo($vehicleId);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }
}
