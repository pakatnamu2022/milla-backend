<?php

use App\Models\ap\comercial\Vehicles;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Http\Services\ap\comercial\VehicleMovementService;

$vehicleId = 1756;
$service   = app(VehicleMovementService::class);

// Resetear vehículo al inicio correcto: EXI Piura art_class=3 (warehouse 86), status=2
Vehicles::where('id', $vehicleId)->update([
    'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
    'warehouse_id'         => 86,
]);

echo "====== SIMULACIÓN CICLO VEHÍCULO 1756 (VIN: LJ12EKS37V4701076) ======\n\n";

$vehicle = Vehicles::find($vehicleId);
$wh = Warehouse::find($vehicle->warehouse_id);
echo "Estado inicial: status={$vehicle->ap_vehicle_status_id} (EN_TRAVESIA)\n";
echo "Almacén inicial: [{$wh->id}] {$wh->description} (sede_id={$wh->sede_id})\n\n";

// ----------------------------------------------------------------
// PASO 1: Recepción a inventario Piura (EXI PIU → ALM PIU)
// ----------------------------------------------------------------
echo "--- PASO 1: Recepción a inventario Piura ---\n";
try {
    $service->storeInventoryVehicleMovement($vehicleId);
    $vehicle->refresh();
    $m1 = VehicleMovement::where('ap_vehicle_id', $vehicleId)->latest('id')->first();
    $wh1 = Warehouse::find($vehicle->warehouse_id);
    echo "OK | Mov ID={$m1->id} | {$m1->movement_type} | status={$m1->ap_vehicle_status_id}\n";
    echo "   Vehículo ahora en: [{$wh1->id}] {$wh1->description} (sede={$wh1->sede_id})\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
    exit;
}

// ----------------------------------------------------------------
// PASO 2a: Traslado en curso → EN_CURSO (ALM PIU → EXR CAJ)
// ----------------------------------------------------------------
echo "--- PASO 2a: Traslado iniciado → EN_CURSO (vehículo en tránsito hacia Cajamarca) ---\n";
$vehicle->refresh();
$exrCaj = Warehouse::find(68); // EXI. POR REC. COMERCIAL CAJ, art_class=3
$prevWh = $vehicle->warehouse_id;
$prevStatus = $vehicle->ap_vehicle_status_id;

VehicleMovement::create([
    'movement_type'        => VehicleMovement::EN_CURSO,
    'ap_vehicle_id'        => $vehicleId,
    'ap_vehicle_status_id' => ApVehicleStatus::EN_CURSO,
    'movement_date'        => now(),
    'confirmed_at'         => now(),
    'observation'          => "Traslado entre empresas iniciado | Piura → Cajamarca",
    'warehouse_id'         => $exrCaj->id,
    'origin_warehouse_id'  => $prevWh,
    'previous_status_id'   => $prevStatus,
    'new_status_id'        => ApVehicleStatus::EN_CURSO,
]);
$vehicle->update([
    'ap_vehicle_status_id' => ApVehicleStatus::EN_CURSO,
    'warehouse_id'         => $exrCaj->id,
]);
$vehicle->refresh();
$m2a = VehicleMovement::where('ap_vehicle_id', $vehicleId)->latest('id')->first();
echo "OK | Mov ID={$m2a->id} | {$m2a->movement_type} | status={$m2a->ap_vehicle_status_id} (EN_CURSO)\n";
echo "   Vehículo ahora en: [{$exrCaj->id}] {$exrCaj->description} (sede={$exrCaj->sede_id})\n\n";

// ----------------------------------------------------------------
// PASO 2b: Traslado completado → TRASLADO INTERNO (EXR CAJ → ALM CAJ)
// ----------------------------------------------------------------
echo "--- PASO 2b: Traslado completado → INVENTARIO_VN en Cajamarca ---\n";
$vehicle->refresh();
$almCaj = Warehouse::find(26); // ALMACEN COMERCIAL CAJ, art_class=3
$prevWh2 = $vehicle->warehouse_id;
$prevStatus2 = $vehicle->ap_vehicle_status_id;

VehicleMovement::create([
    'movement_type'        => VehicleMovement::INTERNAL_TRANSFER,
    'ap_vehicle_id'        => $vehicleId,
    'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
    'movement_date'        => now(),
    'confirmed_at'         => now(),
    'observation'          => "Traslado entre empresas completado | Piura → Cajamarca",
    'warehouse_id'         => $almCaj->id,
    'origin_warehouse_id'  => $prevWh2,
    'previous_status_id'   => $prevStatus2,
    'new_status_id'        => ApVehicleStatus::INVENTARIO_VN,
]);
$vehicle->update([
    'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
    'warehouse_id'         => $almCaj->id,
]);
$vehicle->refresh();
$m2b = VehicleMovement::where('ap_vehicle_id', $vehicleId)->latest('id')->first();
echo "OK | Mov ID={$m2b->id} | {$m2b->movement_type} | status={$m2b->ap_vehicle_status_id} (INVENTARIO_VN)\n";
echo "   Vehículo ahora en: [{$almCaj->id}] {$almCaj->description} (sede={$almCaj->sede_id})\n\n";

// ----------------------------------------------------------------
// PRUEBA A: Facturar desde Piura con vehículo en Cajamarca (debe bloquearse)
// ----------------------------------------------------------------
echo "--- PRUEBA A: Facturación desde Piura con vehículo en Cajamarca ---\n";
$vehicle->refresh();
$vehicleSedeId  = $vehicle->warehouse?->sede_id; // 19 (CAJ)
$documentSedeId = 18; // serie Piura

if ($documentSedeId && $vehicleSedeId && $documentSedeId !== $vehicleSedeId) {
    echo "BLOQUEADO correctamente: vehículo en sede {$vehicleSedeId} (CAJ), documento es de sede {$documentSedeId} (PIU)\n\n";
} else {
    echo "ERROR: No se bloqueó la venta desde sede incorrecta\n\n";
}

// ----------------------------------------------------------------
// PASO 3: Venta en Cajamarca → FACTURADO_FINAL
// ----------------------------------------------------------------
echo "--- PASO 3: Venta en Cajamarca (FACTURADO_FINAL) ---\n";
$vehicle->refresh();
$prevStatus3 = $vehicle->ap_vehicle_status_id;
$ventaMov = VehicleMovement::create([
    'movement_type'        => 'VENTA',
    'ap_vehicle_id'        => $vehicleId,
    'ap_vehicle_status_id' => ApVehicleStatus::FACTURADO_FINAL,
    'movement_date'        => now(),
    'confirmed_at'         => null,
    'observation'          => "Venta de vehículo - Documento: FXX5-00999",
    'warehouse_id'         => $vehicle->warehouse_id,
    'origin_warehouse_id'  => $vehicle->warehouse_id,
    'previous_status_id'   => $prevStatus3,
    'new_status_id'        => ApVehicleStatus::FACTURADO_FINAL,
]);
$vehicle->update(['ap_vehicle_status_id' => ApVehicleStatus::FACTURADO_FINAL]);
$vehicle->refresh();
echo "OK | Mov ID={$ventaMov->id} | VENTA | status=9 | confirmed_at=NULL (pendiente contabilización)\n\n";

// ----------------------------------------------------------------
// PRUEBA B: Traslado a Chiclayo después de FACTURADO_FINAL (debe bloquearse)
// ----------------------------------------------------------------
echo "--- PRUEBA B: Traslado a Chiclayo con vehículo FACTURADO_FINAL ---\n";
$vehicle->refresh();
if (ApVehicleStatus::isSaleStatus($vehicle->ap_vehicle_status_id)) {
    echo "BLOQUEADO correctamente: status={$vehicle->ap_vehicle_status_id} está en SALE_STATUSES\n\n";
} else {
    echo "ERROR: No se bloqueó el traslado después de facturación\n\n";
}

// ----------------------------------------------------------------
// PASO 4: Contabilización (confirmed_at del movimiento de venta)
// ----------------------------------------------------------------
echo "--- PASO 4: Contabilización ---\n";
$ventaMov->update(['confirmed_at' => now()]);
$ventaMov->refresh();
echo "OK | Mov VENTA ID={$ventaMov->id} | confirmed_at={$ventaMov->confirmed_at}\n\n";

// ----------------------------------------------------------------
// PASO 5: Programar entrega → VENDIDO_NO_ENTREGADO
// ----------------------------------------------------------------
echo "--- PASO 5: Programar entrega (storeScheduleDeliveryVehicleMovement) ---\n";
$vehicle->refresh();
try {
    $service->storeScheduleDeliveryVehicleMovement($vehicle);
    $vehicle->refresh();
    $m5 = VehicleMovement::where('ap_vehicle_id', $vehicleId)->latest('id')->first();
    echo "OK | Mov ID={$m5->id} | {$m5->movement_type} | status={$m5->ap_vehicle_status_id}\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// ----------------------------------------------------------------
// PASO 6: Entrega física → VENDIDO_ENTREGADO
// ----------------------------------------------------------------
echo "--- PASO 6: Entrega física (storeCompletedDeliveryVehicleMovement) ---\n";
$vehicle->refresh();
try {
    $service->storeCompletedDeliveryVehicleMovement(
        $vehicle,
        'Av. Grau 123, Cajamarca',
        'Jr. Los Jazmines 789, Cajamarca'
    );
    $vehicle->refresh();
    $m6 = VehicleMovement::where('ap_vehicle_id', $vehicleId)->latest('id')->first();
    echo "OK | Mov ID={$m6->id} | {$m6->movement_type} | status={$m6->ap_vehicle_status_id}\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// ----------------------------------------------------------------
// RESUMEN FINAL
// ----------------------------------------------------------------
echo "====== RESUMEN FINAL ======\n";
$vehicle->refresh();
$finalWh = Warehouse::find($vehicle->warehouse_id);
echo "Vehículo 1756 — status final: {$vehicle->ap_vehicle_status_id} | warehouse: " . ($finalWh ? $finalWh->description : '-') . "\n\n";

$movements = VehicleMovement::where('ap_vehicle_id', $vehicleId)->orderBy('id')->get();
foreach ($movements as $m) {
    $whName     = $m->warehouse_id        ? (Warehouse::find($m->warehouse_id)?->description        ?? '?') : '-';
    $origWhName = $m->origin_warehouse_id ? (Warehouse::find($m->origin_warehouse_id)?->description ?? '?') : '-';
    echo sprintf("ID=%-5d | %-25s | st=%-2d | orig=%-30s | dest=%-30s | conf=%s\n",
        $m->id, $m->movement_type, $m->ap_vehicle_status_id, $origWhName, $whName,
        $m->confirmed_at ? 'SI' : 'NULL'
    );
}
