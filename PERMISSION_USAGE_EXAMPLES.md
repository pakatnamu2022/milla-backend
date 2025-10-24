# Ejemplos de Uso del Modelo Permission

Guía práctica con ejemplos de código para utilizar todos los métodos del modelo Permission.

## Índice

1. [Métodos Básicos](#métodos-básicos)
2. [Métodos de Relaciones](#métodos-de-relaciones)
3. [Scopes (Filtros)](#scopes-filtros)
4. [Métodos Avanzados](#métodos-avanzados)
5. [Generación Automática](#generación-automática)
6. [Import/Export](#importexport)
7. [Casos de Uso Reales](#casos-de-uso-reales)

---

## Métodos Básicos

### Crear un Permiso

```php
use App\Models\gp\gestionsistema\Permission;

// Método básico
$permission = Permission::create([
    'code' => 'vehicle_purchase_order.create',
    'name' => 'Crear Orden de Compra',
    'description' => 'Permite crear nuevas órdenes de compra de vehículos',
    'module' => 'vehicle_purchase_order',
    'vista_id' => 15,
    'type' => 'basic',
    'is_active' => true,
]);

// Con updateOrCreate (evita duplicados)
$permission = Permission::updateOrCreate(
    ['code' => 'vehicle_purchase_order.create'],
    [
        'name' => 'Crear Orden de Compra',
        'module' => 'vehicle_purchase_order',
        'type' => 'basic',
    ]
);
```

### Actualizar un Permiso

```php
$permission = Permission::find(45);
$permission->update([
    'name' => 'Nuevo nombre',
    'is_active' => false,
]);

// O con propiedades individuales
$permission->name = 'Nuevo nombre';
$permission->description = 'Nueva descripción';
$permission->save();
```

### Eliminar un Permiso (Soft Delete)

```php
$permission = Permission::find(45);
$permission->delete(); // Soft delete

// Restaurar
$permission->restore();

// Eliminar permanentemente
$permission->forceDelete();
```

---

## Métodos de Relaciones

### 1. Verificar si está asignado a un Rol

```php
$permission = Permission::find(45);
$roleId = 2;

if ($permission->isGrantedToRole($roleId)) {
    echo "El permiso está asignado al rol";
} else {
    echo "El permiso NO está asignado al rol";
}
```

### 2. Obtener Roles que tienen el Permiso

```php
$permission = Permission::find(45);

// Todos los roles (granted = true o false)
$allRoles = $permission->roles;

// Solo roles con granted = true
$grantedRoles = $permission->roles()
    ->wherePivot('granted', true)
    ->get();

foreach ($grantedRoles as $role) {
    echo "Rol: {$role->nombre}\n";
}

// Con información del pivot
$roles = $permission->roles()->get();
foreach ($roles as $role) {
    echo "Rol: {$role->nombre}, Granted: {$role->pivot->granted}\n";
}
```

### 3. Obtener Usuarios que tienen el Permiso

```php
$permission = Permission::find(45);

// Obtener usuarios (a través de sus roles)
$users = $permission->users()->get();

foreach ($users as $user) {
    echo "Usuario: {$user->name} ({$user->email})\n";
}

// Contar usuarios con el permiso
$userCount = $permission->users()->count();
echo "Total de usuarios con este permiso: {$userCount}";
```

### 4. Obtener Vista Asociada

```php
$permission = Permission::with('vista')->find(45);

if ($permission->vista) {
    echo "Vista: {$permission->vista->descripcion}\n";
    echo "Ruta: {$permission->vista->ruta}\n";
    echo "Icono: {$permission->vista->icono}\n";
}
```

---

## Scopes (Filtros)

### 1. Permisos Activos

```php
// Solo permisos activos
$activePermissions = Permission::active()->get();

// Ordenados por nombre
$permissions = Permission::active()
    ->orderBy('name')
    ->get();
```

### 2. Filtrar por Módulo

```php
// Todos los permisos de un módulo
$permissions = Permission::byModule('vehicle_purchase_order')->get();

// Activos de un módulo
$permissions = Permission::active()
    ->byModule('vehicle_purchase_order')
    ->get();
```

### 3. Filtrar por Tipo

```php
// Permisos básicos (CRUD)
$basicPermissions = Permission::byType('basic')->get();

// Permisos especiales
$specialPermissions = Permission::byType('special')->get();

// Permisos custom
$customPermissions = Permission::byType('custom')->get();
```

### 4. Combinación de Scopes

```php
// Permisos activos, básicos, de un módulo específico
$permissions = Permission::active()
    ->byModule('vehicle_purchase_order')
    ->byType('basic')
    ->orderBy('name')
    ->get();

// Solo permisos de creación activos
$createPermissions = Permission::active()
    ->where('code', 'like', '%.create')
    ->get();
```

---

## Métodos Avanzados

### 1. Clonar un Permiso

```php
$originalPermission = Permission::find(45);

// Clonar con nuevo código
$newPermission = $originalPermission->cloneWithCode(
    'new_module.create',
    'Crear Nuevo Módulo'
);

// Clonar manteniendo el mismo nombre
$clone = $originalPermission->cloneWithCode('another_module.create');
```

**Caso de Uso Real:**
```php
// Crear permisos de un módulo basándose en otro existente
$sourceModule = 'vehicle_purchase_order';
$targetModule = 'vehicle_sale_order';

$sourcePermissions = Permission::byModule($sourceModule)->get();

foreach ($sourcePermissions as $permission) {
    // Extraer la acción (create, edit, etc.)
    $action = $permission->action;

    // Crear el nuevo código
    $newCode = "{$targetModule}.{$action}";

    // Clonar
    $permission->cloneWithCode(
        $newCode,
        str_replace('Compra', 'Venta', $permission->name)
    );
}
```

### 2. Obtener el Nombre de la Acción

```php
$permission = Permission::where('code', 'vehicle_purchase_order.create')->first();

// Obtiene 'create' del código
echo $permission->action; // Salida: 'create'

// Útil para agrupar permisos por acción
$permissions = Permission::active()->get();
$groupedByAction = $permissions->groupBy('action');

// Resultado:
// 'create' => [permisos de crear],
// 'edit' => [permisos de editar],
// etc.
```

### 3. Verificar si es un Permiso CRUD

```php
$permission = Permission::find(45);

if ($permission->isCrudPermission()) {
    echo "Es un permiso CRUD básico";
} else {
    echo "Es un permiso especial o custom";
}

// Filtrar solo permisos CRUD
$allPermissions = Permission::all();
$crudPermissions = $allPermissions->filter(function ($permission) {
    return $permission->isCrudPermission();
});
```

---

## Generación Automática

### 1. Generar Permisos CRUD para un Módulo

```php
use App\Models\gp\gestionsistema\Permission;

// Generar permisos básicos (view, create, edit, delete)
$permissions = Permission::generateCrudPermissions(
    'vehicle_movement',
    25, // vista_id (opcional)
    'Movimientos de Vehículos' // nombre del módulo (opcional)
);

// Resultado:
// - vehicle_movement.view - Ver Movimientos de Vehículos
// - vehicle_movement.create - Crear Movimientos de Vehículos
// - vehicle_movement.edit - Editar Movimientos de Vehículos
// - vehicle_movement.delete - Eliminar Movimientos de Vehículos

// Sin vista_id ni nombre
$permissions = Permission::generateCrudPermissions('opportunity');
// Genera: opportunity.view, opportunity.create, etc.
```

### 2. Comando Artisan Personalizado

```php
// app/Console/Commands/GenerateModulePermissions.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\gp\gestionsistema\Permission;

class GenerateModulePermissions extends Command
{
    protected $signature = 'permission:generate
                            {module : El código del módulo}
                            {--vista-id= : ID de la vista}
                            {--name= : Nombre del módulo}
                            {--with-special : Incluir permisos especiales}';

    protected $description = 'Generar permisos para un módulo';

    public function handle()
    {
        $module = $this->argument('module');
        $vistaId = $this->option('vista-id');
        $moduleName = $this->option('name');

        // Generar permisos CRUD
        $permissions = Permission::generateCrudPermissions(
            $module,
            $vistaId,
            $moduleName
        );

        $this->info("Permisos CRUD generados:");
        foreach ($permissions as $permission) {
            $this->line("  ✓ {$permission->code}");
        }

        // Permisos especiales opcionales
        if ($this->option('with-special')) {
            $specialPermissions = [
                ['action' => 'export', 'name' => 'Exportar', 'type' => 'special'],
                ['action' => 'import', 'name' => 'Importar', 'type' => 'special'],
                ['action' => 'approve', 'name' => 'Aprobar', 'type' => 'special'],
            ];

            foreach ($specialPermissions as $perm) {
                $created = Permission::create([
                    'code' => "{$module}.{$perm['action']}",
                    'name' => "{$perm['name']} " . ($moduleName ?? ucwords(str_replace('_', ' ', $module))),
                    'module' => $module,
                    'vista_id' => $vistaId,
                    'type' => $perm['type'],
                    'is_active' => true,
                ]);
                $this->line("  ✓ {$created->code}");
            }
        }

        $this->info("\n✨ Permisos generados exitosamente!");
    }
}
```

**Uso:**
```bash
# Solo CRUD
php artisan permission:generate vehicle_movement --vista-id=25 --name="Movimientos de Vehículos"

# CRUD + Especiales
php artisan permission:generate opportunity --vista-id=20 --with-special
```

---

## Import/Export

### 1. Exportar Permisos

```php
// Exportar todos los permisos de un módulo
$permissions = Permission::byModule('vehicle_purchase_order')->get();
$exportData = $permissions->map(function ($permission) {
    return $permission->toExportArray();
})->toArray();

// Guardar en JSON
file_put_contents(
    'permisos_vehicle_purchase_order.json',
    json_encode($exportData, JSON_PRETTY_PRINT)
);

// O devolver como respuesta API
return response()->json(['permissions' => $exportData]);
```

**Resultado JSON:**
```json
[
  {
    "code": "vehicle_purchase_order.create",
    "name": "Crear Orden de Compra",
    "description": "Permite crear órdenes de compra",
    "module": "vehicle_purchase_order",
    "type": "basic",
    "is_active": true
  }
]
```

### 2. Importar Permisos

```php
// Desde archivo JSON
$jsonData = file_get_contents('permisos_vehicle_purchase_order.json');
$permissions = json_decode($jsonData, true);

$count = Permission::importFromArray($permissions);
echo "Importados {$count} permisos";

// Desde array directo
$permissions = [
    [
        'code' => 'new_module.view',
        'name' => 'Ver Nuevo Módulo',
        'module' => 'new_module',
        'type' => 'basic',
        'is_active' => true,
    ],
    [
        'code' => 'new_module.create',
        'name' => 'Crear Nuevo Módulo',
        'module' => 'new_module',
        'type' => 'basic',
        'is_active' => true,
    ],
];

Permission::importFromArray($permissions);
```

### 3. Endpoint API para Import/Export

```php
// En PermissionController.php

/**
 * Exportar permisos de un módulo
 */
public function export(Request $request)
{
    $module = $request->input('module');

    $permissions = Permission::byModule($module)->get();
    $exportData = $permissions->map(fn($p) => $p->toExportArray());

    return response()->json([
        'success' => true,
        'data' => $exportData,
    ]);
}

/**
 * Importar permisos desde JSON
 */
public function import(Request $request)
{
    $request->validate([
        'permissions' => 'required|array',
        'permissions.*.code' => 'required|string',
        'permissions.*.name' => 'required|string',
        'permissions.*.module' => 'required|string',
        'permissions.*.type' => 'required|in:basic,special,custom',
    ]);

    $count = Permission::importFromArray($request->permissions);

    return response()->json([
        'success' => true,
        'message' => "{$count} permisos importados exitosamente",
    ]);
}
```

---

## Casos de Uso Reales

### 1. Auditoría: Listar Usuarios con un Permiso Específico

```php
$permissionCode = 'vehicle_purchase_order.delete';
$permission = Permission::where('code', $permissionCode)->first();

if ($permission) {
    $users = $permission->users()->get();

    echo "Usuarios con permiso '{$permissionCode}':\n";
    foreach ($users as $user) {
        echo "- {$user->name} ({$user->email}) - Rol: {$user->role->nombre}\n";
    }
}
```

### 2. Sincronizar Permisos de Desarrollo a Producción

```php
// En desarrollo: Exportar
$permissions = Permission::all();
$exportData = $permissions->map(fn($p) => $p->toExportArray())->toArray();
file_put_contents('permissions_backup.json', json_encode($exportData, JSON_PRETTY_PRINT));

// En producción: Importar
$jsonData = file_get_contents('permissions_backup.json');
$permissions = json_decode($jsonData, true);
Permission::importFromArray($permissions);
```

### 3. Copiar Permisos de un Rol a Otro

```php
use App\Models\gp\gestionsistema\Role;
use App\Http\Services\gp\gestionsistema\PermissionService;

$sourceRole = Role::find(2); // Rol origen
$targetRole = Role::find(5); // Rol destino

// Obtener permisos del rol origen
$permissions = $sourceRole->permissions()
    ->wherePivot('granted', true)
    ->pluck('id')
    ->toArray();

// Asignarlos al rol destino
$service = app(PermissionService::class);
$service->syncPermissionsToRole($targetRole->id, $permissions);

echo "Permisos copiados de '{$sourceRole->nombre}' a '{$targetRole->nombre}'";
```

### 4. Dashboard de Permisos

```php
// Estadísticas de permisos
$stats = [
    'total' => Permission::count(),
    'activos' => Permission::active()->count(),
    'inactivos' => Permission::where('is_active', false)->count(),
    'por_tipo' => Permission::selectRaw('type, COUNT(*) as count')
        ->groupBy('type')
        ->pluck('count', 'type'),
    'por_modulo' => Permission::selectRaw('module, COUNT(*) as count')
        ->groupBy('module')
        ->orderByDesc('count')
        ->pluck('count', 'module'),
    'sin_asignar' => Permission::doesntHave('roles')->count(),
];

// Permisos más usados (más roles asignados)
$mostUsed = Permission::withCount('roles')
    ->orderByDesc('roles_count')
    ->take(10)
    ->get();
```

### 5. Validación de Integridad

```php
// Verificar permisos sin módulo asociado
$orphanPermissions = Permission::whereNull('vista_id')->get();

// Verificar permisos duplicados por código
$duplicates = Permission::selectRaw('code, COUNT(*) as count')
    ->groupBy('code')
    ->having('count', '>', 1)
    ->get();

// Verificar códigos que no siguen la convención module.action
$invalidCodes = Permission::whereRaw("code NOT LIKE '%.__%'")->get();
```

### 6. Migración de Permisos

```php
// Cambiar todos los permisos de un módulo a otro
$oldModule = 'vehicle_purchase';
$newModule = 'vehicle_purchase_order';

Permission::where('module', $oldModule)
    ->update(['module' => $newModule]);

// Actualizar códigos
Permission::where('code', 'like', "{$oldModule}.%")
    ->get()
    ->each(function ($permission) use ($oldModule, $newModule) {
        $newCode = str_replace($oldModule, $newModule, $permission->code);
        $permission->update(['code' => $newCode]);
    });
```

### 7. Asignación Masiva por Tipo de Usuario

```php
use App\Models\gp\gestionsistema\Role;

// Asignar todos los permisos de visualización a un rol
$viewerRole = Role::where('nombre', 'Viewer')->first();
$viewPermissions = Permission::active()
    ->where('code', 'like', '%.view')
    ->pluck('id')
    ->toArray();

$service = app(PermissionService::class);
$service->syncPermissionsToRole($viewerRole->id, $viewPermissions);

// Asignar CRUD completo a administradores
$adminRole = Role::where('nombre', 'Administrador')->first();
$allPermissions = Permission::active()->pluck('id')->toArray();
$service->syncPermissionsToRole($adminRole->id, $allPermissions);
```

### 8. Reportes de Acceso

```php
// Generar reporte de permisos por rol
$roles = Role::with(['permissions' => function ($query) {
    $query->wherePivot('granted', true);
}])->get();

foreach ($roles as $role) {
    echo "Rol: {$role->nombre}\n";
    echo "Permisos: {$role->permissions->count()}\n";

    $permissionsByModule = $role->permissions->groupBy('module');
    foreach ($permissionsByModule as $module => $permissions) {
        echo "  - {$module}: {$permissions->count()} permisos\n";
    }
    echo "\n";
}
```

---

## Helpers y Utilidades

### Helper Global

```php
// app/Helpers/PermissionHelper.php
if (!function_exists('permission_exists')) {
    function permission_exists(string $code): bool
    {
        return \App\Models\gp\gestionsistema\Permission::where('code', $code)
            ->where('is_active', true)
            ->exists();
    }
}

if (!function_exists('get_module_permissions')) {
    function get_module_permissions(string $module): Collection
    {
        return \App\Models\gp\gestionsistema\Permission::active()
            ->byModule($module)
            ->get();
    }
}

// Uso:
if (permission_exists('vehicle_purchase_order.create')) {
    // El permiso existe
}

$permissions = get_module_permissions('opportunity');
```

### Trait para Modelos con Permisos

```php
// app/Traits/HasModulePermissions.php
namespace App\Traits;

use App\Models\gp\gestionsistema\Permission;

trait HasModulePermissions
{
    /**
     * Obtener nombre del módulo desde el modelo
     */
    abstract public function getModuleName(): string;

    /**
     * Generar permisos para este modelo
     */
    public static function generatePermissions(int $vistaId = null): array
    {
        $instance = new static();
        $module = $instance->getModuleName();

        return Permission::generateCrudPermissions($module, $vistaId);
    }
}

// Uso en modelo:
class VehiclePurchaseOrder extends Model
{
    use HasModulePermissions;

    public function getModuleName(): string
    {
        return 'vehicle_purchase_order';
    }
}

// Generar permisos:
VehiclePurchaseOrder::generatePermissions(15);
```

---

## Testing

### Tests de Ejemplo

```php
// tests/Unit/PermissionTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\Role;

class PermissionTest extends TestCase
{
    /** @test */
    public function can_create_permission()
    {
        $permission = Permission::create([
            'code' => 'test.create',
            'name' => 'Test Create',
            'module' => 'test',
            'type' => 'basic',
        ]);

        $this->assertDatabaseHas('permission', [
            'code' => 'test.create',
        ]);
    }

    /** @test */
    public function can_generate_crud_permissions()
    {
        $permissions = Permission::generateCrudPermissions('test_module');

        $this->assertCount(4, $permissions);
        $this->assertDatabaseHas('permission', ['code' => 'test_module.view']);
        $this->assertDatabaseHas('permission', ['code' => 'test_module.create']);
    }

    /** @test */
    public function can_clone_permission()
    {
        $original = Permission::factory()->create([
            'code' => 'original.create',
        ]);

        $clone = $original->cloneWithCode('cloned.create', 'Cloned Permission');

        $this->assertNotEquals($original->id, $clone->id);
        $this->assertEquals('cloned.create', $clone->code);
    }

    /** @test */
    public function can_check_if_granted_to_role()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        $role->permissions()->attach($permission->id, ['granted' => true]);

        $this->assertTrue($permission->isGrantedToRole($role->id));
    }
}
```

---

## Mejores Prácticas

1. **Usar `updateOrCreate`** para evitar duplicados en seeders
2. **Nombrar consistentemente**: `module.action`
3. **Invalidar caché** después de modificar permisos
4. **Documentar permisos custom** específicos de cada módulo
5. **Generar CRUD automáticamente** con `generateCrudPermissions()`
6. **Exportar regularmente** los permisos como backup
7. **Validar integridad** periódicamente (códigos, vínculos)

---

**Última actualización**: 24 de octubre de 2025