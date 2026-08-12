<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktPurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\marketing\MktPurchaseOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktPurchaseOrderService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktPurchaseOrder::query()->with(['activity:id,name', 'currency:id,name,code,symbol', 'supplier:id,full_name']),
      $request,
      MktPurchaseOrder::filters,
      MktPurchaseOrder::sorts,
      MktPurchaseOrderResource::class
    );
  }

  public function find(int $id): MktPurchaseOrder
  {
    $order = MktPurchaseOrder::with(['activity', 'proposal', 'supplier', 'currency', 'supports'])->find($id);
    if (!$order) {
      throw new Exception('Orden de compra no encontrada');
    }
    return $order;
  }

  public function store(mixed $data): MktPurchaseOrderResource
  {
    DB::beginTransaction();
    try {
      $order = MktPurchaseOrder::create($data);
      $order->load(['activity', 'proposal', 'supplier', 'currency']);
      DB::commit();
      return new MktPurchaseOrderResource($order);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktPurchaseOrderResource
  {
    return new MktPurchaseOrderResource($this->find($id));
  }

  public function update(mixed $data): MktPurchaseOrderResource
  {
    $order = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $order->update($data);
      $order->load(['activity', 'proposal', 'supplier', 'currency']);
      DB::commit();
      return new MktPurchaseOrderResource($order);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $order = $this->find($id);
      $order->delete();
      DB::commit();
      return ['message' => 'Orden de compra eliminada correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function changeStatus(int $id, string $status): MktPurchaseOrderResource
  {
    $validStatuses = [
      MktPurchaseOrder::STATUS_DRAFT,
      MktPurchaseOrder::STATUS_SENT,
      MktPurchaseOrder::STATUS_IN_EXECUTION,
      MktPurchaseOrder::STATUS_PENDING_SUPPORT,
      MktPurchaseOrder::STATUS_SUPPORTED,
      MktPurchaseOrder::STATUS_PENDING_BILLING,
      MktPurchaseOrder::STATUS_BILLED,
      MktPurchaseOrder::STATUS_CLOSED,
      MktPurchaseOrder::STATUS_CANCELLED,
    ];

    if (!in_array($status, $validStatuses)) {
      throw new Exception('Estado no válido');
    }

    $order = $this->find($id);
    DB::beginTransaction();
    try {
      $updateData = ['status' => $status];

      if ($status === MktPurchaseOrder::STATUS_SENT) {
        $updateData['sent_at'] = now();
      }
      if ($status === MktPurchaseOrder::STATUS_BILLED) {
        $updateData['billed_at'] = now();
      }

      $order->update($updateData);
      DB::commit();
      return new MktPurchaseOrderResource($order);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
