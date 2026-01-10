<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class=Database\Seeders\ap\commercial\OpportunityStatusSeeder
 */
class OpportunityStatusSeeder extends Seeder
{
  public function run(): void
  {
    ApMasters::where('type', 'OPPORTUNITY_STATUS')->delete();

    $data = [
      ['description' => 'FRIO', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'COLD'],
      ['description' => 'TEMPLADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'WARM'],
      ['description' => 'CALIENTE', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'HOT'],
      ['description' => 'VENTA CONCRETADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'SOLD'],
      ['description' => 'CERRADA', 'type' => 'OPPORTUNITY_STATUS', 'code' => 'CLOSED'],
    ];

    foreach ($data as $item) {
      ApMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ], [
        'code' => $item['code'] ?? null,
      ]);
    }
  }
}
