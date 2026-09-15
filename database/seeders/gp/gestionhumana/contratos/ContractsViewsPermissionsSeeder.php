<?php

namespace Database\Seeders\gp\gestionhumana\contratos;

use App\Http\Services\gp\gestionsistema\PermissionService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vistas (config_vista) y permisos del modulo "Contratos" (Fase 5) en namu-frontend,
 * migracion desde web_millagp_2 (AdministracionPersonal/ContratoController,
 * Configuraciones/TipoContratoController, Configuraciones/FirmantesController,
 * AdministracionPersonal/PlantillaContratoController).
 *
 * Las vistas cuelgan de "Gestion de Personal" (456), como hermanas de "Trabajadores"
 * (457) y del modulo de Reclutamiento — ver docs/PERMISOS_SELECCION_ONBOARDING.md §4.
 *
 * "contratos" ademas recibe sub-permisos de flujo de firma (no son CRUD estandar):
 * solicitar, aprobar-gh, firmar, enviar-trabajador, confirmar lectura, gestion por lotes.
 *
 * Idempotente: updateOrCreate en vistas/permisos + PermissionService::savePermissionsToRole (upsert).
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\contratos\ContractsViewsPermissionsSeeder"
 */
class ContractsViewsPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    $GP = 4;                  // company_id del arbol "Gestion Humana"
    $personalMgmtId = 456;    // Vista "Gestion de Personal" (padre de "Trabajadores" = 457)
    $idPadre = 381;           // idPadre legacy del arbol GH

    // Roles del cluster de Gestion Humana que ya ven "Trabajadores" (permisos 410-413),
    // mismo cluster usado en RecruitmentViewsPermissionsSeeder.
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

    $flowPermissions = [
      'contratos.firma.solicitar'         => 'Solicitar firma',
      'contratos.firma.aprobar-gh'        => 'Conformidad RRHH',
      'contratos.firma.firmar'            => 'Firmar (firmante)',
      'contratos.firma.enviar-trabajador' => 'Enviar al trabajador',
      'contratos.lectura.confirmar'       => 'Confirmar lectura',
      'contratos.lote.gestionar'          => 'Gestión por lotes',
    ];

    $this->command->info('Contratos (Fase 5) — vistas y permisos...');

    DB::beginTransaction();
    try {
      $modules = [
        [
          'descripcion' => 'Contratos',
          'route'       => 'contratos',
          'icon'        => 'FileText',
          'module'      => 'contratos',
        ],
        [
          'descripcion' => 'Plantillas de Contrato',
          'route'       => 'plantillas-contrato',
          'icon'        => 'FileCode',
          'module'      => 'contratos',
        ],
        [
          'descripcion' => 'Tipos de Contrato',
          'route'       => 'tipos-contrato',
          'icon'        => 'Tags',
          'module'      => 'contratos',
        ],
        [
          'descripcion' => 'Firmantes',
          'route'       => 'firmantes',
          'icon'        => 'PenTool',
          'module'      => 'contratos',
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

        if ($mod['route'] === 'contratos') {
          foreach ($flowPermissions as $code => $label) {
            $permission = Permission::updateOrCreate(
              ['code' => $code],
              [
                'code'          => $code,
                'name'          => $label,
                'description'   => "Permite {$label} en Contratos",
                'module'        => $mod['module'],
                'vista_id'      => $view->id,
                'policy_method' => $code,
                'is_active'     => true,
              ]
            );

            $permissionIds[] = $permission->id;
            $this->command->comment("    Permiso (flujo): {$code}");
          }
        }
      }

      $service = new PermissionService();
      foreach ($roleIds as $roleId) {
        $this->command->info("Asignando permisos al rol ID {$roleId}...");
        $service->savePermissionsToRole($roleId, $permissionIds);
      }

      DB::commit();

      $this->command->info('════════════════════════════════════════════════════════');
      $this->command->info('  Contratos — Fase 5 (hijas de Gestion de Personal / 456)');
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
