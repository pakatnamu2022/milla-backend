<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
    if ($data['type_operation_id'] === ApMasters::TIPO_OPERACION_COMERCIAL) {
      $existe = ApModelsVn::where('family_id', $data['family_id'])
        ->where('model_year', $data['model_year'])
        ->where('version', $data['version'])
        ->whereNull('deleted_at')
        ->exists();

      if ($existe) {
        throw new Exception('Ya existe un modelo con esa familia y año.');
      }

      // Generate code using model method (separates correlatives by operation type)
      $data['code'] = ApModelsVn::generateNextCode(
        $data['family_id'],
        $data['model_year'],
        $data['type_operation_id']
      );
    } else {
      $data['code'] = ApModelsVn::generateNextCode(
        $data['family_id'],
        date('Y'),
        $data['type_operation_id']
      );
    }

    $engineType = ApModelsVn::create($data);

    // Invalidar caché
    Cache::forget('models.all');

    return new ApModelsVnResource($engineType);
  }

  public function show($id)
  {
    return new ApModelsVnResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $modelVn = $this->find($data['id']);

    if ($modelVn->type_operation_id != $data['type_operation_id']) {
      throw new Exception('No puedes editar una marca que no corresponde al modulo respectivo.');
    }

    $familyId = $data['family_id'] ?? null;
    $modelYear = $data['model_year'] ?? null;

    $familiaChanged = $familyId !== null && $modelVn->family_id != $familyId;
    $anioChanged = $modelYear !== null && $modelVn->model_year != $modelYear;

    if ($familiaChanged || $anioChanged) {
      $existe = ApModelsVn::where('family_id', $familyId)
        ->where('model_year', $modelYear)
        ->where('id', '!=', $data['id'])
        ->whereNull('deleted_at')
        ->exists();

      if ($existe) {
        throw new Exception('Ya existe un modelo con esa familia y año.');
      }

      // Generate new code using model method (separates correlatives by operation type)
      $data['code'] = ApModelsVn::generateNextCode(
        $familyId,
        $modelYear,
        $modelVn->type_operation_id
      );
    }

    $modelVn->update($data);

    // Invalidar caché
    Cache::forget('models.all');

    return new ApModelsVnResource($modelVn);
  }


  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });

    // Invalidar caché
    Cache::forget('models.all');

    return response()->json(['message' => 'Modelo VN eliminado correctamente']);
  }
}
