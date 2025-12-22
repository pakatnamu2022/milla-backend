<?php

namespace Database\Seeders\gp\viaticos;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerDiemCategorySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $categories = [
      [
        'name' => 'Gerentes',
        'description' => 'Gerente General, Gerente de Área, Jefe de Oficina, Jefe de Proyecto, etc.',
        'active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'name' => 'Colaboradores',
        'description' => 'Empleados, Asistentes, Técnicos, etc.',
        'active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
      ],
    ];

    DB::table('gh_per_diem_category')->insert($categories);

    $this->command->info('   ✓ 2 Categorías de viáticos creadas');
  }
}

