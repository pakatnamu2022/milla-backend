<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApModelsVnService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApModelsVn::class,
      $request,
      ApModelsVn::filters,
      ApModelsVn::sorts,
      ApModelsVnResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApModelsVn::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Modelo VN no encontrado');
    }
    return $engineType;
  }

  public function store(mixed $data)
  {
    $existe = ApModelsVn::where('familia_id', $data['familia_id'])
      ->where('anio_modelo', $data['anio_modelo'])
      ->whereNull('deleted_at')
      ->exists();

    if ($existe) {
      throw new Exception('Ya existe un modelo con esa familia y año.');
    }

    $familia = ApFamilies::findOrFail($data['familia_id']);
    $anioCorto = substr($data['anio_modelo'], -2);
    $data['codigo'] = $familia->codigo . $anioCorto . $this->nextCorrelativeCount(
        ApModelsVn::class,
        3,
        ['familia_id' => $data['familia_id']]
      );
    $engineType = ApModelsVn::create($data);
    return new ApModelsVnResource($engineType);
  }

  public function show($id)
  {
    return new ApModelsVnResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $engineType = $this->find($data['id']);

    $familiaChanged = $engineType->familia_id != $data['familia_id'];
    $anioChanged = $engineType->anio_modelo != $data['anio_modelo'];

    if ($familiaChanged || $anioChanged) {
      $existe = ApModelsVn::where('familia_id', $data['familia_id'])
        ->where('anio_modelo', $data['anio_modelo'])
        ->where('id', '!=', $data['id'])
        ->whereNull('deleted_at')
        ->exists();

      if ($existe) {
        throw new Exception('Ya existe un modelo con esa familia y año.');
      }
      
      $familia = ApFamilies::findOrFail($data['familia_id']);
      $anioCorto = substr($data['anio_modelo'], -2);
      $data['codigo'] = $familia->codigo . $anioCorto . $this->nextCorrelativeCount(
          ApModelsVn::class,
          3,
          ['familia_id' => $data['familia_id']]
        );
    }

    $engineType->update($data);
    return new ApModelsVnResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Modelo VN eliminado correctamente']);
  }
}
