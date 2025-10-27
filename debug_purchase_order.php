<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ÚLTIMA ORDEN DE COMPRA ===\n";
$po = DB::table('ap_purchase_order')->orderBy('id', 'desc')->first();

if (!$po) {
    echo "No hay órdenes de compra\n";
    exit;
}

echo "ID: {$po->id}\n";
echo "Number: {$po->number}\n";
echo "Migration Status: {$po->migration_status}\n";
echo "Vehicle Movement ID: " . ($po->vehicle_movement_id ?? 'NULL') . "\n";
echo "Supplier ID: {$po->supplier_id}\n\n";

// Verificar VehicleMovement
if ($po->vehicle_movement_id) {
    echo "=== VEHICLE MOVEMENT ===\n";
    $vm = DB::table('ap_vehicle_movement')->where('id', $po->vehicle_movement_id)->first();
    if ($vm) {
        echo "Movement Type: {$vm->movement_type}\n";
        echo "Vehicle ID: {$vm->ap_vehicle_id}\n";
        echo "Status ID: {$vm->ap_vehicle_status_id}\n\n";

        // Verificar Vehicle
        echo "=== VEHICLE ===\n";
        $vehicle = DB::table('ap_vehicles')->where('id', $vm->ap_vehicle_id)->first();
        if ($vehicle) {
            echo "VIN: {$vehicle->vin}\n";
            echo "Model ID: {$vehicle->ap_models_vn_id}\n\n";
        } else {
            echo "ERROR: Vehicle no encontrado\n\n";
        }
    } else {
        echo "ERROR: VehicleMovement no encontrado\n\n";
    }
}

// Verificar migration logs
echo "=== MIGRATION LOGS ===\n";
$logs = DB::table('ap_vehicle_purchase_order_migration_log')
    ->where('vehicle_purchase_order_id', $po->id)
    ->get();

if ($logs->count() > 0) {
    foreach ($logs as $log) {
        echo "Step: {$log->step} | Status: {$log->status} | Attempts: {$log->attempts}\n";
    }
} else {
    echo "No hay logs de migración para esta OC\n";
}

echo "\n=== ITEMS ===\n";
$items = DB::table('ap_purchase_order_item')
    ->where('purchase_order_id', $po->id)
    ->get();

echo "Total items: " . $items->count() . "\n";
foreach ($items as $item) {
    echo "- {$item->description} | Is Vehicle: " . ($item->is_vehicle ? 'YES' : 'NO') . "\n";
}
