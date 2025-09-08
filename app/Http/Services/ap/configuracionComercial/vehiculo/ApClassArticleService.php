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
    $ApClassArticle = ApClassArticle::where('id', $id)->first();
    if (!$ApClassArticle) {
      throw new Exception('Clase de artículo no encontrado');
    }
    return $ApClassArticle;
  }

  public function store(array $data)
  {
    $ApClassArticle = ApClassArticle::create($data);
    return new ApClassArticleResource($ApClassArticle);
  }

  public function show($id)
  {
    return new ApClassArticleResource($this->find($id));
  }

  public function update($data)
  {
    $ApClassArticle = $this->find($data['id']);
    $ApClassArticle->update($data);
    return new ApClassArticleResource($ApClassArticle);
  }

  public function destroy($id)
  {
    $ApClassArticle = $this->find($id);
    DB::transaction(function () use ($ApClassArticle) {
      $ApClassArticle->delete();
    });
    return response()->json(['message' => 'Clase de artículo eliminado correctamente']);
  }
}
