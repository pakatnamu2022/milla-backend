<?php

use App\Models\ap\comercial\ShippingGuides;
use Illuminate\Support\Facades\DB;

// Guías de consignación que ya tienen dyn_series (se enviaron a la BD intermedia)
$guides = ShippingGuides::where('is_consignment', true)
    ->whereNotNull('dyn_series')
    ->where('dyn_series', '!=', '')
    ->get(['id', 'document_number', 'dyn_series', 'dynamics_date', 'issue_date',
           'send_dynamics', 'migration_status', 'cancelled_at', 'status']);

echo "Total guías consignación con dyn_series: " . $guides->count() . "\n\n";

$issues = [];

foreach ($guides as $guide) {
    $transferId = $guide->dyn_series;

    // Buscar en la BD intermedia (cabecera)
    $header = DB::connection('dbtp')
        ->table('neInTbTransferenciaInventario')
        ->where('TransferenciaId', $transferId)
        ->first(['TransferenciaId', 'FechaEmision', 'FechaContable', 'ProcesoEstado', 'ProcesoError']);

    // Buscar detalle (dirección de almacenes)
    $detail = DB::connection('dbtp')
        ->table('neInTbTransferenciaInventarioDet')
        ->where('TransferenciaId', $transferId)
        ->first(['AlmacenId_Ini', 'AlmacenId_Fin']);

    if (!$header) {
        // No existe en la intermedia todavía — sin problema aún
        continue;
    }

    $isCancelled = $guide->status === false || $guide->cancelled_at !== null;

    // Fecha esperada en la intermedia
    $expectedDate = ($guide->dynamics_date ?? $guide->issue_date)?->format('Y-m-d');
    $actualDate   = $header->FechaEmision ? substr($header->FechaEmision, 0, 10) : null;

    $dateOk = $expectedDate === $actualDate;

    // Dirección de almacenes esperada para consignación activa (no anulada): Ini=EXR, Fin=ALM
    $warehouseOk = true;
    $warehouseNote = '';
    if ($detail) {
        if (!$isCancelled) {
            // Recepción: EXR → ALM
            $warehouseOk = str_starts_with($detail->AlmacenId_Ini, 'EXR')
                        && str_starts_with($detail->AlmacenId_Fin, 'ALM');
            if (!$warehouseOk) {
                $warehouseNote = "INVERTIDO ({$detail->AlmacenId_Ini} → {$detail->AlmacenId_Fin}, esperado EXR→ALM)";
            }
        } else {
            // Anulación: ALM → EXR
            $warehouseOk = str_starts_with($detail->AlmacenId_Ini, 'ALM')
                        && str_starts_with($detail->AlmacenId_Fin, 'EXR');
            if (!$warehouseOk) {
                $warehouseNote = "INVERTIDO ({$detail->AlmacenId_Ini} → {$detail->AlmacenId_Fin}, esperado ALM→EXR para anulación)";
            }
        }
    }

    if (!$dateOk || !$warehouseOk) {
        $issues[] = [
            'guide_id'         => $guide->id,
            'document_number'  => $guide->document_number,
            'transfer_id'      => $transferId,
            'proceso_estado'   => $header->ProcesoEstado,
            'migration_status' => $guide->migration_status,
            'dynamics_date'    => $guide->dynamics_date?->format('Y-m-d') ?? 'null',
            'issue_date'       => $guide->issue_date?->format('Y-m-d') ?? 'null',
            'date_in_dbtp'     => $actualDate,
            'date_ok'          => $dateOk ? 'OK' : "MAL (esperado: {$expectedDate}, tiene: {$actualDate})",
            'warehouse_ok'     => $warehouseOk ? 'OK' : $warehouseNote,
            'proceso_error'    => $header->ProcesoError ?? '',
        ];
    }
}

if (empty($issues)) {
    echo "✓ Ninguna guía de consignación en la intermedia tiene problemas de fecha o almacén.\n";
} else {
    echo "GUÍAS CON PROBLEMAS (" . count($issues) . "):\n";
    echo str_repeat('-', 100) . "\n";
    foreach ($issues as $issue) {
        echo "Guía #{$issue['guide_id']} | Doc: {$issue['document_number']} | Transfer: {$issue['transfer_id']}\n";
        echo "  ProcesoEstado: {$issue['proceso_estado']} | migration_status: {$issue['migration_status']}\n";
        echo "  Fecha      : {$issue['date_ok']}\n";
        echo "  Almacenes  : {$issue['warehouse_ok']}\n";
        if ($issue['proceso_error']) {
            echo "  ProcesoError: {$issue['proceso_error']}\n";
        }
        echo "\n";
    }
}

// También: guías consignación con send_dynamics=true pero SIN dyn_series (en vuelo, nunca se enviaron)
$pending = ShippingGuides::where('is_consignment', true)
    ->where('send_dynamics', true)
    ->where(function($q) {
        $q->whereNull('dyn_series')->orWhere('dyn_series', '');
    })
    ->get(['id', 'document_number', 'dynamics_date', 'issue_date', 'migration_status']);

echo "\n--- Guías consignación con send_dynamics=true pero SIN dyn_series (pendientes reales) ---\n";
if ($pending->isEmpty()) {
    echo "Ninguna.\n";
} else {
    foreach ($pending as $g) {
        $dynDate  = $g->dynamics_date?->format('Y-m-d') ?? 'SIN dynamics_date';
        $issDate  = $g->issue_date?->format('Y-m-d') ?? 'null';
        $warn     = !$g->dynamics_date ? ' ⚠ dynamics_date NULO — usará issue_date' : '';
        echo "Guía #{$g->id} | Doc: {$g->document_number} | dynamics_date: {$dynDate} | issue_date: {$issDate} | status: {$g->migration_status}{$warn}\n";
    }
}
