<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\IndexRequest;

class IndexApCampaignRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'area_id'       => 'nullable|integer|exists:ap_masters,id',
      'discount_type' => 'nullable|string|in:fixed,percentage',
      'status'        => 'nullable|boolean',
    ];
  }
}
