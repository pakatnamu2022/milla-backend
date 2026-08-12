<?php

namespace App\Http\Resources\ap\marketing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktBudgetResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'plan_id'          => $this->plan_id,
      'plan'             => $this->whenLoaded('plan', fn() => [
        'id'   => $this->plan->id,
        'name' => $this->plan->name,
        'year' => $this->plan->year,
      ]),
      'type'             => $this->type,
      'period_month'     => $this->period_month,
      'currency_id'      => $this->currency_id,
      'currency'         => $this->whenLoaded('currency', fn() => [
        'id'     => $this->currency->id,
        'name'   => $this->currency->name,
        'code'   => $this->currency->code,
        'symbol' => $this->currency->symbol,
      ]),
      'amount_estimated' => $this->amount_estimated,
      'amount_executed'  => $this->amount_executed,
      'status'           => $this->status,
      'notes'            => $this->notes,
      'fundings'         => MktBudgetFundingResource::collection($this->whenLoaded('fundings')),
      'created_by'       => $this->created_by,
      'updated_by'       => $this->updated_by,
      'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'       => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
