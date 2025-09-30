<?php

namespace Database\Seeders;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class OpportunityStatusSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'FRIA', 'type' => 'OPPORTUNITY_STATUS'],
      ['description' => 'TEMPLADA', 'type' => 'OPPORTUNITY_STATUS'],
      ['description' => 'CALIENTE', 'type' => 'OPPORTUNITY_STATUS'],
    ];

    foreach ($data as $item) {
      ApCommercialMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
