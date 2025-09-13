<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignCompanyBranchResource extends JsonResource
{
  public static $wrap = null;

  public function toArray(Request $request): array
  {
    $first = $this->first();

    return [
      'sede_id' => $first->sede->id,
      'sede' => $first->sede->abreviatura,
      'year' => $first->year,
      'month' => $first->month,
      'assigned_workers' => $this->map(function ($item) {
        return [
          'id' => $item->worker->id,
          'name' => $item->worker->nombre_completo,
        ];
      })->values(),
      'status' => $first->status,
    ];
  }
}
