<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            User::where('status_deleted', 1),
            $request,
            User::filters,
            User::sorts,
            UserResource::class,
        );
    }

    private function enrichUserData(array $data): array
    {
        $data['slug'] = !empty($data['descripcion']) ? Str::slug($data['descripcion']) : null;
        return $data;
    }

    public function find($id)
    {
        $view = User::where('id', $id)
            ->where('status_deleted', 1)->first();
        if (!$view) {
            throw new Exception('Usuario no encontrado');
        }
        return $view;
    }

    public function store($data)
    {
        $data = $this->enrichUserData($data);
        $view = User::create($data);
        return new UserResource($view);
    }

    public function show($id)
    {
        return new UserResource($this->find($id));
    }

    public function update($data)
    {
        $view = $this->find($data['id']);
        $data = $this->enrichUserData($data);
        $view->update($data);
        return new UserResource($view);
    }

    public function destroy($id)
    {
        $view = $this->find($id);
        $view->status_deleted = 0;
        $view->save();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

//    GRAFICOS

    public function useStateGraph()
    {
        return User::query()
            ->selectRaw("estado_uso, COUNT(*) as total")
            ->where('status_deleted', 1)
            ->groupBy('estado_uso')
            ->get();
    }

    public function sedeGraph()
    {
        return User::selectRaw('companies.abbreviation as sede, COUNT(*) as total')
            ->join('config_sede', 'config_sede.id', '=', 'help_vistas.sede_id')
            ->join('companies', 'companies.id', '=', 'config_sede.empresa_id')
            ->where('help_vistas.status_deleted', 1)
            ->groupBy('companies.abbreviation')
            ->orderByDesc('total')
            ->get();
    }
}
