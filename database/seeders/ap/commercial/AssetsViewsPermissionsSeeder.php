<?php

namespace Database\Seeders\ap\commercial;

use App\Http\Services\gp\gestionsistema\PermissionService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crea la vista (config_vista) y permisos (permission/role_permission) para el
 * nuevo módulo "Activos" (ap/comercial/activos): un vehículo en INVENTARIO_VN se
 * convierte en activo fijo asignado a un trabajador, con transacción de inventario
 * en Dynamics (cuenta 20 -> warehouse.asset_account 33).
 *
 * La vista cuelga de "Comercial" (config_vista id 418, company_id 3 = AP), igual
 * que "Traslados" (557), "Envios y Recepciones" (436) y "Vehículos" (438).
 *
 * Idempotente: updateOrCreate en vista/permisos y
 * PermissionService::savePermissionsToRole() (upsert) para los roles.
 *
 * php artisan db:seed --class="Database\Seeders\ap\commercial\AssetsViewsPermissionsSeeder"
 */
class AssetsViewsPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    $AP = 3;             // company_id del árbol "Comercial" de AP
    $comercialParentId = 418; // Vista "Comercial" ya existente
    $idPadre = 381;      // idPadre usado por las vistas hermanas (traslados/vehiculos)

    // Roles que hoy ya tienen acceso a "Traslados" (permiso traslados.view, id 1245).
    // Se les otorga también acceso a "Activos" para no dejar el módulo inconsistente.
    $roleIds = [
      98,  // TICS
      109, // Jefe de Contabilidad AP
      110, // Analista Zonal AP
      124, // Practicante de Contabilidad AP
    ];

    $this->command->info('Creando vista y permisos del módulo Activos...');

    DB::beginTransaction();
    try {
      $route = 'activos';
      $descripcion = 'Activos';

      $view = View::updateOrCreate(
        ['route' => $route, 'company_id' => $AP, 'parent_id' => $comercialParentId],
        [
          'descripcion' => $descripcion,
          'submodule'   => false,
          'slug'        => $route,
          'route'       => $route,
          'ruta'        => '-',
          'icon'        => 'Car',
          'company_id'  => $AP,
          'parent_id'   => $comercialParentId,
          'idPadre'     => $idPadre,
        ]
      );
      $this->command->info("  Vista: {$descripcion} (ID: {$view->id})");

      // v1 = solo alta (no baja/reversión). Igual creamos los 4 permisos CRUD
      // para que el panel de gestión pueda ajustarlos sin re-seed.
      $actionConfig = [
        'view'   => ['label' => 'Ver', 'policy_method' => 'view'],
        'create' => ['label' => 'Crear', 'policy_method' => 'create'],
        'update' => ['label' => 'Editar', 'policy_method' => 'update'],
        'delete' => ['label' => 'Eliminar', 'policy_method' => 'delete'],
      ];

      $permissionIds = [];
      foreach ($actionConfig as $action => $cfg) {
        $code = "{$route}.{$action}";

        $permission = Permission::updateOrCreate(
          ['code' => $code],
          [
            'code'          => $code,
            'name'          => "{$cfg['label']} {$descripcion}",
            'description'   => "Permite {$cfg['label']} en {$descripcion}",
            'module'        => 'activos',
            'vista_id'      => $view->id,
            'policy_method' => $cfg['policy_method'],
            'is_active'     => true,
          ]
        );

        $permissionIds[] = $permission->id;
        $this->command->comment("    Permiso: {$code}");
      }

      $this->command->newLine();
      $service = new PermissionService();
      foreach ($roleIds as $roleId) {
        $this->command->info("Asignando permisos al rol ID {$roleId}...");
        $service->savePermissionsToRole($roleId, $permissionIds);
      }

      DB::commit();

      $this->command->newLine();
      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info('  Activos — Vista y permisos');
      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info("Vista: {$descripcion} (ID: {$view->id}) bajo Comercial ({$comercialParentId}).");
      $this->command->info('Total permisos: ' . count($permissionIds));
      $this->command->info('Roles actualizados: ' . implode(', ', $roleIds));
      $this->command->info('════════════════════════════════════════════════════════');
    } catch (\Exception $e) {
      DB::rollBack();
      $this->command->error('Error: ' . $e->getMessage());
      throw $e;
    }
  }
}
