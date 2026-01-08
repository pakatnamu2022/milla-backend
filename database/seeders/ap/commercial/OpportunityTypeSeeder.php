<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use Illuminate\Database\Seeder;

class OpportunityTypeSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'VENTA VN', 'type' => 'OPPORTUNITY_TYPE'],
    ];

    foreach ($data as $item) {
      ApMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
