<?php

namespace App\Http\Services\ap\compras;

use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Support\Collection;

class ApPurchaseOrderReportService
{
  public function generate(
    string $fechaInicio,
    string $fechaFin,
    ?array $sedeIds = null
  ): Collection {
    return PurchaseOrder::with([
      'sede',
      'supplier',
      'vehicle.model.family.brand',
      'vehicle.model.vehicleType',
      'vehicle.color',
      'vehicle.electronicDocumentParent',
    ])
      ->whereBetween('emission_date', [$fechaInicio, $fechaFin])
      ->when($sedeIds, fn($q) => $q->whereIn('sede_id', $sedeIds))
      ->whereNull('deleted_at')
      ->orderBy('emission_date')
      ->orderBy('number')
      ->get();
  }
}
