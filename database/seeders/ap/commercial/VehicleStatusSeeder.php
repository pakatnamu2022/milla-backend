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
        'code' => 'PVN',
        'description' => 'Pedido VN',
        'color' => '#3B82F6'
      ],
      [
        'code' => 'VTR',
        'description' => 'Vehículo en Tránsito',
        'color' => '#F59E0B'
      ],
      [
        'code' => 'VTD',
        'description' => 'Vehículo en Tránsito Devuelto',
        'color' => '#F97316'
      ],
      [
        'code' => 'VNE',
        'description' => 'Vehículo Vendido No Entregado',
        'color' => '#8B5CF6'
      ],
      [
        'code' => 'IVN',
        'description' => 'Inventario VN',
        'color' => '#10B981'
      ],
      [
        'code' => 'VEN',
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
