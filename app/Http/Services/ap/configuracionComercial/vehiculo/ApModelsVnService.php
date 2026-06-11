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

    // Los campos inmutables (code, family_id, model_year, type_operation_id)
    // ya están bloqueados en el Request y no llegarán aquí
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
