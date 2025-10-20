<?php

namespace Database\Seeders;

use App\Http\Services\gp\gestionsistema\PermissionService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\RolePermission;
use Illuminate\Database\Seeder;

// artisan db:seed --class=PermissionSeeder
class PermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * Crea permisos granulares iniciales organizados por módulo
   */
  public function run(): void
  {
    $permissions = [
      [
        'code' => 'opportunity.view_all_users',
        'name' => 'Ver Oportunidades de Todos los Usuarios',
        'description' => 'Permite ver y filtrar oportunidades de cualquier usuario (no solo las propias). Habilita el filtro por usuario en la vista de oportunidades.',
        'module' => 'opportunity',
        'policy_method' => 'viewAllUsers',
        'type' => 'custom',
        'is_active' => true,
      ],
    ];

    $permissionsCreated = [];

    foreach ($permissions as $permission) {
      $permission = Permission::updateOrCreate(
        ['code' => $permission['code']],
        $permission
      );

      $permissionsCreated[] = $permission->id;

    }

    $servicePermission = new PermissionService();
    // Asignar el permiso al rol de administrador 98
    $servicePermission->syncPermissionsToRole(98, $permissionsCreated);

    $this->command->info('✅ Permisos creados exitosamente: ' . count($permissions));
  }
}
