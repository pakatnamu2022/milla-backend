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
   * Sincroniza asignaciones masivas de usuario-sede
   * Agrega nuevas asignaciones y elimina las que no están en la lista
   *
   * @param array $data Array con 'assignments' que contiene arrays de user_id y sede_id
   * @return array Resumen de operaciones realizadas
   */
  public function storeMany($data)
  {
    $assignments = $data['assignments'];
    $created = [];
    $restored = [];
    $deleted = [];
    $errors = [];

    // Crear un array de combinaciones user_id-sede_id enviadas
    $sentCombinations = [];
    foreach ($assignments as $assignment) {
      $key = $assignment['user_id'] . '-' . $assignment['sede_id'];
      $sentCombinations[$key] = $assignment;
    }

    // Obtener todas las asignaciones existentes (incluyendo soft deleted)
    $existingAssignments = UserSede::withTrashed()->get();

    // Procesar asignaciones existentes para determinar cuáles eliminar
    foreach ($existingAssignments as $existing) {
      $key = $existing->user_id . '-' . $existing->sede_id;

      if (!isset($sentCombinations[$key])) {
        // Esta asignación existe pero no está en la lista enviada, debe eliminarse
        if (!$existing->trashed()) {
          $existing->delete();
          $deleted[] = [
            'user_id' => $existing->user_id,
            'sede_id' => $existing->sede_id,
          ];
        }
      } else {
        // Esta asignación existe y está en la lista enviada
        if ($existing->trashed()) {
          // Si estaba eliminada, restaurarla
          $existing->restore();
          $existing->update(['status' => $sentCombinations[$key]['status'] ?? true]);
          $restored[] = [
            'user_id' => $existing->user_id,
            'sede_id' => $existing->sede_id,
          ];
        } else {
          // Si ya existe y está activa, actualizar status si es necesario
          if (isset($sentCombinations[$key]['status']) && $existing->status !== $sentCombinations[$key]['status']) {
            $existing->update(['status' => $sentCombinations[$key]['status']]);
          }
        }
        // Remover de la lista para no intentar crearla después
        unset($sentCombinations[$key]);
      }
    }

    // Crear las nuevas asignaciones que no existían
    foreach ($sentCombinations as $key => $assignment) {
      try {
        $userSede = UserSede::create([
          'user_id' => $assignment['user_id'],
          'sede_id' => $assignment['sede_id'],
          'status' => $assignment['status'] ?? true,
        ]);
        $created[] = [
          'user_id' => $userSede->user_id,
          'sede_id' => $userSede->sede_id,
        ];
      } catch (\Exception $e) {
        $errors[] = [
          'user_id' => $assignment['user_id'],
          'sede_id' => $assignment['sede_id'],
          'error' => $e->getMessage(),
        ];
      }
    }

    return [
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
