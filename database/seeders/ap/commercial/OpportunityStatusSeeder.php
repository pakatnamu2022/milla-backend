<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class OpportunityStatusSeeder extends Seeder
{
  public function run(): void
  {
    ApCommercialMasters::where('type', 'OPPORTUNITY_STATUS')->delete();

    $data = [
      ['description' => 'TEMPLADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'WARM'],
      ['description' => 'CALIENTE', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'HOT'],
      ['description' => 'VENTA CONCRETADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'SOLD'],
      ['description' => 'CERRADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'CLOSED'],
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
