<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$po = DB::table('ap_purchase_order')->where('id', 1)->first();

echo "=== VERIFICANDO RECEPCIONES EN TABLA INTERMEDIA ===\n\n";

// Verificar Recepción
$recepcion = DB::connection('dbtp')
    ->table('neInTbRecepcion')
    ->where('RecepcionId', $po->number_guide)
    ->first();

if ($recepcion) {
    echo "✓ Recepción en tabla intermedia: {$recepcion->RecepcionId} | ProcesoEstado: {$recepcion->ProcesoEstado} | ProcesoError: " . ($recepcion->ProcesoError ?? 'ninguno') . "\n";
} else {
    echo "✗ Recepción NO encontrada en tabla intermedia\n";
}

// Verificar Recepción Detalle
$recepcionDet = DB::connection('dbtp')
    ->table('neInTbRecepcionDt')
    ->where('RecepcionId', $po->number_guide)
    ->first();

if ($recepcionDet) {
    echo "✓ Recepción Detalle en tabla intermedia | Línea: {$recepcionDet->Linea}\n";
} else {
    echo "✗ Recepción Detalle NO encontrada\n";
}

// Verificar Recepción Serial
$vehicle = DB::table('ap_vehicles')->where('id', 1)->first();

$recepcionSerial = DB::connection('dbtp')
    ->table('neInTbRecepcionDtS')
    ->where('Serie', $vehicle->vin)
    ->first();

if ($recepcionSerial) {
    echo "✓ Recepción Serial en tabla intermedia | VIN: {$recepcionSerial->Serie} | ProcesoEstado: " . ($recepcionSerial->ProcesoEstado ?? 'N/A') . "\n";
} else {
    echo "✗ Recepción Serial NO encontrada\n";
}

echo "\n";
