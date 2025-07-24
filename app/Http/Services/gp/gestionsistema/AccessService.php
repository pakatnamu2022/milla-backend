<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\AccessResource;
use App\Http\Resources\gp\tics\EquipmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Access;
use Exception;
use Illuminate\Http\Request;

class AccessService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Access::class,
            $request,
            Access::filters,
            Access::sorts,
            AccessResource::class,
        );
    }

    public function find($id)
    {
        $access = Access::where('id', $id)->first();
        if (!$access) {
            throw new Exception('Acceso no encontrado');
        }
        return $access;
    }

    public function store($data)
    {
        $access = Access::create($data);
        return new AccessResource(Access::find($access->id));
    }

    public function storeMany(array $data)
    {
        $roleId = $data['role_id'];
        $accesses = $data['accesses'];

        foreach ($accesses as $accessData) {
            Access::updateOrCreate(
                [
                    'role_id' => $roleId,
                    'vista_id' => $accessData['vista_id']
                ],
                [
                    'crear' => $accessData['crear'],
                    'ver' => $accessData['ver'],
                    'editar' => $accessData['editar'],
                    'anular' => $accessData['anular'],
                    'status_deleted' => 1,
                ]
            );
        }

        return response()->json(['message' => 'Permisos actualizados correctamente']);
    }


    public function show($id)
    {
        return new AccessResource($this->find($id));
    }

    public function update($data)
    {
        $access = $this->find($data['id']);
        $access->update($data);
        return new EquipmentResource($access);
    }

    public function destroy($id)
    {
        $access = $this->find($id);
        $access->status_deleted = 0;
        $access->save();
        return response()->json(['message' => 'Acceso eliminado correctamente']);
    }

    public function getUsersByRole($id)
    {
        $access = $this->find($id);
        if (!$access) {
            throw new Exception('Acceso no encontrado');
        }

        $users = $access->users()->where('status_deleted', 1)->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No hay usuarios asociados a este acceso'], 404);
        }

        return response()->json($users);
    }
}
