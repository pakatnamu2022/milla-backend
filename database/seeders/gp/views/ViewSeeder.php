<?php

namespace Database\Seeders\gp\views;

use App\Http\Services\gp\gestionsistema\AccessService;
use App\Http\Services\gp\gestionsistema\ViewService;
use App\Models\gp\gestionsistema\Access;
use App\Models\gp\gestionsistema\Permission;
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
    $POST_VENTA_ID = 431;

    $viewService = new ViewService();
    $accessService = new AccessService();

    $data = [
//      COMMERCIAL AP
      ['descripcion' => 'Agenda', 'submodule' => false, 'route' => 'agenda',
        'ruta' => '-', 'icon' => 'Calendar', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Clientes', 'submodule' => false, 'route' => 'clientes',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Proveedores', 'submodule' => false, 'route' => 'proveedores',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Cotización / Solicitud Compra', 'submodule' => false, 'route' => 'cotizacion-solicitud-compra',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Compra Vehiculo Nuevo', 'submodule' => false, 'route' => 'compra-vehiculo-nuevo',
        'ruta' => '-', 'icon' => 'CarFront', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Comprobantes de Venta', 'submodule' => false, 'route' => 'electronic-documents',
        'ruta' => '-', 'icon' => 'ReceiptText', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Vehículos', 'submodule' => false, 'route' => 'vehiculos',
        'ruta' => '-', 'icon' => 'Car', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Solicitudes y Cotizaciones', 'submodule' => false, 'route' => 'solicitudes-cotizaciones',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Dashboard de Leads', 'submodule' => false, 'route' => 'dashboard-visitas-leads',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Envios y Recepciones', 'submodule' => false, 'route' => 'envios-recepciones',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Motivos Descarte Leads', 'submodule' => false, 'route' => 'motivos-descarte',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Entrega de Vehiculos', 'submodule' => false, 'route' => 'entrega-vehiculo',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Post Venta', 'submodule' => false, 'route' => null, 'slug' => 'post-venta',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => null, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Accesorios Homologados', 'submodule' => false, 'route' => 'accesorios-homologados',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $POST_VENTA_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
    ];

    $DELETE = [
      ['descripcion' => 'Vehículos VN', 'submodule' => false, 'route' => 'vehiculos-vn',
        'ruta' => '-', 'icon' => 'CircleDot', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
    ];


    foreach ($data as $item) {
      View::updateOrCreate(
        [
          'descripcion' => $item['descripcion'],
          'company_id' => $item['company_id'],
          'route' => $item['route'],
          'parent_id' => $item['parent_id']
        ],
        $item
      );


    }
  }
}
