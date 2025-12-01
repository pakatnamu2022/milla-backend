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

    $data = [
//      COMMERCIAL AP
      ['descripcion' => 'Agenda', 'submodule' => false, 'route' => 'agenda',
        'ruta' => '-', 'icon' => 'Calendar', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Clientes', 'submodule' => false, 'route' => 'clientes',
        'ruta' => '-', 'icon' => 'Handshake', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Proveedores', 'submodule' => false, 'route' => 'proveedores',
        'ruta' => '-', 'icon' => 'Container', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Compra Vehiculo Nuevo', 'submodule' => false, 'route' => 'compra-vehiculo-nuevo',
        'ruta' => '-', 'icon' => 'CarFront', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Comprobantes de Venta', 'submodule' => false, 'route' => 'documentos-electronicos',
        'ruta' => '-', 'icon' => 'ReceiptText', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Vehículos', 'submodule' => false, 'route' => 'vehiculos',
        'ruta' => '-', 'icon' => 'Car', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Solicitudes y Cotizaciones', 'submodule' => false, 'route' => 'solicitudes-cotizaciones',
        'ruta' => '-', 'icon' => 'MailCheck', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Dashboard de Leads', 'submodule' => false, 'route' => 'dashboard-visitas-leads',
        'ruta' => '-', 'icon' => 'LayoutDashboard', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Envios y Recepciones', 'submodule' => false, 'route' => 'envios-recepciones',
        'ruta' => '-', 'icon' => 'Package', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Motivos Descarte Leads', 'submodule' => false, 'route' => 'motivos-descarte',
        'ruta' => '-', 'icon' => 'ClipboardX', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Entrega de Vehiculos', 'submodule' => false, 'route' => 'entrega-vehiculo',
        'ruta' => '-', 'icon' => 'Truck', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Post Venta', 'submodule' => false, 'route' => null, 'slug' => 'post-venta',
        'ruta' => '-', 'icon' => 'Handshake', 'parent_id' => null, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Accesorios Homologados', 'submodule' => false, 'route' => 'accesorios-homologados',
        'ruta' => '-', 'icon' => 'Handshake', 'parent_id' => $POST_VENTA_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Asignación de Pares', 'submodule' => false, 'route' => 'asignacion-pares', 'slug' => 'asignacion-de-pares',
        'ruta' => '-', 'icon' => 'UserRoundCog', 'parent_id' => $EVALUATION, 'company_id' => $GP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Modelo de Evaluación', 'submodule' => false, 'route' => 'modelo-evaluacion', 'slug' => 'modelo-de-evaluacion',
        'ruta' => '-', 'icon' => 'FileBox', 'parent_id' => $EVALUATION, 'company_id' => $GP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Reportes', 'submodule' => false, 'route' => 'reportes', 'slug' => 'reportes',
        'ruta' => '-', 'icon' => 'Sheet', 'parent_id' => $COMMERCIAL_ID, 'company_id' => $AP, 'idPadre' => $VERSION_2,],
      ['descripcion' => 'Trabajadores', 'submodule' => false, 'route' => 'trabajadores', 'slug' => 'trabajadores',
        'ruta' => '-', 'icon' => 'User2', 'parent_id' => $PERSONAL, 'company_id' => $GP, 'idPadre' => $VERSION_2,],
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

      $this->command->info("Agregado a la base de datos..." . $view->id);
    }
  }
}
