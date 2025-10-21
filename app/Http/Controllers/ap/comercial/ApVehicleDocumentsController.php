<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexApVehicleDocumentsRequest;
use App\Http\Requests\ap\comercial\StoreApVehicleDocumentsRequest;
use App\Http\Requests\ap\comercial\UpdateApVehicleDocumentsRequest;
use App\Http\Services\ap\comercial\ApVehicleDocumentsService;
use App\Models\ap\comercial\ApVehicleDocuments;
use Illuminate\Http\Request;

class ApVehicleDocumentsController extends Controller
{
  protected ApVehicleDocumentsService $service;

  public function __construct(ApVehicleDocumentsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleDocumentsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleDocumentsRequest $request)
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

  public function update(UpdateApVehicleDocumentsRequest $request, $id)
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
}
