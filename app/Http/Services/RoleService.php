<?php

namespace App\Http\Services;

use App\Http\Resources\RoleResource;
use App\Models\Role;
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
        $data['vista'] = trim(($data['marca'] ?? '') . ' ' . ($data['modelo'] ?? '') . ' ' . ($data['serie'] ?? ''));
        $data['marca_modelo'] = trim(($data['marca'] ?? '') . ' ' . ($data['modelo'] ?? ''));
        return $data;
    }

    public function find($id)
    {
        $view = Role::where('id', $id)
            ->where('status_deleted', 1)->first();
        if (!$view) {
            throw new Exception('Vista no encontrado');
        }
        return $view;
    }

    public function store($data)
    {
        $data = $this->enrichRoleData($data);
        $view = Role::create($data);
        return new RoleResource(Role::find($view->id));
    }

    public function show($id)
    {
        return new RoleResource($this->find($id));
    }

    public function update($data)
    {
        $view = $this->find($data['id']);
        $data = $this->enrichRoleData($data);
        $view->update($data);
        return new RoleResource($view);
    }

    public function destroy($id)
    {
        $view = $this->find($id);
        $view->status_deleted = 0;
        $view->save();
        return response()->json(['message' => 'Vista eliminada correctamente']);
    }

//    GRAFICOS

    public function useStateGraph()
    {
        return Role::query()
            ->selectRaw("estado_uso, COUNT(*) as total")
            ->where('status_deleted', 1)
            ->groupBy('estado_uso')
            ->get();
    }

    public function sedeGraph()
    {
        return Role::selectRaw('companies.abbreviation as sede, COUNT(*) as total')
            ->join('config_sede', 'config_sede.id', '=', 'help_vistas.sede_id')
            ->join('companies', 'companies.id', '=', 'config_sede.empresa_id')
            ->where('help_vistas.status_deleted', 1)
            ->groupBy('companies.abbreviation')
            ->orderByDesc('total')
            ->get();
    }
}
