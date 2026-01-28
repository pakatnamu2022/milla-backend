<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use Illuminate\Database\Seeder;

class ApCommercialMastersMinimalSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      // ORIGIN
      ['description' => 'WEB', 'type' => 'ORIGIN'],
      ['description' => 'REFERIDO', 'type' => 'ORIGIN'],

      // TYPE_PERSON
      ['description' => 'NATURAL', 'type' => 'TYPE_PERSON'],
      ['description' => 'JURIDICA', 'type' => 'TYPE_PERSON'],

      // TYPE_DOCUMENT
      ['description' => 'DNI', 'type' => 'TYPE_DOCUMENT', 'code' => 8],
      ['description' => 'RUC', 'type' => 'TYPE_DOCUMENT', 'code' => 11],

      // PERSON_SEGMENT
      ['description' => 'PREMIUM', 'type' => 'PERSON_SEGMENT'],
      ['description' => 'STANDARD', 'type' => 'PERSON_SEGMENT'],

      // ACTIVITY_ECONOMIC
      ['description' => 'COMERCIO', 'type' => 'ACTIVITY_ECONOMIC'],
      ['description' => 'SERVICIOS', 'type' => 'ACTIVITY_ECONOMIC'],

      // GENDER
      ['description' => 'MASCULINO', 'type' => 'GENDER'],
      ['description' => 'FEMENINO', 'type' => 'GENDER'],
    ];

    foreach ($data as $item) {
      ApMasters::firstOrCreate([
        'description' => $item['description'],
        'type' => $item['type'],
      ], [
        'code' => $item['code'] ?? null,
      ]);
    }

    $this->command->info('✅ Maestros mínimos de ApMasters creados!');
  }
}
