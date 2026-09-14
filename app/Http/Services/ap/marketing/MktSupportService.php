<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktSupportResource;
use App\Http\Services\ap\marketing\Concerns\NormalizesUppercaseText;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\marketing\MktSupport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktSupportService extends BaseService implements BaseServiceInterface
{
  use NormalizesUppercaseText;

  const UPPERCASE_FIELDS = ['document_series', 'document_number', 'notes'];

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktSupport::query()->with(['activity:id,name', 'purchaseOrder:id,number,status', 'currency:id,name,code,symbol', 'supplier:id,full_name']),
      $request,
      MktSupport::filters,
      MktSupport::sorts,
      MktSupportResource::class
    );
  }

  public function find(int $id): MktSupport
  {
    $support = MktSupport::with(['activity', 'purchaseOrder', 'supplier', 'currency'])->find($id);
    if (!$support) {
      throw new Exception('Soporte no encontrado');
    }
    return $support;
  }

  public function store(mixed $data): MktSupportResource
  {
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
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

  public function update(mixed $data): MktSupportResource
  {
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
    $support = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $support->update($data);
      $support->load(['activity', 'purchaseOrder', 'supplier', 'currency']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $support = $this->find($id);
      if ($support->purchase_order_id) {
        throw new Exception('No se puede eliminar un sustento vinculado a una orden de compra');
      }
      $support->delete();
      DB::commit();
      return ['message' => 'Soporte eliminado correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
