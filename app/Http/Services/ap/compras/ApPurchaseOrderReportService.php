<?php

namespace App\Http\Services\ap\compras;

use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
      'cuentasPorPagar' => $this->fetchCuentasPorPagar(),
    ];
  }

  private function fetchCuentasPorPagar(): array
  {
    try {
      $pdo  = DB::connection('dbtp3')->getPdo();
      $stmt = $pdo->prepare('EXEC SP_GP_ReporteDocumentosNoAplicadosCuentaPorPagar');
      $stmt->execute();

      $rows = [];
      do {
        if ($stmt->columnCount() > 0) {
          $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
          break;
        }
      } while ($stmt->nextRowset());

      $map = [];
      foreach ($rows as $row) {
        $doc    = trim((string)($row->Documento ?? ''));
        $parts  = explode('-', $doc);
        // Documento formato: "F004-00000329-FAC" → clave: "F004-00000329"
        $key    = count($parts) >= 3
          ? $parts[0] . '-' . $parts[1]
          : $doc;

        $map[$key] = [
          'monto'           => (float)($row->Monto ?? 0),
          'montoSinAplicar' => (float)($row->MontoSinAplicar ?? 0),
        ];
      }

      return $map;
    } catch (\Throwable) {
      return [];
    }
  }
}
