<?php

namespace Database\Seeders;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Seeder;

class ClientStatusSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['description' => 'PROSPECTO', 'type' => 'CLIENT_STATUS', 'code' => 'PROSPECT'],
      ['description' => 'CALIFICADO', 'type' => 'CLIENT_STATUS', 'code' => 'QUALIFIED'],
      ['description' => 'EN NEGOCIACIÓN', 'type' => 'CLIENT_STATUS', 'code' => 'NEGOTIATION'],
      ['description' => 'LISTO PARA CERRAR', 'type' => 'CLIENT_STATUS', 'code' => 'READY_TO_CLOSE'],
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
