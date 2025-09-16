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
    $TypeCurrency = TypeCurrency::where('id', $id)->first();
    if (!$TypeCurrency) {
      throw new Exception('Tipo de moneda no encontrado');
    }
    return $TypeCurrency;
  }

  public function store(mixed $data)
  {
    $TypeCurrency = TypeCurrency::create($data);
    return new TypeCurrencyResource($TypeCurrency);
  }

  public function show($id)
  {
    return new TypeCurrencyResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $TypeCurrency = $this->find($data['id']);
    $TypeCurrency->update($data);
    return new TypeCurrencyResource($TypeCurrency);
  }

  public function destroy($id)
  {
    $TypeCurrency = $this->find($id);
    DB::transaction(function () use ($TypeCurrency) {
      $TypeCurrency->delete();
    });
    return response()->json(['message' => 'Tipo de moneda eliminado correctamente']);
  }
}
