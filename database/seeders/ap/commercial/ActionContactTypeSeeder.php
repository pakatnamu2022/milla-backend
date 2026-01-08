<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use Illuminate\Database\Seeder;

class ActionContactTypeSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'EMAIL', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'TELEFONO', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'REUNION', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'VIDEOLLAMADA', 'type' => 'ACTION_CONTACT_TYPE'],
      ['description' => 'WHATSAPP', 'type' => 'ACTION_CONTACT_TYPE'],
    ];

    foreach ($data as $item) {
      ApMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ]);
    }
  }
}
