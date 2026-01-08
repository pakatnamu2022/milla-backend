<?php

namespace Database\Seeders\ap\compras;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use Illuminate\Database\Seeder;

class UnitMeasurementSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['dyn_code' => 'UND', 'nubefac_code' => '-', 'description' => 'UNIDAD'],
    ];

    foreach ($data as $item) {
      UnitMeasurement::firstOrCreate($item);
    }
  }
}
