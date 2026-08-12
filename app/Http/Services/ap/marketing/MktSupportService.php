<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktSupportResource;
use App\Http\Services\BaseService;
use App\Models\ap\marketing\MktSupport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktSupportService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktSupport::class,
      $request,
      MktSupport::filters,
      MktSupport::sorts,
      MktSupportResource::class
    );
  }

  private function find(int $id): MktSupport
  {
    $support = MktSupport::with(['activity', 'purchaseOrder', 'supplier', 'currency'])->find($id);
    if (!$support) {
      throw new Exception('Soporte no encontrado');
    }
    return $support;
  }

  public function store(mixed $data): MktSupportResource
  {
    DB::beginTransaction();
    try {
      $support = MktSupport::create($data);
      $support->load(['activity', 'purchaseOrder', 'supplier', 'currency']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktSupportResource
  {
    return new MktSupportResource($this->find($id));
  }

  public function destroy(int $id)
  {
    $support = $this->find($id);
    $support->delete();
    return response()->json(['message' => 'Soporte eliminado correctamente']);
  }
}
