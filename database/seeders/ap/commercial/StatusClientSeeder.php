<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class=Database\Seeders\ap\commercial\StatusClientSeeder
 */
class StatusClientSeeder extends Seeder
{
  public function run(): void
  {
    ApMasters::where('type', 'STATUS_CLIENT')->delete();

    $data = [
      ['description' => 'FRIO', 'type' => 'STATUS_CLIENT'],
      ['description' => 'TEMPLADO', 'type' => 'STATUS_CLIENT'],
      ['description' => 'CALIENTE', 'type' => 'STATUS_CLIENT'],
    ];

    foreach ($data as $item) {
      ApMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
