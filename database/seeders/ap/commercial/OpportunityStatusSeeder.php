<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class OpportunityStatusSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'ABIERTA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'OPEN'],
      ['description' => 'FRIA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'COLD'],
      ['description' => 'TEMPLADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'WARM'],
      ['description' => 'CALIENTE', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'HOT'],
      ['description' => 'GANADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'WON'],
      ['description' => 'PERDIDA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'LOST'],
    ];

    foreach ($data as $item) {
      ApCommercialMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ], [
        'code' => $item['code'] ?? null,
      ]);
    }
  }
}
