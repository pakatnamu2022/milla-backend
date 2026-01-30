<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexPhoneLineWorkerRequest;
use App\Http\Requests\gp\tics\StorePhoneLineWorkerRequest;
use App\Http\Requests\gp\tics\UpdatePhoneLineWorkerRequest;
use App\Http\Services\gp\tics\PhoneLineWorkerService;

class PhoneLineWorkerController extends Controller
{
  protected PhoneLineWorkerService $service;

  public function __construct(PhoneLineWorkerService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPhoneLineWorkerRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StorePhoneLineWorkerRequest $request)
  {
    $data = $request->validated();
    return response()->json($this->service->store($data));
  }

  public function show($id)
  {
    try {
      return response()->json($this->service->show($id));
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }

  public function update(UpdatePhoneLineWorkerRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->update($data));
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }
}
