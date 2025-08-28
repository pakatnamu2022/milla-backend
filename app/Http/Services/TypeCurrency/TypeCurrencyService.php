<?php

namespace App\Http\Services\TypeCurrency;

use App\Http\Resources\TypeCurrency\TypeCurrencyResource;
use App\Http\Services\BaseService;
use App\Models\TypeCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TypeCurrencyService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      TypeCurrency::class,
      $request,
      TypeCurrency::filters,
      TypeCurrency::sorts,
      TypeCurrencyResource::class,
    );
  }

  public function find($id)
  {
    $engineType = TypeCurrency::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de moneda no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = TypeCurrency::create($data);
    return new TypeCurrencyResource($engineType);
  }

  public function show($id)
  {
    return new TypeCurrencyResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new TypeCurrencyResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de moneda eliminado correctamente']);
  }
}
