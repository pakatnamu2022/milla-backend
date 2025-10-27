<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$poId = 1; // ID de la orden de compra

echo "=== RESETEANDO MIGRACIÓN DE ORDEN DE COMPRA ID: {$poId} ===\n\n";

// Resetear el estado de la orden de compra
DB::table('ap_purchase_order')
    ->where('id', $poId)
    ->update(['migration_status' => 'pending']);

echo "✓ Estado de orden de compra actualizado a 'pending'\n";

// Eliminar todos los logs de migración para esta orden
$deletedLogs = DB::table('ap_vehicle_purchase_order_migration_log')
    ->where('vehicle_purchase_order_id', $poId)
    ->delete();

echo "✓ {$deletedLogs} logs de migración eliminados\n\n";

echo "=== DESPACHANDO JOB DE MIGRACIÓN ===\n";

// Despachar el job
App\Jobs\VerifyAndMigratePurchaseOrderJob::dispatch($poId);

echo "✓ Job VerifyAndMigratePurchaseOrderJob despachado a la cola 'sync'\n\n";

echo "Ejecuta: php artisan queue:work --queue=sync,default --tries=3 --verbose\n";
