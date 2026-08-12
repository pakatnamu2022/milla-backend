<?php

namespace App\Http\Resources\ap\marketing;

use App\Models\ap\marketing\MktPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktPurchaseOrderResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'activity_id' => $this->activity_id,
      'activity'    => $this->whenLoaded('activity', fn() => [
        'id'   => $this->activity->id,
        'name' => $this->activity->name,
      ]),
      'proposal_id' => $this->proposal_id,
      'proposal'    => $this->whenLoaded('proposal', fn() => [
        'id'     => $this->proposal->id,
        'amount' => $this->proposal->amount,
        'status' => $this->proposal->status,
      ]),
      'supplier_id' => $this->supplier_id,
      'supplier'    => $this->whenLoaded('supplier', fn() => [
        'id'        => $this->supplier->id,
        'full_name' => $this->supplier->full_name,
      ]),
      'currency_id' => $this->currency_id,
      'currency'    => $this->whenLoaded('currency', fn() => [
        'id'     => $this->currency->id,
        'name'   => $this->currency->name,
        'code'   => $this->currency->code,
        'symbol' => $this->currency->symbol,
      ]),
      'number'      => $this->number,
      'amount'      => $this->amount,
      'issue_date'  => $this->issue_date?->format('Y-m-d'),
      'status'       => $this->status,
      'status_label' => MktPurchaseOrder::STATUS_LABELS[$this->status] ?? $this->status,
      'sent_at'     => $this->sent_at?->format('Y-m-d H:i:s'),
      'billed_at'   => $this->billed_at?->format('Y-m-d H:i:s'),
      'notes'       => $this->notes,
      'supports'    => MktSupportResource::collection($this->whenLoaded('supports')),
      'created_by'  => $this->created_by,
      'updated_by'  => $this->updated_by,
      'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'  => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
