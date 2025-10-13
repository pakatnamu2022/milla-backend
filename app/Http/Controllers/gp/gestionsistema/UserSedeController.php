<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexUserSedeRequest;
use App\Http\Requests\gp\gestionsistema\StoreUserSedeRequest;
use App\Http\Requests\gp\gestionsistema\UpdateUserSedeRequest;
use App\Http\Services\gp\gestionsistema\UserSedeService;

class UserSedeController extends Controller
{
  protected UserSedeService $service;

  public function __construct(UserSedeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexUserSedeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreUserSedeRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id)
  {
    try {
      return response()->json($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateUserSedeRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getSedesByUser(int $userId)
  {
    try {
      return $this->success($this->service->getSedesByUser($userId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getUsersBySede(int $sedeId)
  {
    try {
      return $this->success($this->service->getUsersBySede($sedeId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
