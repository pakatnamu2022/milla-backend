<?php

namespace Database\Seeders;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class OpportunityTypeSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'VENTA VN', 'type' => 'OPPORTUNITY_TYPE'],
    ];

    foreach ($data as $item) {
      ApCommercialMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
