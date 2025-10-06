<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class ActionTypeSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'SEGUIMIENTO', 'type' => 'ACTION_TYPE'],
      ['description' => 'OFERTA', 'type' => 'ACTION_TYPE'],
    ];

    foreach ($data as $item) {
      ApCommercialMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
