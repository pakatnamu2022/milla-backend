<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Resources\gp\gestionsistema\ViewResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;

class PositionService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Position::where('status_deleted', 1),
            $request,
            Position::filters,
            Position::sorts,
            PositionResource::class,
        );
    }

    private function enrichViewData(array $data): array
    {
        return $data;
    }

    public function find($id)
    {
        $view = Position::where('id', $id)
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
}
