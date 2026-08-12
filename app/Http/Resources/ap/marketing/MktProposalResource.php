<?php

namespace App\Http\Resources\ap\marketing;

use App\Models\ap\marketing\MktProposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktProposalResource extends JsonResource
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
      'amount'       => $this->amount,
      'description'  => $this->description,
      'status'       => $this->status,
      'status_label' => MktProposal::STATUS_LABELS[$this->status] ?? $this->status,
      'notes'        => $this->notes,
      'reviewed_by'  => $this->reviewed_by,
      'reviewed_by_user' => $this->whenLoaded('reviewedBy', fn() => [
        'id'   => $this->reviewedBy->id,
        'name' => $this->reviewedBy->name,
      ]),
      'reviewed_at'  => $this->reviewed_at?->format('Y-m-d H:i:s'),
      'created_by'   => $this->created_by,
      'updated_by'   => $this->updated_by,
      'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
