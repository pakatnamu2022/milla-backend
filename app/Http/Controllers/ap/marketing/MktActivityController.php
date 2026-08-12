<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktActivityRequest;
use App\Http\Requests\ap\marketing\StoreMktActivityLocationRequest;
use App\Http\Requests\ap\marketing\StoreMktActivityRequest;
use App\Http\Requests\ap\marketing\StoreMktSupportRequest;
use App\Http\Requests\ap\marketing\UpdateMktActivityRequest;
use App\Http\Services\ap\marketing\MktActivityService;
use Illuminate\Http\Request;

class MktActivityController extends Controller
{
  protected MktActivityService $service;

  public function __construct(MktActivityService $service)
  {
    $this->service = $service;
  }

  public function index(IndexMktActivityRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreMktActivityRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
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

  public function update(UpdateMktActivityRequest $request, $id)
  {
    try {
      $data = $request->validated();
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

  public function addLocation(StoreMktActivityLocationRequest $request, $id)
  {
    try {
      return $this->success($this->service->addLocation($id, $request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function addSupport(StoreMktSupportRequest $request, $id)
  {
    try {
      return $this->success($this->service->addSupport($id, $request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function changeStatus(Request $request, $id)
  {
    try {
      $status = $request->input('status');
      return $this->success($this->service->changeStatus($id, $status));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
