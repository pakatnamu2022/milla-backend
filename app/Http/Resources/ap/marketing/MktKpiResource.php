<?php

namespace App\Http\Resources\ap\marketing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktKpiResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'           => $this->id,
      'activity_id'  => $this->activity_id,
      'activity'     => $this->whenLoaded('activity', fn() => [
        'id'   => $this->activity->id,
        'name' => $this->activity->name,
      ]),
      'period_month' => $this->period_month,
      'period_year'  => $this->period_year,
      'leads'        => $this->leads,
      'sales'        => $this->sales,
      'investment'   => $this->investment,
      'currency_id'  => $this->currency_id,
      'currency'     => $this->whenLoaded('currency', fn() => [
        'id'     => $this->currency->id,
        'name'   => $this->currency->name,
        'code'   => $this->currency->code,
        'symbol' => $this->currency->symbol,
      ]),
      'notes'        => $this->notes,
      'created_by'   => $this->created_by,
      'updated_by'   => $this->updated_by,
      'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
