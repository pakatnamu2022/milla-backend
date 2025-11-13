<?php

namespace App\Http\Services\ap;

use App\Http\Resources\ap\ApPostVentaMastersResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApPostVentaMasters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApPostVentaMastersService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApPostVentaMasters::class,
      $request,
      ApPostVentaMasters::filters,
      ApPostVentaMasters::sorts,
      ApPostVentaMastersResource::class,
    );
  }

  public function find($id)
  {
    $ApPostVentaMasters = ApPostVentaMasters::where('id', $id)->first();
    if (!$ApPostVentaMasters) {
      throw new Exception('Concepto de tabla maestra no encontrado');
    }
    return $ApPostVentaMasters;
  }

  public function store(Mixed $data)
  {
    $ApPostVentaMasters = ApPostVentaMasters::create($data);
    return new ApPostVentaMastersResource($ApPostVentaMasters);
  }

  public function show($id)
  {
    return new ApPostVentaMastersResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $ApPostVentaMasters = $this->find($data['id']);
    $ApPostVentaMasters->update($data);
    return new ApPostVentaMastersResource($ApPostVentaMasters);
  }

  public function destroy($id)
  {
    $ApPostVentaMasters = $this->find($id);
    DB::transaction(function () use ($ApPostVentaMasters) {
      $ApPostVentaMasters->delete();
    });
    return response()->json(['message' => 'Concepto de tabla maestra eliminado correctamente']);
  }
}
