<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\ApMasters;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapOrderQuotationsStatusCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ap:map-order-quotations-status
    {--preview : Muestra cuántas cotizaciones se actualizarían por status, sin modificar datos}
    {--apply : Ejecuta la actualización real de status_id}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Mapea el status_id de ap_order_quotations según el valor legacy de la columna status (no la modifica), usando ap_masters (type=STATUS_ORDER_QUOTE) como referencia';

  /**
   * Mapa: valor legacy de la columna `status` => id de ap_masters correspondiente.
   */
  private const STATUS_MAP = [
    ApOrderQuotations::STATUS_APERTURADO => ApMasters::STATUS_ORDER_QUOTE_APERTURADO,
    ApOrderQuotations::STATUS_POR_FACTURAR => ApMasters::STATUS_ORDER_QUOTE_FACTURAR,
    ApOrderQuotations::STATUS_FACTURADO => ApMasters::STATUS_ORDER_QUOTE_FACTURADO,
    ApOrderQuotations::STATUS_SEGMENTADA => ApMasters::STATUS_ORDER_QUOTE_SEGMENTADO,
    ApOrderQuotations::STATUS_DESCARTADO => ApMasters::STATUS_ORDER_QUOTE_DESCARTADO,
  ];

  public function handle(): int
  {
    $preview = (bool) $this->option('preview');
    $apply = (bool) $this->option('apply');

    if (!$preview && !$apply) {
      $this->error('Debes especificar una opción: --preview (previsualizar) o --apply (actualizar).');
      return 1;
    }

    $unmapped = $this->findUnmappedMasters();
    if (!empty($unmapped)) {
      $this->error('Los siguientes valores legacy de "status" no tienen un ap_masters válido asociado. Corrige el mapeo o crea el master antes de continuar:');
      $this->table(['status (legacy)', 'ap_masters id esperado'], $unmapped);
      return 1;
    }

    $summary = $this->buildSummary();

    if (empty($summary)) {
      $this->info('No se encontraron cotizaciones para evaluar.');
      return 0;
    }

    $this->table(
      ['status (legacy)', 'status_id esperado', 'total filas', 'ya correctas', 'a corregir'],
      collect($summary)->map(fn($row) => [
        $row['status'],
        $row['expected_status_id'],
        $row['total'],
        $row['already_correct'],
        $row['to_update'],
      ])->toArray()
    );

    $totalToUpdate = array_sum(array_column($summary, 'to_update'));
    $this->newLine();
    $this->info("Total a actualizar: {$totalToUpdate}");

    if ($preview || $totalToUpdate === 0) {
      return 0;
    }

    if (!$this->confirm("Esto actualizará status_id en {$totalToUpdate} cotización(es), sin tocar la columna status. ¿Continuar?")) {
      $this->info('Operación cancelada.');
      return 0;
    }

    $updated = 0;
    foreach (self::STATUS_MAP as $status => $expectedStatusId) {
      $updated += DB::table('ap_order_quotations')
        ->where('status', $status)
        ->where('status_id', '!=', $expectedStatusId)
        ->update(['status_id' => $expectedStatusId]);
    }

    $this->info("Cotizaciones actualizadas: {$updated}");

    return 0;
  }

  /**
   * Valida que cada id destino del mapa exista realmente en ap_masters
   * (type=STATUS_ORDER_QUOTE), para no dejar un status_id huérfano.
   */
  private function findUnmappedMasters(): array
  {
    $existingIds = ApMasters::query()
      ->where('type', 'STATUS_ORDER_QUOTE')
      ->pluck('id')
      ->flip();

    $unmapped = [];
    foreach (self::STATUS_MAP as $status => $expectedStatusId) {
      if (!$existingIds->has($expectedStatusId)) {
        $unmapped[] = [$status, $expectedStatusId];
      }
    }

    return $unmapped;
  }

  private function buildSummary(): array
  {
    $summary = [];

    foreach (self::STATUS_MAP as $status => $expectedStatusId) {
      $total = DB::table('ap_order_quotations')->where('status', $status)->count();
      if ($total === 0) {
        continue;
      }

      $alreadyCorrect = DB::table('ap_order_quotations')
        ->where('status', $status)
        ->where('status_id', $expectedStatusId)
        ->count();

      $summary[] = [
        'status' => $status,
        'expected_status_id' => $expectedStatusId,
        'total' => $total,
        'already_correct' => $alreadyCorrect,
        'to_update' => $total - $alreadyCorrect,
      ];
    }

    // Valores de status que no están contemplados en el mapa (para visibilidad, no bloquean).
    $mappedStatuses = array_keys(self::STATUS_MAP);
    $others = DB::table('ap_order_quotations')
      ->whereNotIn('status', $mappedStatuses)
      ->select('status', DB::raw('count(*) as total'))
      ->groupBy('status')
      ->get();

    foreach ($others as $row) {
      $summary[] = [
        'status' => $row->status ?? '(NULL)',
        'expected_status_id' => 'SIN MAPEO',
        'total' => $row->total,
        'already_correct' => 0,
        'to_update' => 0,
      ];
    }

    return $summary;
  }
}