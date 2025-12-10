<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use Illuminate\Database\Seeder;

class PerDiemCategoriesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $data = [
      [
        'name' => 'Gerentes',
        'description' => 'Gerente General, Gerente de Área, Jefe de Oficina, Jefe de Proyecto, etc.',
        'order' => 1,
        'active' => true,
      ],
      [
        'name' => 'Colaboradores',
        'description' => 'Empleados, Asistentes, Técnicos, etc.',
        'order' => 2,
        'active' => true,
      ],
    ];

    foreach ($data as $item) {
      PerDiemCategory::firstOrCreate([
        'name' => $item['name'],
      ], $item);
    }

  }
}
