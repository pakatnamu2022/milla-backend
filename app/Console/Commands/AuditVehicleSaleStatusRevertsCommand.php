<?php

namespace App\Console\Commands;

use App\Http\Services\ap\comercial\VehicleSaleStatusAuditService;
use Illuminate\Console\Command;

/**
 * Resumen por consola del reporte de vehículos vendidos que volvieron a inventario.
 * El detalle completo (qué pasó, qué está mal, cómo corregirlo) se ve en la vista del front:
 * GET /ap/commercial/reports/vehicle-sale-status-audit.
 *
 * SOLO REPORTA: no modifica datos.
 */
class AuditVehicleSaleStatusRevertsCommand extends Command
{
  protected $signature = 'vehicles:audit-sale-status-reverts
    {--vin=* : Limitar a uno o más VIN}
    {--csv= : Exportar el reporte a un archivo CSV}';

  protected $description = 'Resumen y dry run de vehículos vendidos revertidos a inventario (no modifica datos)';

  public function handle(VehicleSaleStatusAuditService $service): int
  {
    $report = $service->generate((array) $this->option('vin'));
    $rows = $report['rows'];

    if (!$rows) {
      $this->info('No se encontraron vehículos vendidos revertidos.');
      return self::SUCCESS;
    }

    $this->table(
      ['VIN', 'Causa', 'Estado actual → propuesto', 'Almacén', 'Acción'],
      array_map(fn($r) => [
        $r['vin'], $r['cause'], "{$r['current']} → {$r['target']}",
        $r['wh_changes'] ? "{$r['wh_current']} → {$r['wh_target']}" : $r['wh_current'], $r['action'],
      ], $rows)
    );

    $s = $report['summary'];
    $this->newLine();
    $this->info('DRY RUN: no se modificó ningún dato.');
    $this->line("Vehículos: {$s['total']} | Corregibles: {$s['fix']} | Revisar: {$s['review']} | Sin acción: {$s['none']}");
    foreach ($report['causes'] as $cause) {
      $this->line("  {$cause['code']}: {$cause['count']}");
    }

    if ($path = $this->option('csv')) {
      $fh = fopen($path, 'w');
      fputcsv($fh, ['VIN', 'Causa', 'Comprobantes vigentes', 'Estado actual', 'Estado propuesto', 'Almacén actual', 'Almacén propuesto', 'Acción', 'Qué pasó', 'Qué está mal', 'Corrección', 'Prevención']);
      foreach ($rows as $r) {
        fputcsv($fh, [
          $r['vin'], $r['cause'], implode(' | ', array_column($r['invoices'], 'n')), $r['current'], $r['target'],
          $r['wh_current'], $r['wh_target'], $r['action'],
          implode(' ', $r['happened']), implode(' ', $r['wrong']), $r['fix'], implode(' ', $r['prevention']),
        ]);
      }
      fclose($fh);
      $this->line("CSV: {$path}");
    }

    return self::SUCCESS;
  }
}
