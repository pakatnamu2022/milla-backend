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
      /**
       * VIATICOS
       */
      ['descripcion' => 'Viaticos', 'submodule' => false, 'route' => 'viaticos', 'parent_id' => $GH, 'slug' => 'viaticos',
        'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2,
        'children' => [
          ['descripcion' => 'Categoría de Viaticos', 'submodule' => false, 'route' => 'categoria-viaticos', 'slug' => 'categoria-viaticos',
            'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2,],
          ['descripcion' => 'Política de Viaticos', 'submodule' => false, 'route' => 'politica-viaticos', 'slug' => 'politica-viaticos',
            'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2,],
          ['descripcion' => 'Solicitud de Viaticos', 'submodule' => false, 'route' => 'solicitud-viaticos', 'slug' => 'solicitud-viaticos',
            'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2,],
          ['descripcion' => 'Convenios Hoteles', 'submodule' => false, 'route' => 'convenios-hoteles', 'slug' => 'convenios-hoteles',
            'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2,],
//          ['descripcion' => 'Reservaciones Hoteles', 'submodule' => false, 'route' => 'reservaciones-hoteles', 'slug' => 'reservaciones-hoteles',
//            'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2,],
        ]
      ],
      ['descripcion' => 'Maestros', 'submodule' => false, 'route' => 'maestros', 'parent_id' => $GH, 'slug' => 'viaticos',
        'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2],
      ['descripcion' => 'Maestros', 'submodule' => false, 'route' => 'maestros', 'parent_id' => $GH, 'slug' => 'viaticos',
        'ruta' => '-', 'icon' => 'TicketsPlane', 'company_id' => $GP, 'idPadre' => $VERSION_2]

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
      }
    }
  }
}
