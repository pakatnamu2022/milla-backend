<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class=Database\Seeders\ap\commercial\VehiclePurchaseOrderWithMovementsSeeder
 */
class PurchaseOrderWithMovementsSeeder extends Seeder
{
  public function run(): void
  {
    // Crear la orden de compra de vehículo
    $purchaseOrder = VehiclePurchaseOrder::create([
      'id' => 1,
      'vin' => '1HGBH41AX1N109186',
      'year' => 2025,
      'engine_number' => 'ENG32345XYZ',
      'ap_models_vn_id' => 5,
      'vehicle_color_id' => 517,
      'supplier_order_type_id' => 641,
      'engine_type_id' => 628,
      'ap_vehicle_status_id' => 3,
      'sede_id' => 13,
      'invoice_series' => 'F001',
      'invoice_number' => '00001234',
      'emission_date' => '2025-10-11',
      'unit_price' => 48296.61,
      'discount' => 3380.76,
      'subtotal' => 44915.85,
      'igv' => 8084.85,
      'total' => 53000.70,
      'supplier_id' => 4,
      'currency_id' => 1,
      'exchange_rate_id' => 1,
      'number' => '1400000001',
      'number_guide' => '1400000001',
      'warehouse_id' => 95,
      'warehouse_physical_id' => null,
      'migration_status' => 'completed',
      'credit_note_dynamics' => 'NC. RE00000004',
      'receipt_dynamics' => 'RE00000003',
      'invoice_dynamics' => 'F008-00109911-FAC',
      'migrated_at' => '2025-10-13 16:40:02',
      'created_at' => '2025-10-13 16:30:38',
      'updated_at' => '2025-10-14 11:07:01',
      'deleted_at' => null,
    ]);

    // Primer movimiento: Creación de orden de compra
    VehicleMovement::create([
      'id' => 1,
      'ap_vehicle_status_id' => 1,
      'ap_vehicle_purchase_order_id' => $purchaseOrder->id,
      'observation' => 'Creación de orden de compra de vehículo',
      'movement_date' => '2025-10-13 16:30:38',
      'created_at' => '2025-10-13 16:30:38',
      'updated_at' => '2025-10-13 16:30:38',
      'deleted_at' => null,
    ]);

    // Segundo movimiento: Vehículo en tránsito
    VehicleMovement::create([
      'id' => 10,
      'ap_vehicle_status_id' => 2,
      'ap_vehicle_purchase_order_id' => $purchaseOrder->id,
      'observation' => 'Vehículo en tránsito - Factura Dynamics: F008-00109911-FAC',
      'movement_date' => '2025-10-14 11:06:01',
      'created_at' => '2025-10-14 11:06:01',
      'updated_at' => '2025-10-14 11:06:01',
      'deleted_at' => null,
    ]);

    // Tercer movimiento: Vehículo devuelto por NC
    VehicleMovement::create([
      'id' => 11,
      'ap_vehicle_status_id' => 3,
      'ap_vehicle_purchase_order_id' => $purchaseOrder->id,
      'observation' => 'Vehículo devuelto por NC - NC: NC. RE00000004',
      'movement_date' => '2025-10-14 11:07:01',
      'created_at' => '2025-10-14 11:07:01',
      'updated_at' => '2025-10-14 11:07:01',
      'deleted_at' => null,
    ]);
  }
}

