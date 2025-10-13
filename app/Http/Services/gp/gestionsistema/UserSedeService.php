<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\UserSedeResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\UserSede;
use Exception;
use Illuminate\Http\Request;

class UserSedeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      UserSede::with(['user', 'sede']),
      $request,
      UserSede::filters,
      UserSede::sorts,
      UserSedeResource::class,
    );
  }

  public function find($id)
  {
    $userSede = UserSede::with(['user', 'sede'])->find($id);
    if (!$userSede) {
      throw new Exception('Asignación de usuario-sede no encontrada');
    }
    return $userSede;
  }

  public function store($data)
  {
    // Check if assignment already exists
    $exists = UserSede::where('user_id', $data['user_id'])
      ->where('sede_id', $data['sede_id'])
      ->withTrashed()
      ->first();

    if ($exists) {
      if ($exists->trashed()) {
        $exists->restore();
        $exists->update(['status' => $data['status'] ?? true]);
        return new UserSedeResource($exists->load(['user', 'sede']));
      }
      throw new Exception('La asignación de usuario-sede ya existe');
    }

    $userSede = UserSede::create($data);
    return new UserSedeResource($userSede->load(['user', 'sede']));
  }

  public function show($id)
  {
    return new UserSedeResource($this->find($id));
  }

  public function update($data)
  {
    $userSede = $this->find($data['id']);
    $userSede->update($data);
    return new UserSedeResource($userSede->load(['user', 'sede']));
  }

  public function destroy($id)
  {
    $userSede = $this->find($id);
    $userSede->delete();
    return response()->json(['message' => 'Asignación de usuario-sede eliminada correctamente']);
  }

  public function getSedesByUser($userId)
  {
    $userSedes = UserSede::with('sede')
      ->where('user_id', $userId)
      ->where('status', true)
      ->get();

    return UserSedeResource::collection($userSedes);
  }

  public function getUsersBySede($sedeId)
  {
    $userSedes = UserSede::with('user')
      ->where('sede_id', $sedeId)
      ->where('status', true)
      ->get();

    return UserSedeResource::collection($userSedes);
  }
}
