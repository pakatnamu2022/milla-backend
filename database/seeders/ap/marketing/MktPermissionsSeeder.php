<?php

namespace Database\Seeders\ap\marketing;

use App\Http\Services\gp\gestionsistema\PermissionService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * php artisan db:seed --class=Database\Seeders\ap\marketing\MktPermissionsSeeder
 */
class MktPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    $TICS = 98;
    $AP = 3;
    $parentId = null; // Ajustar si Marketing cuelga de un padre (ej. 418 AP Comercial)

    $this->command->info('Creando vistas y permisos del módulo Marketing...');

    DB::beginTransaction();
    try {
      // ── Vista padre ────────────────────────────────────────────────────
      $marketingView = View::updateOrCreate(
        ['route' => 'marketing', 'company_id' => $AP, 'parent_id' => $parentId],
        [
          'descripcion' => 'Marketing',
          'submodule'   => false,
          'slug'        => 'marketing',
          'route'       => 'marketing',
          'ruta'        => null,
          'icon'        => 'Megaphone',
          'company_id'  => $AP,
          'parent_id'   => $parentId,
        ]
      );
      $this->command->info("Vista padre: Marketing (ID: {$marketingView->id})");

      // ── Submódulos: route, acciones permitidas ─────────────────────────
      $submodules = [
        [
          'descripcion' => 'Dashboard',
          'route'       => 'dashboard',
          'slug'        => 'dashboard',
          'icon'        => 'LayoutDashboard',
          'actions'     => ['view'],                           // Solo lectura
        ],
        [
          'descripcion' => 'Planes',
          'route'       => 'planes',
          'slug'        => 'planes',
          'icon'        => 'FileText',
          'actions'     => ['view', 'create', 'update', 'delete'],
        ],
        [
          'descripcion' => 'Presupuestos',
          'route'       => 'presupuestos',
          'slug'        => 'presupuestos',
          'icon'        => 'Wallet',
          'actions'     => ['view', 'create', 'update', 'delete'],
        ],
        [
          'descripcion' => 'Actividades',
          'route'       => 'actividades',
          'slug'        => 'actividades',
          'icon'        => 'CalendarDays',
          'actions'     => ['view', 'create', 'update', 'delete'],
        ],
        [
          'descripcion' => 'Propuestas',
          'route'       => 'propuestas',
          'slug'        => 'propuestas',
          'icon'        => 'FilePlus',
          'actions'     => ['view', 'create', 'update', 'delete'],
        ],
        [
          'descripcion' => 'Órdenes de Compra',
          'route'       => 'ordenes-compra',
          'slug'        => 'ordenes-compra',
          'icon'        => 'ShoppingCart',
          'actions'     => ['view', 'create', 'update', 'delete'],
        ],
        [
          'descripcion' => 'Sustentos',
          'route'       => 'sustentos',
          'slug'        => 'sustentos',
          'icon'        => 'Paperclip',
          'actions'     => ['view', 'create'],                 // Sin actualizar
        ],
        [
          'descripcion' => 'KPIs',
          'route'       => 'kpis',
          'slug'        => 'kpis',
          'icon'        => 'TrendingUp',
          'actions'     => ['view', 'create', 'update', 'delete'],
        ],
      ];

      $actionConfig = [
        'view'   => ['label' => 'Ver', 'policy_method' => 'view'],
        'create' => ['label' => 'Crear', 'policy_method' => 'create'],
        'update' => ['label' => 'Editar', 'policy_method' => 'update'],
        'delete' => ['label' => 'Eliminar', 'policy_method' => 'delete'],
      ];

      $permissionIds = [];

      foreach ($submodules as $mod) {
        // ── Vista del submódulo ────────────────────────────────────────
        $view = View::updateOrCreate(
          ['route' => $mod['route'], 'company_id' => $AP, 'parent_id' => $marketingView->id],
          [
            'descripcion' => $mod['descripcion'],
            'submodule'   => false,
            'slug'        => $mod['slug'],
            'route'       => $mod['route'],
            'ruta'        => '-',
            'icon'        => $mod['icon'],
            'company_id'  => $AP,
            'parent_id'   => $marketingView->id,
          ]
        );
        $this->command->info("  Vista: {$mod['descripcion']} (ID: {$view->id})");

        // ── Permisos del submódulo ─────────────────────────────────────
        foreach ($mod['actions'] as $action) {
          $cfg = $actionConfig[$action];
          $code = "{$mod['route']}.{$action}";

          $permission = Permission::updateOrCreate(
            ['code' => $code],
            [
              'code'          => $code,
              'name'          => "{$cfg['label']} {$mod['descripcion']}",
              'description'   => "Permite {$cfg['label']} en {$mod['descripcion']}",
              'module'        => 'marketing',
              'vista_id'      => $view->id,
              'policy_method' => $cfg['policy_method'],
              'is_active'     => true,
            ]
          );

          $permissionIds[] = $permission->id;
          $this->command->comment("    Permiso: {$code}");
        }
      }

      // ── Asignar permisos al rol TICS (role_permission + config_asigxvistaxrole) ──
      $this->command->newLine();
      $this->command->info("Asignando permisos al rol TICS (ID: {$TICS})...");

      // savePermissionsToRole escribe en role_permission Y sincroniza
      // los permisos .view en config_asigxvistaxrole (Access), igual que el
      // botón "Sincronizar permisos" del panel de gestión.
      $service = new PermissionService();
      $service->savePermissionsToRole($TICS, $permissionIds);

      DB::commit();

      $this->command->newLine();
      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info('  Marketing — Resumen');
      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->table(
        ['Submódulo', 'Permisos'],
        array_map(
          fn($m) => [$m['descripcion'], implode(', ', $m['actions'])],
          $submodules
        )
      );
      $this->command->info("Total permisos: " . count($permissionIds));
      $this->command->info("Asignados al rol TICS ({$TICS}) + config_asigxvistaxrole sincronizado.");
      $this->command->info('════════════════════════════════════════════════════════');
    } catch (\Exception $e) {
      DB::rollBack();
      $this->command->error('Error: ' . $e->getMessage());
      throw $e;
    }
  }
}
