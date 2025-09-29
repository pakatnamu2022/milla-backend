<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\DistrictResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class DistrictService extends BaseService
{
  public function list(Request $request)
  {
    // Si se solicita todos los distritos, usar caché
    if ($request->boolean('all')) {
      return Cache::remember('districts.all', now()->addMonth(), function () use ($request) { // 1 mes
        return $this->getFilteredResults(
          District::class,
          $request,
          District::filters,
          District::sorts,
          DistrictResource::class,
        );
      });
    }

    // Si hay parámetros, no usar caché
    return $this->getFilteredResults(
      District::class,
      $request,
      District::filters,
      District::sorts,
      DistrictResource::class,
    );
  }

  public function find($id)
  {
    $District = District::where('id', $id)->first();
    if (!$District) {
      throw new Exception('Distrito no encontrado');
    }
    return $District;
  }

  public function store(Mixed $data)
  {
    $District = District::create($data);
    Cache::forget('districts.all');
    return new DistrictResource($District);
  }

  public function show($id)
  {
    return new DistrictResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $District = $this->find($data['id']);
    $District->update($data);
    Cache::forget('districts.all');
    return new DistrictResource($District);
  }

  public function destroy($id)
  {
    $District = $this->find($id);
    DB::transaction(function () use ($District) {
      $District->delete();
    });
    Cache::forget('districts.all');
    return response()->json(['message' => 'Distrito eliminado correctamente']);
  }
}
