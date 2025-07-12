<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\ViewResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\View;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ViewService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            View::where('status_deleted', 1),
            $request,
            View::filters,
            View::sorts,
            ViewResource::class,
        );
    }

    private function enrichViewData(array $data): array
    {
        $data['slug'] = !empty($data['descripcion']) ? Str::slug($data['descripcion']) : null;
        return $data;
    }

    public function find($id)
    {
        $view = View::where('id', $id)
            ->where('status_deleted', 1)->first();
        if (!$view) {
            throw new Exception('Vista no encontrada');
        }
        return $view;
    }

    public function store($data)
    {
        $data = $this->enrichViewData($data);
        $view = View::create($data);
        return new ViewResource($view);
    }

    public function show($id)
    {
        return new ViewResource($this->find($id));
    }

    public function update($data)
    {
        $view = $this->find($data['id']);
        $data = $this->enrichViewData($data);
        $view->update($data);
        return new ViewResource($view);
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
        return View::query()
            ->selectRaw("estado_uso, COUNT(*) as total")
            ->where('status_deleted', 1)
            ->groupBy('estado_uso')
            ->get();
    }

    public function sedeGraph()
    {
        return View::selectRaw('companies.abbreviation as sede, COUNT(*) as total')
            ->join('config_sede', 'config_sede.id', '=', 'help_vistas.sede_id')
            ->join('companies', 'companies.id', '=', 'config_sede.empresa_id')
            ->where('help_vistas.status_deleted', 1)
            ->groupBy('companies.abbreviation')
            ->orderByDesc('total')
            ->get();
    }
}
