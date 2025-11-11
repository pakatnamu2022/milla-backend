<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\StoreApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\UpdateApVehicleDeliveryRequest;
use App\Http\Services\ap\comercial\ApVehicleDeliveryService;
use Illuminate\Http\Request;

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
   * Genera la guía de remisión para una entrega de vehículo
   */
  public function generateShippingGuide($id, Request $request)
  {
    try {
      $data = $request->validate([
        'driver_doc' => 'required|integer|max_digits:11|min_digits:8',
        'license' => 'nullable|string|max:20',
        'plate' => 'nullable|string|max:20',
        'driver_name' => 'required|string|max:100',
        'enviar_sunat' => 'required|boolean',
        'transfer_modality_id' => 'required|integer|exists:ap_commercial_masters,id',
      ]);

      return $this->success($this->service->generateShippingGuide($id, $data));
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
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
}
