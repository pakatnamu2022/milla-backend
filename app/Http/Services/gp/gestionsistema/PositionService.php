<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PositionResource;
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

  private function enrichPositionData(array $data): array
  {
    return $data;
  }

  public function find($id)
  {
    $position = Position::where('id', $id)
      ->where('status_deleted', 1)->first();
    if (!$position) {
      throw new Exception('Posición no encontrada');
    }
    return $position;
  }

  public function store($data)
  {
    $data = $this->enrichPositionData($data);
    $position = Position::create($data);
    return new PositionResource($position);
  }

  public function show($id)
  {
    return new PositionResource($this->find($id));
  }

  public function update($data)
  {
    $position = $this->find($data['id']);
    $data = $this->enrichPositionData($data);
    $position->update($data);
    return new PositionResource($position);
  }

  public function destroy($id)
  {
    $position = $this->find($id);
    $position->status_deleted = 0;
    $position->save();
    return response()->json(['message' => 'Posición eliminada correctamente']);
  }
}
