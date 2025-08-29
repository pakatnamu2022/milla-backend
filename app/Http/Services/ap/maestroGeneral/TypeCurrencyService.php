<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\TypeCurrencyResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TypeCurrencyService extends BaseService implements BaseServiceInterface
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

  public function store(mixed $data)
  {
    $engineType = TypeCurrency::create($data);
    return new TypeCurrencyResource($engineType);
  }

  public function show($id)
  {
    return new TypeCurrencyResource($this->find($id));
  }

  public function update(mixed $data)
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
