<?php

namespace Database\Seeders\gp\views;

use App\Http\Services\gp\gestionsistema\AccessService;
use App\Http\Services\gp\gestionsistema\ViewService;
use App\Models\gp\gestionsistema\Access;
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
    $VERSION_2 = 381;
    $COMMERCIAL_ID = 418;

    $viewService = new ViewService();
    $accessService = new AccessService();

    $data = [
//      COMMERCIAL AP
      ['descripcion' => 'Agenda', 'submodule' => false, 'route' => 'agenda',
        'ruta' => '-', 'icon' => 'Calendar', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
    ];

    foreach ($data as $item) {
      $view = View::updateOrCreate(
        [
          'descripcion' => $item['descripcion'],
          'company_id' => $item['company_id'],
          'route' => $item['route'],
          'parent_id' => $item['parent_id']
        ],
        $item
      );

      Access::firstOrCreate(
        [
          'role_id' => $TICS,
          'vista_id' => $view->id
        ],
        [
          'ver' => true,
          'crear' => true,
          'editar' => true,
          'anular' => true,
          'status_deleted' => 1,
        ]
      );


    }
  }
}
