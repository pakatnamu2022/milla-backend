<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\DiscountRequestsOrderQuotationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountRequestsOrderQuotationService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      DiscountRequestsOrderQuotation::class,
      $request,
      DiscountRequestsOrderQuotation::filters,
      DiscountRequestsOrderQuotation::sorts,
      DiscountRequestsOrderQuotationResource::class,
    );
  }

  public function find($id): DiscountRequestsOrderQuotation
  {
    $record = DiscountRequestsOrderQuotation::find($id);
    if (!$record) {
      throw new Exception('Solicitud de descuento no encontrada.');
    }
    return $record;
  }

  public function store(mixed $data): DiscountRequestsOrderQuotationResource
  {
    $type = $data['type'];

    if ($type === DiscountRequestsOrderQuotation::TYPE_GLOBAL) {
      $exists = DiscountRequestsOrderQuotation::where('ap_order_quotation_id', $data['ap_order_quotation_id'])
        ->where('type', DiscountRequestsOrderQuotation::TYPE_GLOBAL)
        ->where('item_type', $data['item_type'])
        ->exists();

      if ($exists) {
        throw new Exception('Ya existe un descuento GLOBAL activo para esta cotización. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    if ($type === DiscountRequestsOrderQuotation::TYPE_PARTIAL) {
      $data['ap_order_quotation_id'] = ApOrderQuotationDetails::findOrFail($data['ap_order_quotation_detail_id'])->order_quotation_id;
      $exists = DiscountRequestsOrderQuotation::where('ap_order_quotation_detail_id', $data['ap_order_quotation_detail_id'])
        ->where('type', DiscountRequestsOrderQuotation::TYPE_PARTIAL)
        ->where('item_type', $data['item_type'])
        ->exists();

      if ($exists) {
        throw new Exception('Ya existe un descuento PARTIAL activo para este detalle de cotización. Debe eliminarlo antes de crear uno nuevo.');
      }
    }

    $record = DB::transaction(function () use ($data) {
      return DiscountRequestsOrderQuotation::create([
        'type' => $data['type'],
        'ap_order_quotation_id' => $data['ap_order_quotation_id'] ?? null,
        'ap_order_quotation_detail_id' => $data['ap_order_quotation_detail_id'] ?? null,
        'manager_id' => auth()->id(),
        'request_date' => now(),
        'requested_discount_percentage' => $data['requested_discount_percentage'],
        'requested_discount_amount' => $data['requested_discount_amount'],
        'item_type' => $data['item_type'],
      ]);
    });

    return new DiscountRequestsOrderQuotationResource($record);
  }

  public function show($id): DiscountRequestsOrderQuotationResource
  {
    return new DiscountRequestsOrderQuotationResource($this->find($id));
  }

  public function update(mixed $data): DiscountRequestsOrderQuotationResource
  {
    $record = $this->findNotApproved($data['id']);

    DB::transaction(function () use ($record, $data) {
      $record->update([
        'requested_discount_percentage' => $data['requested_discount_percentage'] ?? $record->requested_discount_percentage,
        'requested_discount_amount' => $data['requested_discount_amount'] ?? $record->requested_discount_amount,
      ]);
    });

    return new DiscountRequestsOrderQuotationResource($record->fresh());
  }

  public function destroy($id): void
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->delete();
    });
  }

  public function approve($id): DiscountRequestsOrderQuotationResource
  {
    $record = $this->findNotApproved($id);

    DB::transaction(function () use ($record) {
      $record->update([
        'approved_id' => auth()->id(),
        'approval_date' => now(),
      ]);
    });

    return new DiscountRequestsOrderQuotationResource($record->fresh());
  }

  private function findNotApproved($id): DiscountRequestsOrderQuotation
  {
    $record = $this->find($id);

    if (!is_null($record->approved_id) || !is_null($record->approval_date)) {
      throw new Exception('No se puede modificar una solicitud de descuento que ya ha sido aprobada.');
    }

    return $record;
  }
}
