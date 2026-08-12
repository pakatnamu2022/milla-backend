<?php

namespace App\Http\Resources\ap\marketing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktActivityResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'budget_id'        => $this->budget_id,
      'budget'           => $this->whenLoaded('budget', fn() => [
        'id'   => $this->budget->id,
        'type' => $this->budget->type,
      ]),
      'activity_type'    => $this->activity_type,
      'name'             => $this->name,
      'description'      => $this->description,
      'objective'        => $this->objective,
      'responsible'      => $this->responsible,
      'channel'          => $this->channel,
      'start_date'       => $this->start_date?->format('Y-m-d'),
      'end_date'         => $this->end_date?->format('Y-m-d'),
      'currency_id'      => $this->currency_id,
      'currency'         => $this->whenLoaded('currency', fn() => [
        'id'     => $this->currency->id,
        'name'   => $this->currency->name,
        'code'   => $this->currency->code,
        'symbol' => $this->currency->symbol,
      ]),
      'estimated_amount' => $this->estimated_amount,
      'supplier_id'      => $this->supplier_id,
      'supplier'         => $this->whenLoaded('supplier', fn() => [
        'id'   => $this->supplier->id,
        'name' => $this->supplier->name,
      ]),
      'status'           => $this->status,
      'notes'            => $this->notes,
      'locations'        => MktActivityLocationResource::collection($this->whenLoaded('locations')),
      'proposals'        => MktProposalResource::collection($this->whenLoaded('proposals')),
      'kpis'             => MktKpiResource::collection($this->whenLoaded('kpis')),
      'created_by'       => $this->created_by,
      'updated_by'       => $this->updated_by,
      'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'       => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
