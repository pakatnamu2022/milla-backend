<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\RoleResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Role;
use Exception;
use Illuminate\Http\Request;

class RoleService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Role::where('status_deleted', 1),
      $request,
      Role::filters,
      Role::sorts,
      RoleResource::class,
    );
  }

  private function enrichRoleData(array $data): array
  {
    $data['updater_user'] = auth()->user()->id;
    return $data;
  }

  public function find($id)
  {
    $role = Role::where('id', $id)
      ->where('status_deleted', 1)->first();
    if (!$role) {
      throw new Exception('Rol no encontrado');
    }
    return $role;
  }

  public function store($data)
  {
    $data = $this->enrichRoleData($data);
    $data['creator_user'] = auth()->user()->id;
    $role = Role::create($data);
    return new RoleResource(Role::find($role->id));
  }

  public function show($id)
  {
    return new RoleResource($this->find($id));
  }

  public function update($data)
  {
    $role = $this->find($data['id']);
    $data = $this->enrichRoleData($data);
    $role->update($data);
    return new RoleResource($role);
  }

  public function destroy($id)
  {
    $role = $this->find($id);
    $role->status_deleted = 0;
    $role->save();
    return response()->json(['message' => 'Rol eliminado correctamente']);
  }

  public function getUsersByRole($id)
  {
    $role = $this->find($id);
    if (!$role) {
      throw new Exception('Rol no encontrado');
    }
    $users = $role->users()->get();
    return UserResource::collection($users);
  }
}
