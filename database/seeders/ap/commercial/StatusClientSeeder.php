<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class StatusClientSeeder extends Seeder
{
  public function run(): void
  {
    ApCommercialMasters::where('type', 'STATUS_CLIENT')->delete();

    $data = [
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
