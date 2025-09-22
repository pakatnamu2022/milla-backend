<?php

namespace App\Http\Services\ap;

use App\Http\Resources\ap\ApCommercialMastersResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApCommercialMasters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApCommercialMastersService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApCommercialMasters::class,
      $request,
      ApCommercialMasters::filters,
      ApCommercialMasters::sorts,
      ApCommercialMastersResource::class,
    );
  }

  public function find($id)
  {
    $ApCommercialMasters = ApCommercialMasters::where('id', $id)->first();
    if (!$ApCommercialMasters) {
      throw new Exception('Concepto de tabla maestra no encontrado');
    }
    return $ApCommercialMasters;
  }

  public function store(Mixed $data)
  {
    if (
      isset($data['type']) &&
      $data['type'] === 'TIPO_DOCUMENTO' &&
      (!isset($data['code']) || !is_int($data['code']))
    ) {
      throw new Exception('El campo num. digitos debe tener formato de número entero.');
    }

    $ApCommercialMasters = ApCommercialMasters::create($data);
    return new ApCommercialMastersResource($ApCommercialMasters);
  }

  public function show($id)
  {
    return new ApCommercialMastersResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $ApCommercialMasters = $this->find($data['id']);
    if (
      isset($data['type']) &&
      $data['type'] === 'TIPO_DOCUMENTO' &&
      (!isset($data['code']) || !is_int($data['code']))
    ) {
      throw new Exception('El campo num. digitos debe tener formato de número entero.');
    }
    $ApCommercialMasters->update($data);
    return new ApCommercialMastersResource($ApCommercialMasters);
  }

  public function destroy($id)
  {
    $ApCommercialMasters = $this->find($id);
    DB::transaction(function () use ($ApCommercialMasters) {
      $ApCommercialMasters->delete();
    });
    return response()->json(['message' => 'Concepto de tabla maestra eliminado correctamente']);
  }
}
