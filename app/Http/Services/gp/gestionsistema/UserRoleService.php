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
    // El ID ahora representa el user_id, no el id de UserRole
    $userId = $data['id'];

    // Verificar que role_id esté presente
    if (!isset($data['role_id'])) {
      throw new Exception('El role_id es requerido');
    }

    // Buscar si el usuario ya tiene un rol asignado
    $userRole = UserRole::where('user_id', $userId)
      ->where('status_deleted', 1)
      ->first();

    if ($userRole) {
      // Si ya tiene un rol, actualizarlo
      $userRole->update(['role_id' => $data['role_id']]);
      return new UserRoleResource($userRole->fresh(['role', 'user']));
    } else {
      // Si no tiene rol, crear uno nuevo
      $newUserRole = UserRole::create([
        'user_id' => $userId,
        'role_id' => $data['role_id'],
        'status_deleted' => 1
      ]);
      return new UserRoleResource($newUserRole->load(['role', 'user']));
    }
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
