<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $data = [
      [
        'parent_id' => null, 'code' => 'accommodation', 'name' => 'Alojamiento',
        'description' => 'Hotel lodging', 'requires_receipt' => true, 'order' => 1, 'active' => true,
      ],
      [
        'parent_id' => null, 'code' => 'transportation', 'name' => 'Pasajes',
        'description' => 'Bus, plane, or other transport tickets', 'requires_receipt' => true, 'order' => 2, 'active' => true,
      ],
      [
        'parent_id' => null, 'code' => 'meals', 'name' => 'Alimentación',
        'description' => 'Food and beverages', 'requires_receipt' => true, 'order' => 3, 'active' => true,
        'children' => [
          [
            'code' => 'breakfast', 'name' => 'Desayuno', 'description' => 'Morning meal',
            'requires_receipt' => true, 'order' => 1, 'active' => true,
          ],
          [
            'code' => 'lunch', 'name' => 'Almuerzo', 'description' => 'Midday meal',
            'requires_receipt' => true, 'order' => 2, 'active' => true,
          ],
          [
            'code' => 'dinner', 'name' => 'Cena', 'description' => 'Evening meal',
            'requires_receipt' => true, 'order' => 3, 'active' => true,
          ]
        ],
      ],
      [
        'parent_id' => null, 'code' => 'local_transport', 'name' => 'Transporte Local',
        'description' => 'Transportation within destination city (taxi, uber, etc.)', 'requires_receipt' => false, 'order' => 4, 'active' => true,
      ],
    ];

    foreach ($data as $item) {
      $children = $item['children'] ?? [];
      unset($item['children']);

      $expenseType = ExpenseType::create($item);

      foreach ($children as $child) {
        $child['parent_id'] = $expenseType->id;
        ExpenseType::create($child);
      }
    }
  }
}
