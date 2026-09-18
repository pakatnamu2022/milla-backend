<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Matriz mensual de vehículos vendidos (tabla dinámica): Marca > Familia > Versión
 * en filas, meses en columnas y "Cuenta de VIN" como valor.
 *
 * La venta se cuenta por el mes de emisión del comprobante (factura/boleta vigente),
 * con las mismas reglas del reporte de facturación de vehículos:
 *  - Un comprobante anulado por NC (neto ≈ 0) no cuenta.
 *  - Refacturación: la venta se atribuye al periodo del PRIMER comprobante de la solicitud.
 *
 * SHOP = tienda (ap_masters) y SEDE = sucursal (config_sede.suc_abrev) de la serie
 * del comprobante vigente, igual que en el reporte de vehículos.
 */
class ApVehicleSalesMatrixService
{
  private const NO_FAMILY = 'SIN FAMILIA';
  private const NO_BRAND = 'SIN MARCA';
  private const NO_MODEL = 'SIN VERSIÓN';

  /**
   * @param int        $year
   * @param array|null $shopIds IDs de tienda (SHOP)
   * @param array|null $sedeIds IDs de config_sede (SEDE)
   */
  public function generate(int $year, ?array $shopIds = null, ?array $sedeIds = null): array
  {
    $detail = $this->buildDetail($year, $shopIds, $sedeIds);

    $lastMonth = $year === (int)now()->year ? (int)now()->month : 12;

    return [
      'year'        => $year,
      'last_month'  => $lastMonth,
      'filters'     => [
        'shop' => $this->describeFilter($shopIds, 'ap_masters', 'description'),
        'sede' => $this->describeFilter($sedeIds, 'config_sede', 'suc_abrev'),
      ],
      'rows'        => $this->buildTree($detail),
      'totals'      => $this->sumMonths($detail),
      'detail'      => $detail->values()->all(),
    ];
  }

  /**
   * Una fila por venta (VIN) atribuida al año pedido.
   */
  private function buildDetail(int $year, ?array $shopIds, ?array $sedeIds): Collection
  {
    $tol = ElectronicDocument::ROUNDING_TOLERANCE;
    $today = now()->toDateString();

    $invoices = DB::table('ap_billing_electronic_documents as d')
      ->leftJoin('assign_sales_series as s', 'd.series_id', '=', 's.id')
      ->leftJoin('config_sede as c', 's.sede_id', '=', 'c.id')
      ->leftJoin('ap_masters as shop', 'c.shop_id', '=', 'shop.id')
      ->whereNull('d.deleted_at')
      ->whereNotNull('d.purchase_request_quote_id')
      ->whereIn('d.sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA,
      ])
      ->where('d.is_advance_payment', false)
      ->where('d.anulado', false)
      ->orderBy('d.fecha_de_emision')
      ->orderBy('d.id')
      ->get([
        'd.id',
        'd.purchase_request_quote_id as prq_id',
        'd.full_number',
        'd.total',
        'd.aceptada_por_sunat',
        'd.fecha_de_emision',
        'c.id as sede_id',
        'c.suc_abrev as sede',
        'shop.id as shop_id',
        'shop.description as shop',
      ]);

    if ($invoices->isEmpty()) {
      return collect();
    }

    $invoiceIds = $invoices->pluck('id')->all();
    $ncByInvoice = $this->noteTotals($invoiceIds, ElectronicDocument::TYPE_NOTA_CREDITO, $today);
    $ndByInvoice = $this->noteTotals($invoiceIds, ElectronicDocument::TYPE_NOTA_DEBITO, $today);

    $vehicles = $this->vehiclesByQuote($invoices->pluck('prq_id')->unique()->all());

    $rows = collect();

    foreach ($invoices->groupBy('prq_id') as $prqId => $prqInvoices) {
      $vehicle = $vehicles->get($prqId);
      if (!$vehicle || !$vehicle->vin) {
        continue;
      }

      $enriched = $prqInvoices->map(function ($inv) use ($ncByInvoice, $ndByInvoice) {
        $totalNc = (float)($ncByInvoice[$inv->id] ?? 0);
        $totalNd = (float)($ndByInvoice[$inv->id] ?? 0);
        return [
          'invoice' => $inv,
          'total_nc' => $totalNc,
          'net'      => (float)$inv->total - $totalNc + $totalNd,
        ];
      });

      $cancelled = $enriched->filter(fn($e) => $e['total_nc'] > $tol && round($e['net'], 2) <= $tol);
      $vigentes = $enriched->reject(fn($e) => $e['total_nc'] > $tol && round($e['net'], 2) <= $tol);

      if ($vigentes->isEmpty()) {
        continue;
      }

      // Vigente preferido: aceptado por SUNAT y, a igualdad, el más reciente.
      $chosen = $vigentes
        ->sortBy(fn($e) => sprintf(
          '%d-%s-%012d',
          $e['invoice']->aceptada_por_sunat ? 1 : 0,
          substr((string)$e['invoice']->fecha_de_emision, 0, 10),
          $e['invoice']->id
        ))
        ->last()['invoice'];

      $first = $enriched->first()['invoice'];
      $attributed = substr((string)($cancelled->isNotEmpty() ? $first->fecha_de_emision : $chosen->fecha_de_emision), 0, 10);

      if ((int)substr($attributed, 0, 4) !== $year) {
        continue;
      }
      if (!empty($shopIds) && !in_array((int)$chosen->shop_id, array_map('intval', $shopIds), true)) {
        continue;
      }
      if (!empty($sedeIds) && !in_array((int)$chosen->sede_id, array_map('intval', $sedeIds), true)) {
        continue;
      }

      $rows->push([
        'vin'          => $vehicle->vin,
        'brand'        => $vehicle->brand ?: self::NO_BRAND,
        'family'       => $vehicle->family ?: self::NO_FAMILY,
        'model'        => $vehicle->model ?: self::NO_MODEL,
        'shop'         => $chosen->shop ?: 'SIN SHOP',
        'sede'         => $chosen->sede ?: 'SIN SEDE',
        'invoice'      => $chosen->full_number,
        'invoice_date' => substr((string)$chosen->fecha_de_emision, 0, 10),
        'sale_date'    => $attributed,
        'month'        => (int)substr($attributed, 5, 2),
      ]);
    }

    // Un VIN cuenta una sola vez por mes aunque tenga más de una solicitud.
    return $rows
      ->unique(fn($r) => $r['vin'] . '|' . $r['month'])
      ->sortBy([['brand', 'asc'], ['family', 'asc'], ['model', 'asc'], ['sale_date', 'asc']])
      ->values();
  }

  /**
   * Suma de NC/ND por comprobante original.
   * Las NC solo cuentan si fueron aceptadas por SUNAT o se emitieron hoy.
   */
  private function noteTotals(array $invoiceIds, int $type, string $today): array
  {
    $result = [];

    foreach (array_chunk($invoiceIds, 1000) as $chunk) {
      $query = DB::table('ap_billing_electronic_documents')
        ->whereNull('deleted_at')
        ->where('sunat_concept_document_type_id', $type)
        ->where('is_advance_payment', false)
        ->where('anulado', false)
        ->whereIn('original_document_id', $chunk);

      if ($type === ElectronicDocument::TYPE_NOTA_CREDITO) {
        $query->where(function ($q) use ($today) {
          $q->where('aceptada_por_sunat', true)->orWhereDate('fecha_de_emision', $today);
        });
      } else {
        $query->where('aceptada_por_sunat', true);
      }

      foreach ($query->groupBy('original_document_id')
                 ->selectRaw('original_document_id as id, SUM(total) as total')
                 ->get() as $note) {
        $result[$note->id] = ($result[$note->id] ?? 0) + (float)$note->total;
      }
    }

    return $result;
  }

  /**
   * Vehículo (VIN, marca, familia, versión) de cada solicitud de compra.
   */
  private function vehiclesByQuote(array $prqIds): Collection
  {
    $result = collect();

    foreach (array_chunk($prqIds, 1000) as $chunk) {
      $result = $result->union(
        DB::table('purchase_request_quote as prq')
          ->join('ap_vehicles as v', 'prq.ap_vehicle_id', '=', 'v.id')
          ->leftJoin('ap_models_vn as m', 'v.ap_models_vn_id', '=', 'm.id')
          ->leftJoin('ap_families as f', 'm.family_id', '=', 'f.id')
          ->leftJoin('ap_vehicle_brand as b', 'f.brand_id', '=', 'b.id')
          ->whereIn('prq.id', $chunk)
          ->get([
            'prq.id as prq_id',
            'v.vin',
            'm.version as model',
            'f.description as family',
            'b.name as brand',
          ])
          ->keyBy('prq_id')
      );
    }

    return $result;
  }

  /**
   * Árbol Marca > Familia > Versión con conteo por mes y total.
   */
  private function buildTree(Collection $detail): array
  {
    $node = fn(string $name, Collection $items, array $children = []) => [
      'name'     => $name,
      'months'   => $this->monthCounts($items),
      'total'    => $items->count(),
      'children' => $children,
    ];

    return $detail
      ->groupBy('brand')
      ->map(fn(Collection $brandItems, string $brand) => $node(
        $brand,
        $brandItems,
        $brandItems
          ->groupBy('family')
          ->map(fn(Collection $familyItems, string $family) => $node(
            $family,
            $familyItems,
            $familyItems
              ->groupBy('model')
              ->map(fn(Collection $modelItems, string $model) => $node($model, $modelItems))
              ->values()
              ->all()
          ))
          ->values()
          ->all()
      ))
      ->values()
      ->all();
  }

  private function sumMonths(Collection $detail): array
  {
    return [
      'months' => $this->monthCounts($detail),
      'total'  => $detail->count(),
    ];
  }

  /** @return int[] Conteo de los 12 meses (índice 0 = enero). */
  private function monthCounts(Collection $items): array
  {
    $counts = array_fill(0, 12, 0);
    foreach ($items as $item) {
      $counts[$item['month'] - 1]++;
    }
    return $counts;
  }

  /** Texto del filtro aplicado ("(Todas)" si no hay filtro). */
  private function describeFilter(?array $ids, string $table, string $column): string
  {
    if (empty($ids)) {
      return '(Todas)';
    }

    $names = DB::table($table)->whereIn('id', $ids)->pluck($column)->unique()->values()->all();

    return count($names) === 1 ? $names[0] : '(Varios elementos)';
  }
}
