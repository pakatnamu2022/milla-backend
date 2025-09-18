<?php

namespace App\Http\Resources\ap\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSeriesAssignmentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'worker_id' => $this->id,
      'worker_name' => $this->nombre_completo,
      'vouchers' => $this->whenLoaded('vouchers', function () {
        return $this->vouchers->map(function ($voucher) {
          return [
            'id' => $voucher->id,
            'series' => $voucher->series,
            'sede' => $voucher->sede->abreviatura,
            'type_receipt' => $voucher->typeReceipt?->description,
            'type_operation' => $voucher->typeOperation?->description,
          ];
        });
      })
    ];
  }
}
