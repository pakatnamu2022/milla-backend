<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\UserRoleResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\UserRole;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserRoleService extends BaseService
{
  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      UserRole::where('status_deleted', 1)->with(['role', 'user']),
      $request,
      UserRole::filters,
      UserRole::sorts,
      UserRoleResource::class,
    );
  }

  /**
   * @param $id
   * @return mixed
   * @throws Exception
   */
  public function find($id): mixed
  {
    $userRole = UserRole::where('id', $id)
      ->where('status_deleted', 1)
      ->with(['role', 'user'])
      ->first();
    if (!$userRole) {
      throw new Exception('Asignación de rol no encontrada');
    }
    return $userRole;
  }

  /**
   * @param $data
   * @return UserRoleResource
   * @throws Exception
   */
  public function store($data): UserRoleResource
  {
    // Verificar si ya existe una asignación activa del rol al usuario
    $existingUserRole = UserRole::where('user_id', $data['user_id'])
      ->where('role_id', $data['role_id'])
      ->where('status_deleted', 1)
      ->first();

    if ($existingUserRole) {
      throw new Exception('El usuario ya tiene este rol asignado');
    }

    $data['status_deleted'] = 1;
    $userRole = UserRole::create($data);
    return new UserRoleResource(UserRole::find($userRole->id));
  }

  /**
   * @param $id
   * @return UserRoleResource
   * @throws Exception
   */
  public function show($id): UserRoleResource
  {
    return new UserRoleResource($this->find($id));
  }

  /**
   * @param $data
   * @return UserRoleResource
   * @throws Exception
   */
  public function update($data): UserRoleResource
  {
    $userRole = $this->find($data['id']);

    // Si se intenta cambiar el rol o usuario, verificar que no exista duplicado
    if (isset($data['role_id']) || isset($data['user_id'])) {
      $userId = $data['user_id'] ?? $userRole->user_id;
      $roleId = $data['role_id'] ?? $userRole->role_id;

      $existingUserRole = UserRole::where('user_id', $userId)
        ->where('role_id', $roleId)
        ->where('status_deleted', 1)
        ->where('id', '!=', $data['id'])
        ->first();

      if ($existingUserRole) {
        throw new Exception('El usuario ya tiene este rol asignado');
      }
    }

    $userRole->update($data);
    return new UserRoleResource($userRole);
  }

  /**
   * @param $id
   * @return JsonResponse
   * @throws Exception
   */
  public function destroy($id): JsonResponse
  {
    $userRole = $this->find($id);
    $userRole->status_deleted = 0;
    $userRole->save();
    return response()->json(['message' => 'Asignación de rol eliminada correctamente']);
  }

  /**
   * @param $userId
   * @return AnonymousResourceCollection
   */
  public function getRolesByUser($userId): AnonymousResourceCollection
  {
    $userRoles = UserRole::where('user_id', $userId)
      ->where('status_deleted', 1)
      ->with(['role', 'user'])
      ->get();
    return UserRoleResource::collection($userRoles);
  }

  /**
   * @param $roleId
   * @return AnonymousResourceCollection
   */
  public function getUsersByRole($roleId): AnonymousResourceCollection
  {
    $userRoles = UserRole::where('role_id', $roleId)
      ->where('status_deleted', 1)
      ->with(['role', 'user'])
      ->get();
    return UserRoleResource::collection($userRoles);
  }
}
