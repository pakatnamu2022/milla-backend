<?php

namespace App\Http\Services\GeneralMaster;

use App\Http\Resources\GeneralMaster\GeneralMasterResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\GeneralMaster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GeneralMasterService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      GeneralMaster::class,
      $request,
      GeneralMaster::filters,
      GeneralMaster::sorts,
      GeneralMasterResource::class,
    );
  }

  public function find($id)
  {
    $generalMaster = GeneralMaster::where('id', $id)->first();
    if (!$generalMaster) {
      throw new Exception('Registro no encontrado en General Master');
    }
    return $generalMaster;
  }

  public function store(Mixed $data)
  {
    $generalMaster = GeneralMaster::create($data);

    Cache::forget('general_masters_types');

    return new GeneralMasterResource($generalMaster);
  }

  public function show($id)
  {
    return new GeneralMasterResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $generalMaster = $this->find($data['id']);
    $generalMaster->update($data);

    Cache::forget('general_masters_types');

    return new GeneralMasterResource($generalMaster);
  }

  public function destroy($id)
  {
    $generalMaster = $this->find($id);
    DB::transaction(function () use ($generalMaster) {
      $generalMaster->delete();
    });

    Cache::forget('general_masters_types');

    return response()->json(['message' => 'Registro de General Master eliminado correctamente']);
  }

  public function getTypes()
  {
    return Cache::remember('general_masters_types', 1440, function () {
      $types = GeneralMaster::select('type')
        ->distinct()
        ->whereNotNull('type')
        ->orderBy('type')
        ->pluck('type');

      return response()->json([
        'data' => $types,
        'count' => $types->count(),
        'cached_at' => now()->toDateTimeString(),
      ]);
    });
  }
}
