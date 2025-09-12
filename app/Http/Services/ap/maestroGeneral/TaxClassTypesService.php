<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\TaxClassTypesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TaxClassTypesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      TaxClassTypes::class,
      $request,
      TaxClassTypes::filters,
      TaxClassTypes::sorts,
      TaxClassTypesResource::class,
    );
  }

  public function find($id)
  {
    $TaxClassTypes = TaxClassTypes::where('id', $id)->first();
    if (!$TaxClassTypes) {
      throw new Exception('Tipo de clase de impuesto no encontrado');
    }
    return $TaxClassTypes;
  }

  public function store(Mixed $data)
  {
    $TaxClassTypes = TaxClassTypes::create($data);
    return new TaxClassTypesResource($TaxClassTypes);
  }

  public function show($id)
  {
    return new TaxClassTypesResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $TaxClassTypes = $this->find($data['id']);
    $TaxClassTypes->update($data);
    return new TaxClassTypesResource($TaxClassTypes);
  }

  public function destroy($id)
  {
    $TaxClassTypes = $this->find($id);
    DB::transaction(function () use ($TaxClassTypes) {
      $TaxClassTypes->delete();
    });
    return response()->json(['message' => 'Tipo de clase de impuesto eliminado correctamente']);
  }
}
