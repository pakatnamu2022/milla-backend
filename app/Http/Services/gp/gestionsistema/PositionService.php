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
    $Position = Position::where('id', $id)
      ->where('status_deleted', 1)->first();
    if (!$Position) {
      throw new Exception('Vista no encontrada');
    }
    return $Position;
  }

  public function store($data)
  {
    $data = $this->enrichPositionData($data);
    $Position = Position::create($data);
    return new PositionResource($Position);
  }

  public function show($id)
  {
    return new PositionResource($this->find($id));
  }

  public function update($data)
  {
    $Position = $this->find($data['id']);
    $data = $this->enrichPositionData($data);
    $Position->update($data);
    return new PositionResource($Position);
  }

  public function destroy($id)
  {
    $Position = $this->find($id);
    $Position->status_deleted = 0;
    $Position->save();
    return response()->json(['message' => 'Vista eliminada correctamente']);
  }
}
