<?php

namespace App\Console\Commands;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Console\Command;

class AccountingEntryVinReportCommand extends Command
{
  protected $signature = 'accounting-entry:vin-report
    {--csv : Exportar como CSV en lugar de tabla}
    {--pending : Mostrar solo VINs sin asiento enviado}
    {--sent : Mostrar solo VINs con asiento enviado}';

  protected $description = 'Reporte de VINs entregados con estado de asiento contable (enviado / no enviado)';

  public function handle(): int
  {
    $guides = ShippingGuides::with([
      'vehicleMovement.vehicle',
      'migrationLogs' => fn($q) => $q->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
      ]),
    ])
      ->where('transfer_reason_id', SunatConcepts::TRANSFER_REASON_VENTA)
      ->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('status_dynamic', 1)
      ->whereHas('sedeTransmitter', fn($q) => $q->where('empresa_id', Company::AP_DYNAMICS))
      ->orderBy('issue_date')
      ->get();

    $rows = $guides->map(function (ShippingGuides $sg) {
      $vin = $sg->vehicleMovement?->vehicle?->vin ?? 'N/A';
      $headerLog = $sg->migrationLogs->first();

      $accountingStatus = 'NO ENVIADO';
      $procesoEstado    = '-';

      if ($headerLog) {
        $accountingStatus = strtoupper($headerLog->status);
        $procesoEstado    = $headerLog->proceso_estado === 1 ? 'Procesado GP' : ($headerLog->proceso_estado === 0 ? 'Pendiente GP' : '-');
      }

      return [
        'guide_id'         => $sg->id,
        'guide_number'     => $sg->document_number,
        'issue_date'       => $sg->issue_date?->format('Y-m-d') ?? '-',
        'vin'              => $vin,
        'accounting_status'=> $accountingStatus,
        'proceso_estado'   => $procesoEstado,
        'external_id'      => $headerLog?->external_id ?? '-',
      ];
    });

    if ($this->option('pending')) {
      $rows = $rows->filter(fn($r) => $r['accounting_status'] === 'NO ENVIADO');
    } elseif ($this->option('sent')) {
      $rows = $rows->filter(fn($r) => $r['accounting_status'] !== 'NO ENVIADO');
    }

    $sent    = $rows->where('accounting_status', '!=', 'NO ENVIADO')->count();
    $pending = $rows->where('accounting_status', 'NO ENVIADO')->count();
    $gpOk    = $rows->where('proceso_estado', 'Procesado GP')->count();

    $this->info("Total: {$rows->count()} | Enviados: {$sent} | Sin enviar: {$pending} | Procesados GP: {$gpOk}");
    $this->newLine();

    if ($this->option('csv')) {
      $this->line(implode(',', ['guide_id', 'guide_number', 'issue_date', 'vin', 'accounting_status', 'proceso_estado', 'external_id']));
      foreach ($rows as $row) {
        $this->line(implode(',', array_values($row)));
      }
      return 0;
    }

    $this->table(
      ['ID Guía', 'Guía', 'Fecha', 'VIN', 'Estado Asiento', 'Estado GP', 'Referencia'],
      $rows->values()->map(fn($r) => array_values($r))->toArray()
    );

    return 0;
  }
}
