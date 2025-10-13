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
        $exists->update(['status' => true]);
        return new UserSedeResource($exists);
      }
      throw new Exception('La asignación de usuario-sede ya existe');
    }

    $userSede = UserSede::create($data);
    return new UserSedeResource($userSede);
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

  /**
   * Sincroniza asignaciones de sedes para un usuario específico
   * Agrega nuevas asignaciones y elimina las que no están en la lista
   *
   * @param array $data Array con 'user_id' y 'sede_ids' (array de IDs de sedes)
   * @return array Resumen de operaciones realizadas
   */
  public function storeMany($data)
  {
    $userId = $data['user_id'];
    $sedeIds = $data['sede_ids'];
    $created = [];
    $restored = [];
    $deleted = [];
    $errors = [];

    // Validar que el usuario existe
    if (!\App\Models\User::find($userId)) {
      throw new Exception('El usuario no existe');
    }

    // Obtener todas las asignaciones existentes para este usuario (incluyendo soft deleted)
    $existingAssignments = UserSede::withTrashed()
      ->where('user_id', $userId)
      ->get();

    // Crear un array de sede_ids enviados para búsqueda rápida
    $sentSedeIds = array_flip($sedeIds);

    // Procesar asignaciones existentes para determinar cuáles eliminar
    foreach ($existingAssignments as $existing) {
      if (!isset($sentSedeIds[$existing->sede_id])) {
        // Esta sede ya no está en la lista, debe eliminarse
        if (!$existing->trashed()) {
          $existing->delete();
          $deleted[] = [
            'sede_id' => $existing->sede_id,
          ];
        }
      } else {
        // Esta sede está en la lista enviada
        if ($existing->trashed()) {
          // Si estaba eliminada, restaurarla
          $existing->restore();
          $existing->update(['status' => true]);
          $restored[] = [
            'sede_id' => $existing->sede_id,
          ];
        }
        // Remover de la lista para no intentar crearla después
        unset($sentSedeIds[$existing->sede_id]);
      }
    }

    // Crear las nuevas asignaciones que no existían
    foreach ($sentSedeIds as $sedeId => $index) {
      try {
        // Validar que la sede existe
        if (!\App\Models\gp\maestroGeneral\Sede::find($sedeId)) {
          $errors[] = [
            'sede_id' => $sedeId,
            'error' => 'La sede no existe',
          ];
          continue;
        }

        $userSede = UserSede::create([
          'user_id' => $userId,
          'sede_id' => $sedeId,
          'status' => true,
        ]);
        $created[] = [
          'sede_id' => $userSede->sede_id,
        ];
      } catch (\Exception $e) {
        $errors[] = [
          'sede_id' => $sedeId,
          'error' => $e->getMessage(),
        ];
      }
    }

    return [
      'user_id' => $userId,
      'created' => $created,
      'restored' => $restored,
      'deleted' => $deleted,
      'errors' => $errors,
      'summary' => [
        'created_count' => count($created),
        'restored_count' => count($restored),
        'deleted_count' => count($deleted),
        'errors_count' => count($errors),
      ]
    ];
  }
}
