<?php

namespace Database\Seeders\gp\gestionhumana\reclutamiento;

use App\Http\Services\gp\gestionsistema\PermissionService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vistas (config_vista) y permisos del modulo "Reclutamiento y Seleccion" en namu-frontend,
 * migracion incremental desde web_millagp_2 (controllers app/Http/Controllers/Reclutamiento/*).
 *
 * Fase 1 (Postulacion): las vistas de reclutamiento cuelgan de "Gestion de Personal" (vista 456),
 * como hermanas de "Trabajadores" (457). Empieza con "Procesos de Postulacion"
 * (legacy idVista 50, ProcesoPostulacionController).
 *
 * Idempotente: updateOrCreate en vistas/permisos + PermissionService::savePermissionsToRole (upsert).
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\reclutamiento\RecruitmentViewsPermissionsSeeder"
 */
class RecruitmentViewsPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    $GP = 4;                  // company_id del arbol "Gestion Humana"
    $personalMgmtId = 456;    // Vista "Gestion de Personal" (padre de "Trabajadores" = 457)
    $idPadre = 381;           // idPadre legacy del arbol GH

    // Roles del cluster de Gestion Humana que ya ven "Trabajadores" (permisos 410-413).
    $roleIds = [
      98,  // TICS
      102, // TIC's TP
      127, // GERENTE GESTION HUMANA
      68,  // ANALISTA EN PROYECTOS DE GESTION HUMANA
      24,  // GESTION HUMANA
      138, // GESTION HUMANA AP
    ];

    $actionConfig = [
      'view'   => 'Ver',
      'create' => 'Crear',
      'update' => 'Editar',
      'delete' => 'Eliminar',
    ];

    $this->command->info('Reclutamiento y Seleccion — vistas y permisos (Fase 1)...');

    DB::beginTransaction();
    try {
      // ── Vistas hoja de la fase 1 (hermanas de "Trabajadores", hijas de 456) ──
      $modules = [
        [
          'descripcion' => 'Procesos de Postulacion',
          'route'       => 'procesos-postulacion',
          'icon'        => 'ClipboardList',
          'module'      => 'reclutamiento',
        ],
      ];

      $permissionIds = [];

      foreach ($modules as $mod) {
        $view = View::updateOrCreate(
          ['route' => $mod['route'], 'company_id' => $GP, 'parent_id' => $personalMgmtId],
          [
            'descripcion' => $mod['descripcion'],
            'submodule'   => false,
            'slug'        => $mod['route'],
            'route'       => $mod['route'],
            'ruta'        => '-',
            'icon'        => $mod['icon'],
            'company_id'  => $GP,
            'parent_id'   => $personalMgmtId,
            'idPadre'     => $idPadre,
          ]
        );
        $this->command->info("  Vista: {$mod['descripcion']} (ID: {$view->id})");

        foreach ($actionConfig as $action => $label) {
          $code = "{$mod['route']}.{$action}";

          $permission = Permission::updateOrCreate(
            ['code' => $code],
            [
              'code'          => $code,
              'name'          => "{$label} {$mod['descripcion']}",
              'description'   => "Permite {$label} en {$mod['descripcion']}",
              'module'        => $mod['module'],
              'vista_id'      => $view->id,
              'policy_method' => $action,
              'is_active'     => true,
            ]
          );

          $permissionIds[] = $permission->id;
          $this->command->comment("    Permiso: {$code}");
        }
      }

      $service = new PermissionService();
      foreach ($roleIds as $roleId) {
        $this->command->info("Asignando permisos al rol ID {$roleId}...");
        $service->savePermissionsToRole($roleId, $permissionIds);
      }

      DB::commit();

      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info('  Reclutamiento — Fase 1 (hijas de Gestion de Personal / 456)');
      $this->command->info('  ' . count($modules) . ' vista(s), ' . count($permissionIds) . ' permisos.');
      $this->command->info('  Roles actualizados: ' . implode(', ', $roleIds));
      $this->command->info('════════════════════════════════════════════════════════');
    } catch (\Exception $e) {
      DB::rollBack();
      $this->command->error('Error: ' . $e->getMessage());
      throw $e;
    }
  }
}
