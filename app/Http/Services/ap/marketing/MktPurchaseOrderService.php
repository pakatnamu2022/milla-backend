<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktPurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\marketing\MktPurchaseOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktPurchaseOrderService extends BaseService implements BaseServiceInterface
{
  // Transiciones permitidas desde cada estado
  const ALLOWED_TRANSITIONS = [
    MktPurchaseOrder::STATUS_DRAFT           => [MktPurchaseOrder::STATUS_SENT, MktPurchaseOrder::STATUS_CANCELLED],
    MktPurchaseOrder::STATUS_SENT            => [MktPurchaseOrder::STATUS_IN_EXECUTION, MktPurchaseOrder::STATUS_CANCELLED],
    MktPurchaseOrder::STATUS_IN_EXECUTION    => [MktPurchaseOrder::STATUS_PENDING_SUPPORT, MktPurchaseOrder::STATUS_CANCELLED],
    MktPurchaseOrder::STATUS_PENDING_SUPPORT => [MktPurchaseOrder::STATUS_SUPPORTED, MktPurchaseOrder::STATUS_CANCELLED],
    MktPurchaseOrder::STATUS_SUPPORTED       => [MktPurchaseOrder::STATUS_PENDING_BILLING, MktPurchaseOrder::STATUS_CANCELLED],
    MktPurchaseOrder::STATUS_PENDING_BILLING => [MktPurchaseOrder::STATUS_BILLED, MktPurchaseOrder::STATUS_CANCELLED],
    MktPurchaseOrder::STATUS_BILLED          => [MktPurchaseOrder::STATUS_CLOSED],
    MktPurchaseOrder::STATUS_CLOSED          => [],
    MktPurchaseOrder::STATUS_CANCELLED       => [],
  ];

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktPurchaseOrder::query()->with(['activity:id,name', 'currency:id,name,code,symbol', 'supplier:id,full_name', 'electronicDocument:id,full_number,status,enlace_del_pdf']),
      $request,
      MktPurchaseOrder::filters,
      MktPurchaseOrder::sorts,
      MktPurchaseOrderResource::class
    );
  }

  public function find(int $id): MktPurchaseOrder
  {
    $order = MktPurchaseOrder::with(['activity', 'proposal', 'supplier', 'currency', 'supports', 'electronicDocument'])->find($id);
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

    if (!in_array($order->status, [MktPurchaseOrder::STATUS_DRAFT, MktPurchaseOrder::STATUS_SENT])) {
      throw new Exception('Solo se pueden editar órdenes en estado borrador o enviado');
    }

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
      if ($order->status !== MktPurchaseOrder::STATUS_DRAFT) {
        throw new Exception('Solo se pueden eliminar órdenes en estado borrador');
      }
      $order->delete();
      DB::commit();
      return ['message' => 'Orden de compra eliminada correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function changeStatus(int $id, string $status, array $extra = []): MktPurchaseOrderResource
  {
    $order = $this->find($id);

    // Validar que la transición está permitida
    $allowed = self::ALLOWED_TRANSITIONS[$order->status] ?? [];
    if (!in_array($status, $allowed)) {
      throw new Exception(
        "No se puede pasar de '{$order->status_label}' a '" . (MktPurchaseOrder::STATUS_LABELS[$status] ?? $status) . "'"
      );
    }

    // Validaciones específicas por transición destino
    match ($status) {
      MktPurchaseOrder::STATUS_SENT => $this->validateForSent($order),
      MktPurchaseOrder::STATUS_SUPPORTED => $this->validateForSupported($order),
      MktPurchaseOrder::STATUS_BILLED => $this->validateForBilled($order, $extra),
      default => null,
    };

    DB::beginTransaction();
    try {
      $updateData = ['status' => $status];

      if ($status === MktPurchaseOrder::STATUS_SENT) {
        $updateData['sent_at'] = now();
      }
      if ($status === MktPurchaseOrder::STATUS_BILLED) {
        $updateData['billed_at'] = now();
        $updateData['electronic_document_id'] = $extra['electronic_document_id'];
      }

      $order->update($updateData);
      $order->load(['activity', 'proposal', 'supplier', 'currency', 'electronicDocument']);
      DB::commit();
      return new MktPurchaseOrderResource($order);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  private function validateForSent(MktPurchaseOrder $order): void
  {
    if (!$order->supplier_id || !$order->amount || !$order->issue_date) {
      throw new Exception('La OC debe tener proveedor, monto y fecha de emisión antes de enviarse');
    }
  }

  private function validateForSupported(MktPurchaseOrder $order): void
  {
    $supportsCount = $order->supports()->count();
    if ($supportsCount === 0) {
      throw new Exception('Debe registrar al menos un sustento antes de marcar la OC como sustentada');
    }
  }

  private function validateForBilled(MktPurchaseOrder $order, array $extra): void
  {
    if (empty($extra['electronic_document_id'])) {
      throw new Exception('Debe asociar un documento electrónico (factura) para marcar la OC como facturada');
    }

    $doc = ElectronicDocument::find($extra['electronic_document_id']);
    if (!$doc) {
      throw new Exception('El documento electrónico no existe');
    }
    if ($doc->status !== 'accepted') {
      throw new Exception('El documento electrónico debe estar aceptado por SUNAT para facturar la OC');
    }
  }
}
