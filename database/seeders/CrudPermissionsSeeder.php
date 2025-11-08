<?php

namespace Database\Seeders;

use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder para crear permisos CRUD básicos para todos los módulos
 * basándose en las vistas registradas en la base de datos
 *
 * Comando para ejecutar:
 * php artisan db:seed --class=CrudPermissionsSeeder
 */
class CrudPermissionsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->command->info("🔍 Consultando vistas desde la base de datos...");

    // Obtener todas las vistas activas desde la base de datos
    $views = View::where(function ($query) {
      $query->whereNull('status_deleted')
        ->orWhere('status_deleted', '=', 1)
        ->whereNotNull('route');
    })->get();

    if ($views->isEmpty()) {
      $this->command->warn("⚠️  No se encontraron vistas en la base de datos.");
      return;
    }

    $this->command->info("✅ Se encontraron {$views->count()} vistas.");

    // Convertir las vistas a formato de módulos
    // NO eliminamos duplicados, cada vista genera sus propios permisos
    $modules = [];
    foreach ($views as $view) {
      // Generar un código único combinando el slug/submodule con el ID de la vista
      $baseCode = $view->route;

      // Si el código base está vacío, usar el ID
      if (empty($baseCode)) {
        $baseCode = 'vista-' . $view->id;
      }

      // Usar submodule como nombre del módulo si existe, sino usar slug o descripcion slugificada
      $module = $view->submodule ?? $view->slug ?? Str::slug($view->descripcion);

      if (empty($module)) {
        $module = $view->slug ?? $view->descripcion ?? $view->id;
      }

      // Usar vista_id como clave única para evitar que se eliminen vistas con mismo slug
      // pero permitiendo que cada vista tenga sus propios permisos
      $uniqueKey = $view->id;

      $modules[$uniqueKey] = [
        'code' => $baseCode,
        'name' => $view->descripcion ?? "Vista {$view->id}",
        'module' => $module,
        'vista_id' => $view->id,
      ];
    }

    // Acciones CRUD básicas
    $crudActions = [
      'view' => [
        'label' => 'Ver',
        'description' => 'Permite visualizar información',
        'policy_method' => 'view',
      ],
      'create' => [
        'label' => 'Crear',
        'description' => 'Permite crear nuevos registros',
        'policy_method' => 'create',
      ],
      'update' => [
        'label' => 'Editar',
        'description' => 'Permite modificar registros existentes',
        'policy_method' => 'update',
      ],
      'delete' => [
        'label' => 'Eliminar',
        'description' => 'Permite eliminar o anular registros',
        'policy_method' => 'delete',
      ],
    ];

    $created = 0;
    $skipped = 0;
    $updated = 0;

    DB::beginTransaction();
    try {
      $this->command->info("🚀 Iniciando creación de permisos CRUD...");
      $this->command->newLine();

      foreach ($modules as $module) {
        $this->command->info("📦 Procesando módulo: {$module['name']} (vista_id: {$module['vista_id']})");

        foreach ($crudActions as $action => $actionConfig) {
          // Generar código único: incluir vista_id para evitar conflictos
          $code = "{$module['code']}.{$action}";

          // Verificar si ya existe un permiso para esta vista_id y acción específica
          $existingPermission = Permission::withTrashed()
            ->where('vista_id', $module['vista_id'])
            ->where('policy_method', $actionConfig['policy_method'])
            ->first();

          // Si no existe por vista_id, buscar por code (para mantener compatibilidad)
          if (!$existingPermission) {
            $existingPermission = Permission::withTrashed()->where('code', $code)->first();
          }

          if ($existingPermission) {
            // Si existe y está soft-deleted, restaurarlo y actualizarlo
            if ($existingPermission->trashed()) {
              $existingPermission->restore();
              $existingPermission->update([
                'name' => "{$actionConfig['label']} {$module['name']}",
                'description' => "{$actionConfig['description']} - {$module['name']}",
                'module' => $module['module'],
                'vista_id' => $module['vista_id'],
                'policy_method' => $actionConfig['policy_method'],
                'is_active' => true,
              ]);
              $updated++;
              $this->command->info("   🔄 Restaurado: {$code}");
            } else {
              // Ya existe y está activo, solo omitir
              $skipped++;
              $this->command->comment("   ⏭️  Omitido: {$code}");
            }
            continue;
          }

          // Crear nuevo permiso
          Permission::create([
            'code' => $code,
            'name' => "{$actionConfig['label']} {$module['name']}",
            'description' => "{$actionConfig['description']} - {$module['name']}",
            'module' => $module['module'],
            'vista_id' => $module['vista_id'],
            'policy_method' => $actionConfig['policy_method'],
            'is_active' => true,
          ]);

          $created++;
          $this->command->info("   ✅ Creado: {$code}");
        }
      }

      DB::commit();

      $this->command->newLine();
      $this->command->info("════════════════════════════════════════════════════════════");
      $this->command->info("  RESUMEN DE EJECUCIÓN");
      $this->command->info("════════════════════════════════════════════════════════════");
      $this->command->info("📊 Vistas procesadas: " . count($modules));
      $this->command->info("✅ Permisos creados: {$created}");
      $this->command->info("🔄 Permisos restaurados/actualizados: {$updated}");
      $this->command->info("⏭️  Permisos omitidos (ya existen): {$skipped}");
      $this->command->info("📊 Total procesado: " . ($created + $skipped + $updated));
      $this->command->info("════════════════════════════════════════════════════════════");
    } catch (\Exception $e) {
      DB::rollBack();
      $this->command->error("❌ Error: " . $e->getMessage());
      $this->command->error("Stack trace: " . $e->getTraceAsString());
      throw $e;
    }
  }
}

