<?php

use App\Http\Services\ap\comercial\ShippingGuidesService;
use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

// Regresión VIN LVZA53P93VAA02345: un traslado creado sobre otro que aún no llegó, y la llegada
// tardía del primero revirtió a INVENTARIO_VN un vehículo ya facturado.

const ARRIVAL_WAREHOUSE_ID = 14;
const SEDE_RECEIVER_ID = 30;
const TYPE_OPERATION_ID = 794;
const CLASS_ID = 7;

beforeEach(function () {
  // Sin listeners de modelo: evita que Auditable escriba en audit_logs
  Event::fake();

  Schema::create('shipping_guides', function ($t) {
    $t->id();
    $t->string('document_number')->nullable();
    $t->string('dyn_series')->nullable();
    $t->boolean('status')->default(true);
    $t->boolean('is_accounted')->default(false);
    $t->boolean('is_annulled')->default(false);
    $t->string('document_type')->nullable();
    $t->unsignedBigInteger('transfer_reason_id')->nullable();
    $t->dateTime('issue_date')->nullable();
    $t->unsignedBigInteger('vehicle_movement_id')->nullable();
    $t->unsignedBigInteger('sede_transmitter_id')->nullable();
    $t->unsignedBigInteger('sede_receiver_id')->nullable();
    $t->softDeletes();
    $t->timestamps();
  });

  Schema::create('ap_vehicle_movement', function ($t) {
    $t->id();
    $t->string('movement_type')->nullable();
    $t->unsignedBigInteger('ap_vehicle_id');
    $t->unsignedBigInteger('ap_vehicle_status_id')->nullable();
    $t->text('observation')->nullable();
    $t->unsignedBigInteger('warehouse_id')->nullable();
    $t->unsignedBigInteger('origin_warehouse_id')->nullable();
    $t->dateTime('movement_date')->nullable();
    $t->dateTime('confirmed_at')->nullable();
    $t->text('origin_address')->nullable();
    $t->text('destination_address')->nullable();
    $t->text('cancellation_reason')->nullable();
    $t->unsignedBigInteger('cancelled_by')->nullable();
    $t->dateTime('cancelled_at')->nullable();
    $t->unsignedBigInteger('previous_status_id')->nullable();
    $t->unsignedBigInteger('new_status_id')->nullable();
    $t->unsignedBigInteger('created_by')->nullable();
    $t->softDeletes();
    $t->timestamps();
  });

  Schema::create('ap_vehicles', function ($t) {
    $t->id();
    $t->string('vin')->nullable();
    $t->unsignedBigInteger('ap_vehicle_status_id')->nullable();
    $t->unsignedBigInteger('warehouse_id')->nullable();
    $t->unsignedBigInteger('type_operation_id')->nullable();
    $t->unsignedBigInteger('ap_models_vn_id')->nullable();
    $t->softDeletes();
    $t->timestamps();
  });

  Schema::create('ap_models_vn', function ($t) {
    $t->id();
    $t->unsignedBigInteger('class_id')->nullable();
    $t->softDeletes();
    $t->timestamps();
  });

  Schema::create('warehouse', function ($t) {
    $t->id();
    $t->unsignedBigInteger('sede_id')->nullable();
    $t->unsignedBigInteger('type_operation_id')->nullable();
    $t->unsignedBigInteger('article_class_id')->nullable();
    $t->boolean('is_received')->default(false);
    $t->boolean('status')->default(true);
    $t->softDeletes();
    $t->timestamps();
  });

  Schema::create('config_sede', function ($t) {
    $t->id();
    $t->string('abreviatura')->nullable();
    $t->softDeletes();
    $t->timestamps();
  });
});

/** Vehículo con un modelo y un almacén de origen. */
function makeVehicle(int $statusId, int $warehouseId = 26): Vehicles
{
  DB::table('ap_models_vn')->insert(['id' => 1, 'class_id' => CLASS_ID]);

  $id = DB::table('ap_vehicles')->insertGetId([
    'vin'                  => 'LVZA53P93VAA02345',
    'ap_vehicle_status_id' => $statusId,
    'warehouse_id'         => $warehouseId,
    'type_operation_id'    => TYPE_OPERATION_ID,
    'ap_models_vn_id'      => 1,
  ]);

  return Vehicles::findOrFail($id);
}

/** Guía asociada al vehículo mediante un movimiento de tipo TRAVESIA. */
function makeGuide(Vehicles $vehicle, array $attrs = []): ShippingGuides
{
  $movementId = DB::table('ap_vehicle_movement')->insertGetId([
    'movement_type'        => VehicleMovement::IN_TRANSIT,
    'ap_vehicle_id'        => $vehicle->id,
    'ap_vehicle_status_id' => $vehicle->ap_vehicle_status_id,
  ]);

  $id = DB::table('shipping_guides')->insertGetId(array_merge([
    'document_number'     => 'T042-00000147',
    'document_type'       => ShippingGuides::DOCUMENT_TYPE_GR,
    'transfer_reason_id'  => SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE,
    'status'              => true,
    'is_accounted'        => true,
    'is_annulled'         => false,
    'issue_date'          => now()->toDateString(),
    'vehicle_movement_id' => $movementId,
  ], $attrs));

  return ShippingGuides::findOrFail($id);
}

/** Invoca el candado privado del servicio sin construir sus dependencias HTTP. */
function runPendingGuideGuard(int $vehicleId): void
{
  $service = (new ReflectionClass(ShippingGuidesService::class))->newInstanceWithoutConstructor();
  $method = new ReflectionMethod(ShippingGuidesService::class, 'ensureNoPendingGuide');
  $method->invoke($service, $vehicleId);
}

// ──────────────────────────────────────────────────────────────────────────────
// Candado: no crear una guía mientras hay otra pendiente o en curso
// ──────────────────────────────────────────────────────────────────────────────

test('permite crear una guía cuando el vehículo no tiene guías', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);

  runPendingGuideGuard($vehicle->id);

  expect(true)->toBeTrue();
});

test('bloquea una guía nueva si la anterior no está contabilizada', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle, ['is_accounted' => false]);

  runPendingGuideGuard($vehicle->id);
})->throws(Exception::class, 'pendiente de contabilizar');

/** Movimiento de llegada que registra la contabilización final del traslado. */
function markTransferArrived(Vehicles $vehicle, string $documentNumber = 'T042-00000147', string $label = 'Guía'): void
{
  DB::table('ap_vehicle_movement')->insert([
    'movement_type'        => VehicleMovement::INTERNAL_TRANSFER,
    'ap_vehicle_id'        => $vehicle->id,
    'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
    'observation'          => "Por contabilización en Dynamics | {$label}: {$documentNumber} (Dynamics: CT-{$documentNumber})",
  ]);
}

test('bloquea una guía nueva si el traslado anterior está contabilizado pero aún no llegó', function () {
  $vehicle = makeVehicle(ApVehicleStatus::EN_CURSO);
  makeGuide($vehicle, ['issue_date' => now()->addDays(5)->toDateString()]);

  runPendingGuideGuard($vehicle->id);
})->throws(Exception::class, 'traslado T042-00000147 en camino');

test('bloquea aunque la fecha del traslado ya pasó, mientras no haya llegado', function () {
  // Caso T042-90: fecha 15/08, llegada registrada 11 días después
  $vehicle = makeVehicle(ApVehicleStatus::EN_CURSO);
  makeGuide($vehicle, ['issue_date' => now()->subDays(4)->toDateString()]);

  runPendingGuideGuard($vehicle->id);
})->throws(Exception::class, 'aún no llega');

test('bloquea aunque el vehículo ya esté facturado: se puede facturar, no volver a trasladar', function () {
  $vehicle = makeVehicle(ApVehicleStatus::FACTURADO_FINAL);
  makeGuide($vehicle);

  runPendingGuideGuard($vehicle->id);
})->throws(Exception::class, 'en camino');

test('bloquea también una guía interna contabilizada que no llegó', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle, [
    'document_type'      => ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA,
    'transfer_reason_id' => null,
    'document_number'    => 'TI01-00000010',
  ]);

  runPendingGuideGuard($vehicle->id);
})->throws(Exception::class, 'TI01-00000010');

test('permite una guía nueva cuando el traslado anterior ya llegó', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle);
  markTransferArrived($vehicle);

  runPendingGuideGuard($vehicle->id);

  expect(true)->toBeTrue();
});

test('la llegada de una guía interna se reconoce por su número', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle, [
    'document_type'      => ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA,
    'transfer_reason_id' => null,
    'document_number'    => 'TI01-00000010',
  ]);
  markTransferArrived($vehicle, 'TI01-00000010', 'Guía interna');

  runPendingGuideGuard($vehicle->id);

  expect(true)->toBeTrue();
});

test('la llegada de otra guía no libera al traslado que sigue en camino', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle, ['document_number' => 'T042-00000147']);
  markTransferArrived($vehicle, 'T004-00000165');

  runPendingGuideGuard($vehicle->id);
})->throws(Exception::class, 'T042-00000147');

test('permite una guía nueva si el traslado anterior fue anulado', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle, ['is_annulled' => true]);

  runPendingGuideGuard($vehicle->id);

  expect(true)->toBeTrue();
});

test('una guía de venta contabilizada no cuenta como traslado en camino', function () {
  $vehicle = makeVehicle(ApVehicleStatus::INVENTARIO_VN);
  makeGuide($vehicle, ['transfer_reason_id' => SunatConcepts::TRANSFER_REASON_VENTA]);

  runPendingGuideGuard($vehicle->id);

  expect(true)->toBeTrue();
});

test('el traslado en camino de otro vehículo no bloquea', function () {
  $other = makeVehicle(ApVehicleStatus::EN_CURSO);
  makeGuide($other);

  $otherVehicleId = DB::table('ap_vehicles')->insertGetId([
    'vin'                  => 'OTRO',
    'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
  ]);

  runPendingGuideGuard($otherVehicleId);

  expect(true)->toBeTrue();
});

// ──────────────────────────────────────────────────────────────────────────────
// Llegada del traslado: no revertir el estado de venta
// ──────────────────────────────────────────────────────────────────────────────

/** Almacén ALM (is_received = true) de la sede receptora donde llega el vehículo. */
function makeArrivalWarehouse(): void
{
  DB::table('config_sede')->insert(['id' => SEDE_RECEIVER_ID, 'abreviatura' => 'GRA']);
  DB::table('warehouse')->insert([
    'id'                => ARRIVAL_WAREHOUSE_ID,
    'sede_id'           => SEDE_RECEIVER_ID,
    'type_operation_id' => TYPE_OPERATION_ID,
    'article_class_id'  => CLASS_ID,
    'is_received'       => true,
    'status'            => true,
  ]);
}

function arriveTransfer(Vehicles $vehicle): VehicleMovement
{
  $guide = makeGuide($vehicle, ['sede_receiver_id' => SEDE_RECEIVER_ID]);

  return (new VehicleMovementService())
    ->storeInterCompanyTransferCompletedVehicleMovement($vehicle, $guide->load('sedeReceiver'));
}

test('la llegada del traslado pasa un vehículo EN CURSO a INVENTARIO_VN', function () {
  makeArrivalWarehouse();
  $vehicle = makeVehicle(ApVehicleStatus::EN_CURSO);

  $movement = arriveTransfer($vehicle);

  expect($movement->new_status_id)->toBe(ApVehicleStatus::INVENTARIO_VN)
    ->and($vehicle->fresh()->ap_vehicle_status_id)->toBe(ApVehicleStatus::INVENTARIO_VN)
    ->and($vehicle->fresh()->warehouse_id)->toBe(ARRIVAL_WAREHOUSE_ID);
});

test('la llegada del traslado NO revierte un vehículo facturado a INVENTARIO_VN', function (int $saleStatus) {
  makeArrivalWarehouse();
  $vehicle = makeVehicle($saleStatus);

  $movement = arriveTransfer($vehicle);

  expect($movement->ap_vehicle_status_id)->toBe($saleStatus)
    ->and($movement->previous_status_id)->toBe($saleStatus)
    ->and($movement->new_status_id)->toBe($saleStatus)
    ->and($vehicle->fresh()->ap_vehicle_status_id)->toBe($saleStatus);
})->with([
  'FACTURADO'            => ApVehicleStatus::FACTURADO,
  'FACTURADO FINAL'      => ApVehicleStatus::FACTURADO_FINAL,
  'VENDIDO NO ENTREGADO' => ApVehicleStatus::VENDIDO_NO_ENTREGADO,
  'VENDIDO ENTREGADO'    => ApVehicleStatus::VENDIDO_ENTREGADO,
  'ACTIVO'               => ApVehicleStatus::ACTIVO,
]);

test('la llegada del traslado sí actualiza el almacén de un vehículo ya facturado', function () {
  makeArrivalWarehouse();
  $vehicle = makeVehicle(ApVehicleStatus::FACTURADO_FINAL, warehouseId: 26);

  arriveTransfer($vehicle);

  expect($vehicle->fresh()->warehouse_id)->toBe(ARRIVAL_WAREHOUSE_ID);
});
