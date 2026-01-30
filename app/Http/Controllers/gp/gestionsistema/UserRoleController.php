<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexUserRoleRequest;
use App\Http\Requests\gp\gestionsistema\StoreUserRoleRequest;
use App\Http\Requests\gp\gestionsistema\UpdateUserRoleRequest;
use App\Http\Services\gp\gestionsistema\UserRoleService;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
  protected UserRoleService $service;

  public function __construct(UserRoleService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of the resource.
   * @param IndexUserRoleRequest $request
   * @return JsonResponse
   */
  public function index(IndexUserRoleRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created resource in storage.
   * @param StoreUserRoleRequest $request
   * @return JsonResponse
   */
  public function store(StoreUserRoleRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified resource.
   * @param int $id
   * @return JsonResponse
   */
  public function show(int $id)
  {
    try {
      return response()->json($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified resource in storage.
   * @param UpdateUserRoleRequest $request
   * @param int $id
   * @return JsonResponse
   */
  public function update(UpdateUserRoleRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified resource from storage.
   * @param int $id
   * @return JsonResponse
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get roles by user ID.
   * @param int $userId
   * @return JsonResponse
   */
  public function rolesByUser(int $userId)
  {
    try {
      return response()->json($this->service->getRolesByUser($userId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get users by role ID.
   * @param int $roleId
   * @return JsonResponse
   */
  public function usersByRole(int $roleId)
  {
    try {
      return response()->json($this->service->getUsersByRole($roleId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
