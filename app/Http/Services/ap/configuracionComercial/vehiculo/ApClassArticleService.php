<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApClassArticleResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApClassArticleService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApClassArticle::class,
      $request,
      ApClassArticle::filters,
      ApClassArticle::sorts,
      ApClassArticleResource::class,
    );
  }

  public function find($id)
  {
    $ApCommercialMasters = ApClassArticle::where('id', $id)->first();
    if (!$ApCommercialMasters) {
      throw new Exception('Clase de artículo no encontrado');
    }
    return $ApCommercialMasters;
  }

  public function store(array $data)
  {
    $ApCommercialMasters = ApClassArticle::create($data);
    return new ApClassArticleResource($ApCommercialMasters);
  }

  public function show($id)
  {
    return new ApClassArticleResource($this->find($id));
  }

  public function update($data)
  {
    $ApCommercialMasters = $this->find($data['id']);
    $ApCommercialMasters->update($data);
    return new ApClassArticleResource($ApCommercialMasters);
  }

  public function destroy($id)
  {
    $ApCommercialMasters = $this->find($id);
    DB::transaction(function () use ($ApCommercialMasters) {
      $ApCommercialMasters->delete();
    });
    return response()->json(['message' => 'Clase de artículo eliminado correctamente']);
  }
}
