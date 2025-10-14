<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * php artisan db:seed --class=Database\Seeders\ap\commercial\VehicleStatusSeeder
 */
class VehicleStatusSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      [
        'code' => 'PEDIDO_VN',
        'description' => 'Pedido VN',
        'color' => '#3B82F6'
      ],
      [
        'code' => 'VEHICULO_TRANSITO',
        'description' => 'Vehículo en Tránsito (Asocia la factura en Dynamics)',
        'color' => '#F59E0B'
      ],
      [
        'code' => 'VEHICULO_TRANSITO_DEVUELTO',
        'description' => 'Vehículo en Tránsito Devuelto (Devolución de PDI)',
        'color' => '#F97316'
      ],
      [
        'code' => 'VENDIDO_NO_ENTREGADO',
        'description' => 'Vehículo Vendido No Entregado',
        'color' => '#8B5CF6'
      ],
      [
        'code' => 'INVENTARIO_VN',
        'description' => 'Inventario VN (Recepción con PDI)',
        'color' => '#10B981'
      ],
      [
        'code' => 'VENDIDO_ENTREGADO',
        'description' => 'Vehículo Vendido Entregado',
        'color' => '#059669'
      ]
    ];
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    ApVehicleStatus::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    foreach ($data as $item) {
      ApVehicleStatus::firstOrCreate([
        'code' => $item['code'],
        'description' => $item['description'],
        'color' => $item['color'],
      ]);
    }
  }
}
