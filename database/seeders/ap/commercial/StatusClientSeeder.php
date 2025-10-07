<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class=Database\Seeders\ap\commercial\StatusClientSeeder
 */
class StatusClientSeeder extends Seeder
{
  public function run(): void
  {
    ApCommercialMasters::where('type', 'STATUS_CLIENT')->delete();

    $data = [
      ['description' => 'FRIO', 'type' => 'STATUS_CLIENT'],
      ['description' => 'TEMPLADO', 'type' => 'STATUS_CLIENT'],
      ['description' => 'CALIENTE', 'type' => 'STATUS_CLIENT'],
    ];

    foreach ($data as $item) {
      ApCommercialMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
