<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$log = DB::table('ap_vehicle_purchase_order_migration_log')
    ->where('vehicle_purchase_order_id', 1)
    ->where('step', 'purchase_order')
    ->first();

if ($log && $log->error_message) {
    echo "=== ERROR EN PASO: purchase_order ===\n";
    echo $log->error_message . "\n\n";

    if ($log->proceso_estado !== null) {
        echo "Proceso Estado: {$log->proceso_estado}\n";
    }
} else {
    echo "No hay mensaje de error registrado\n";
}

// Ver también el log de purchase_order_detail
$detailLog = DB::table('ap_vehicle_purchase_order_migration_log')
    ->where('vehicle_purchase_order_id', 1)
    ->where('step', 'purchase_order_detail')
    ->first();

if ($detailLog && $detailLog->error_message) {
    echo "\n=== ERROR EN PASO: purchase_order_detail ===\n";
    echo $detailLog->error_message . "\n";
}
