<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class ActionContactTypeSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'EMAIL', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'TELÉFONO', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'REUNIÓN', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'VIDEOLLAMADA', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'WHATSAPP', 'type' => 'ACTION_CONTACT_TYPE'],
    ];

    foreach ($data as $item) {
      ApCommercialMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
