<?php

namespace App\Http\Resources\ap\marketing;

use App\Models\ap\marketing\MktSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktSupportResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                => $this->id,
      'activity_id'       => $this->activity_id,
      'activity'          => $this->whenLoaded('activity', fn() => [
        'id'   => $this->activity->id,
        'name' => $this->activity->name,
      ]),
      'purchase_order_id' => $this->purchase_order_id,
      'purchase_order'    => $this->whenLoaded('purchaseOrder', fn() => [
        'id'     => $this->purchaseOrder->id,
        'number' => $this->purchaseOrder->number,
        'status' => $this->purchaseOrder->status,
      ]),
      'type'              => $this->type,
      'type_label'        => MktSupport::TYPE_LABELS[$this->type] ?? $this->type,
      'document_series'   => $this->document_series,
      'document_number'   => $this->document_number,
      'issue_date'        => $this->issue_date?->format('Y-m-d'),
      'supplier_id'       => $this->supplier_id,
      'supplier'          => $this->whenLoaded('supplier', fn() => [
        'id'        => $this->supplier->id,
        'full_name' => $this->supplier->full_name,
      ]),
      'currency_id'       => $this->currency_id,
      'currency'          => $this->whenLoaded('currency', fn() => [
        'id'     => $this->currency->id,
        'name'   => $this->currency->name,
        'code'   => $this->currency->code,
        'symbol' => $this->currency->symbol,
      ]),
      'amount'            => $this->amount,
      'file_path'         => $this->file_path,
      'notes'             => $this->notes,
      'created_by'        => $this->created_by,
      'updated_by'        => $this->updated_by,
      'created_at'        => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
