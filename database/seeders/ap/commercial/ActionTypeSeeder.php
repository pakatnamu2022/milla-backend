<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
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
      ApMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
