<?php

namespace App\Http\Services\ap\compras;

use App\Models\ap\ApMasters;
use App\Models\ap\compras\AccountPayable;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Support\Collection;

class ApPurchaseOrderReportService
{
  public function generate(
    string $fechaInicio,
    string $fechaFin,
    ?array $sedeIds = null
  ): array {
    $orders = PurchaseOrder::with([
      'sede',
      'supplier',
      'vehicle.model.family.brand',
      'vehicle.model.vehicleType',
      'vehicle.color',
      'vehicle.electronicDocumentParent',
    ])
      ->whereBetween('emission_date', [$fechaInicio, $fechaFin])
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->when($sedeIds, fn($q) => $q->whereIn('sede_id', $sedeIds))
      ->whereNull('deleted_at')
      ->orderBy('emission_date')
      ->orderBy('number')
      ->get();

    return [
      'orders'          => $orders,
      'cuentasPorPagar' => $this->buildCxpMap(),
    ];
  }

  private function buildCxpMap(): array
  {
    return AccountPayable::where('monto_sin_aplicar', '>', 0)
      ->get(['documento', 'monto', 'monto_sin_aplicar'])
      ->mapWithKeys(function ($row) {
        // Documento: "F004-00000329-FAC" → clave: "F004-00000329"
        $parts = explode('-', $row->documento);
        $key   = count($parts) >= 3 ? $parts[0] . '-' . $parts[1] : $row->documento;

        return [$key => [
          'monto'           => (float)$row->monto,
          'montoSinAplicar' => (float)$row->monto_sin_aplicar,
        ]];
      })
      ->toArray();
  }
}
