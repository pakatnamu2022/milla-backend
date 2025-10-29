<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexShippingGuidesRequest;
use App\Http\Requests\ap\comercial\StoreShippingGuidesRequest;
use App\Http\Requests\ap\comercial\UpdateShippingGuidesRequest;
use App\Http\Services\ap\comercial\ShippingGuidesService;
use App\Models\ap\comercial\ShippingGuides;
use Illuminate\Http\Request;

class ShippingGuidesController extends Controller
{
  protected ShippingGuidesService $service;

  public function __construct(ShippingGuidesService $service)
  {
    $this->service = $service;
  }

  public function index(IndexShippingGuidesRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreShippingGuidesRequest $request)
  {
    try {
      $data = $request->validated();

      // Agregar el archivo si existe
      if ($request->hasFile('file')) {
        $data['file'] = $request->file('file');
      }

      return $this->success($this->service->store($data));
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

  public function update(UpdateShippingGuidesRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      // Agregar el archivo si existe
      if ($request->hasFile('file')) {
        $data['file'] = $request->file('file');
      }

      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function cancel(Request $request, $id)
  {
    try {
      $request->validate([
        'cancellation_reason' => 'required|string',
      ]);

      return $this->success($this->service->cancel($id, $request->cancellation_reason));
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
}
