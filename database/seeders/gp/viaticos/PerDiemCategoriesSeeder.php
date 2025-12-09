<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        'name' => 'Managers',
        'description' => 'General Manager, Business Manager, Operations Manager, etc.',
        'order' => 1,
        'active' => true,
      ],
      [
        'name' => 'Other Employees',
        'description' => 'Supervisors, Coordinators, Analysts, Assistants, Technicians, etc.',
        'order' => 2,
        'active' => true,
      ],
    ];

    foreach ($data as $item) {
      PerDiemCategory::create($item);
    }

  }
}
