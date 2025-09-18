<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApBankResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\ap\configuracionComercial\venta\ApBank;

class ApBankService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApBank::class,
      $request,
      ApBank::filters,
      ApBank::sorts,
      ApBankResource::class,
    );
  }

  public function find($id)
  {
    $bank = ApBank::where('id', $id)->first();
    if (!$bank) {
      throw new Exception('Chequera no encontrado');
    }
    return $bank;
  }

  public function store(mixed $data)
  {
    $bank = ApBank::create($data);
    return new ApBankResource($bank);
  }

  public function show($id)
  {
    return new ApBankResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $bank = $this->find($data['id']);
    $bank->update($data);
    return new ApBankResource($bank);
  }

  public function destroy($id)
  {
    $bank = $this->find($id);
    DB::transaction(function () use ($bank) {
      $bank->delete();
    });
    return response()->json(['message' => 'Chequera eliminado correctamente']);
  }
}
