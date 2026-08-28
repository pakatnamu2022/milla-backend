<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Http\Services\gp\gestionsistema\PermissionService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crea las vistas (config_vista) y permisos (permission/role_permission) que
 * faltaban para el módulo Planillas: varias páginas ya existían en el
 * frontend (namu-frontend/src/app/gp/gestion-humana/planillas/*) pero nunca
 * se registraron en config_vista, y "Registro de Planilla" (vista 574) sí
 * existía pero no tenía NINGÚN permiso creado — por eso ni el rol TICS podía
 * verla en el menú aunque la ruta funcionara si se navegaba directo.
 *
 * Idempotente: usa updateOrCreate en vistas/permisos y
 * PermissionService::savePermissionsToRole() (upsert) para los roles, por lo
 * que correr este seeder varias veces no duplica nada ni revoca accesos.
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollViewsPermissionsSeeder"
 */
class PayrollViewsPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    $GP = 4; // company_id usado por el resto del árbol "Gestión Humana" (ver vista 77/503)
    $planillasParentId = 503; // Vista "Planillas" ya existente

    // Vista "Registro de Planilla" (574) ya existe pero no tiene permisos.
    $registroPlanillaViewId = 574;

    // Roles que ya tienen acceso a otras vistas hijas de "Planillas" (503) —
    // se les otorga también acceso a las vistas nuevas, para no dejar el
    // módulo inconsistente entre sus propias páginas.
    $roleIds = [
      98,  // TICS
      54,  // Gestor de Planillas
      68,  // Analista en Proyectos de Gestión Humana
      127, // Gerente Gestión Humana
      122, // Supervisor de Seguridad Patrimonial
    ];

    $this->command->info('Creando vistas y permisos faltantes del módulo Planillas...');

    DB::beginTransaction();
    try {
      $submodules = [
        [
          'descripcion' => 'Aseguradoras',
          'route' => 'aseguradora',
          'icon' => 'Building2',
        ],
        [
          'descripcion' => 'Asignación Familiar',
          'route' => 'asignacion-familiar',
          'icon' => 'Users',
        ],
        [
          'descripcion' => 'Bonificaciones',
          'route' => 'bonificaciones',
          'icon' => 'Gift',
        ],
        [
          'descripcion' => 'Condiciones de Trabajo',
          'route' => 'condiciones-trabajo',
          'icon' => 'Briefcase',
        ],
        [
          'descripcion' => 'Liquidación BB.SS.',
          'route' => 'liquidacion-bbss',
          'icon' => 'Calculator',
        ],
        [
          'descripcion' => 'Préstamos',
          'route' => 'prestamos',
          'icon' => 'HandCoins',
        ],
        [
          'descripcion' => 'Seguros',
          'route' => 'seguros',
          'icon' => 'Shield',
        ],
        [
          'descripcion' => 'Tarjeta de Alimentos',
          'route' => 'tarjeta-de-alimentos',
          'icon' => 'CreditCard',
        ],
        [
          'descripcion' => 'Tipo Día Trabajo',
          'route' => 'tipo-dia-trabajo',
          'icon' => 'CalendarCheck',
        ],
      ];

      $actionConfig = [
        'view' => ['label' => 'Ver', 'policy_method' => 'view'],
        'create' => ['label' => 'Crear', 'policy_method' => 'create'],
        'update' => ['label' => 'Editar', 'policy_method' => 'update'],
        'delete' => ['label' => 'Eliminar', 'policy_method' => 'delete'],
      ];

      $permissionIds = [];

      // ── Vistas nuevas + sus permisos CRUD ──────────────────────────────
      foreach ($submodules as $mod) {
        $view = View::updateOrCreate(
          ['route' => $mod['route'], 'company_id' => $GP, 'parent_id' => $planillasParentId],
          [
            'descripcion' => $mod['descripcion'],
            'submodule' => false,
            'slug' => $mod['route'],
            'route' => $mod['route'],
            'ruta' => '-',
            'icon' => $mod['icon'],
            'company_id' => $GP,
            'parent_id' => $planillasParentId,
            'idPadre' => 381,
          ]
        );
        $this->command->info("  Vista: {$mod['descripcion']} (ID: {$view->id})");

        foreach ($actionConfig as $action => $cfg) {
          $code = "{$mod['route']}.{$action}";

          $permission = Permission::updateOrCreate(
            ['code' => $code],
            [
              'code' => $code,
              'name' => "{$cfg['label']} {$mod['descripcion']}",
              'description' => "Permite {$cfg['label']} en {$mod['descripcion']}",
              'module' => 'planillas',
              'vista_id' => $view->id,
              'policy_method' => $cfg['policy_method'],
              'is_active' => true,
            ]
          );

          $permissionIds[] = $permission->id;
          $this->command->comment("    Permiso: {$code}");
        }
      }

      // ── Permisos faltantes para la vista ya existente "Registro de Planilla" ──
      $registroPlanillaView = View::find($registroPlanillaViewId);
      if ($registroPlanillaView) {
        $this->command->info("  Vista existente: {$registroPlanillaView->descripcion} (ID: {$registroPlanillaView->id}) — generando permisos");

        foreach ($actionConfig as $action => $cfg) {
          $code = "registro-planilla.{$action}";

          $permission = Permission::updateOrCreate(
            ['code' => $code],
            [
              'code' => $code,
              'name' => "{$cfg['label']} Registro de Planilla",
              'description' => "Permite {$cfg['label']} en Registro de Planilla",
              'module' => 'planillas',
              'vista_id' => $registroPlanillaView->id,
              'policy_method' => $cfg['policy_method'],
              'is_active' => true,
            ]
          );

          $permissionIds[] = $permission->id;
          $this->command->comment("    Permiso: {$code}");
        }
      } else {
        $this->command->warn("  Vista 'Registro de Planilla' (ID {$registroPlanillaViewId}) no encontrada — se omite.");
      }

      // ── Asignar todos los permisos nuevos a los roles del módulo ───────
      $this->command->newLine();
      $service = new PermissionService();
      foreach ($roleIds as $roleId) {
        $this->command->info("Asignando permisos al rol ID {$roleId}...");
        $service->savePermissionsToRole($roleId, $permissionIds);
      }

      DB::commit();

      $this->command->newLine();
      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info('  Planillas — Vistas y permisos');
      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info('Vistas nuevas: ' . count($submodules) . ' + 1 existente (Registro de Planilla) actualizada con permisos.');
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
