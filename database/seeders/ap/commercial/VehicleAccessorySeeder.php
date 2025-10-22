<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class VehicleAccessorySeeder extends Seeder
{
  public function run(): void
  {
    $accessories = [
      ['code' => 'KIT-SEG-SZK-NGV', 'description' => 'KIT DE SEGURO SZK NEW GRAND VITARA', 'type' => 'VEHICLE_ACCESSORY'],
      ['code' => 'KIT-SEG-SZK-SWF', 'description' => 'KIT DE SEGURO SZK SWIFT', 'type' => 'VEHICLE_ACCESSORY'],
      ['code' => 'KIT-SEG-SZK-VIT', 'description' => 'KIT DE SEGURO SZK VITARA', 'type' => 'VEHICLE_ACCESSORY'],
      ['code' => 'LLANTA-ALEACION', 'description' => 'LLANTA DE ALEACION', 'type' => 'VEHICLE_ACCESSORY'],
      ['code' => 'COBERTOR-CARGO', 'description' => 'COBERTOR DE CARGO', 'type' => 'VEHICLE_ACCESSORY'],
      ['code' => 'PROTECTOR-PARAGOLPE', 'description' => 'PROTECTOR DE PARAGOLPE', 'type' => 'VEHICLE_ACCESSORY'],
    ];

    foreach ($accessories as $accessory) {
      ApCommercialMasters::firstOrCreate([
        'code' => $accessory['code'],
        'type' => $accessory['type'],
      ], [
        'description' => $accessory['description'],
        'status' => true,
      ]);
    }

    $this->command->info('✅ Accesorios de vehículos creados en ApCommercialMasters!');
  }
}
