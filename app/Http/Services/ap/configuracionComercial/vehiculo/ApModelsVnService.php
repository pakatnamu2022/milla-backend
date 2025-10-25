<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
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
    $all = $request->query('all') === 'true';
    $status = $request->query('status') == 1;
    $onlyAll = $all && $status && count($request->except(['all'])) === 1;

    if ($onlyAll) {
      $isCached = Cache::has('models.all');
      \Log::info('ApModelsVn - Usando caché: ' . ($isCached ? 'SI (desde caché)' : 'NO (generando nueva)'));

      // Cachear solo los datos, no la respuesta completa
      $data = Cache::remember('models.all', now()->addMonth(), function () use ($request) { // 1 mes
        $response = $this->getFilteredResults(
          ApModelsVn::class,
          $request,
          ApModelsVn::filters,
          ApModelsVn::sorts,
          ApModelsVnResource::class,
        );
        // Retornar solo el contenido JSON decodificado
        return json_decode($response->content(), true);
      });

      // Crear respuesta y agregar header para indicar si viene de caché
      return response()->json($data)->header('X-Cache-Status', $isCached ? 'HIT' : 'MISS');
    }

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
    $existe = ApModelsVn::where('family_id', $data['family_id'])
      ->where('model_year', $data['model_year'])
      ->where('version', $data['version'])
      ->whereNull('deleted_at')
      ->exists();

    if ($existe) {
      throw new Exception('Ya existe un modelo con esa familia y año.');
    }

    $familia = ApFamilies::findOrFail($data['family_id']);
    $anioCorto = substr($data['model_year'], -2);
    $data['code'] = $familia->code . $anioCorto . $this->nextCorrelativeCount(
        ApModelsVn::class,
        3,
        ['family_id' => $data['family_id']]
      );
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

      $familia = ApFamilies::findOrFail($familyId);
      $anioCorto = substr($modelYear, -2);

      $data['code'] = $familia->code . $anioCorto . $this->nextCorrelativeCount(
          ApModelsVn::class,
          3,
          ['family_id' => $familyId]
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
