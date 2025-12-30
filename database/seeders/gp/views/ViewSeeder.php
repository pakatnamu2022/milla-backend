<?php

namespace Database\Seeders\gp\views;

use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class=Database\Seeders\gp\views\ViewSeeder
 */
class ViewSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $TICS = 98;

    $AP = 3;
    $GP = 4;

//    VIEWS
    $VERSION_2 = 381;
    $COMMERCIAL_ID = 418;
    $POST_VENTA_ID = 431;
    $EVALUATION = 308;
    $PERSONAL = 46;
    $GH = 77;

    $data = [
      ['descripcion' => 'Contabilidad', 'submodule' => true, 'slug' => 'contabilidad', 'route' => 'contabilidad', 'ruta' => null,
        'icon' => 'TicketsPlane', 'company_id' => $AP, 'idPadre' => $VERSION_2, 'parent_id' => null,
        'children' => [
          ['descripcion' => 'Viaticos AP', 'submodule' => false, 'route' => 'viaticos-ap', 'slug' => 'viaticos-ap',
            'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $AP, 'idPadre' => $VERSION_2,
            'children' => [
              ['descripcion' => 'Solicitud de Viaticos', 'submodule' => false, 'route' => 'solicitud-viaticos', 'slug' => 'solicitud-viaticos',
                'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $AP, 'idPadre' => $VERSION_2,],
            ]
          ],
        ],
      ],
    ];

    foreach ($data as $item) {
      $children = $item['children'] ?? [];
      unset($item['children']);

      $view = View::updateOrCreate(
        [
          'descripcion' => $item['descripcion'],
          'company_id' => $item['company_id'],
          'route' => $item['route'],
          'parent_id' => $item['parent_id']
        ],
        $item
      );

      $this->command->info("Agregado a la base de datos..." . $view->id);

      // Procesar children si existen
      foreach ($children as $child) {
        $grandChildren = $child['children'] ?? [];
        unset($child['children']);
        $child['parent_id'] = $view->id;

        $childView = View::updateOrCreate(
          [
            'descripcion' => $child['descripcion'],
            'company_id' => $child['company_id'],
            'route' => $child['route'],
            'parent_id' => $child['parent_id']
          ],
          $child
        );

        $this->command->info("  - Child agregado: " . $childView->id);

        // Procesar grandchildren (tercer nivel)
        foreach ($grandChildren as $grandChild) {
          unset($grandChild['children']);
          $grandChild['parent_id'] = $childView->id;

          $grandChildView = View::updateOrCreate(
            [
              'descripcion' => $grandChild['descripcion'],
              'company_id' => $grandChild['company_id'],
              'route' => $grandChild['route'],
              'parent_id' => $grandChild['parent_id']
            ],
            $grandChild
          );

          $this->command->info("    - GrandChild agregado: " . $grandChildView->id);
        }
      }
    }
  }
}
