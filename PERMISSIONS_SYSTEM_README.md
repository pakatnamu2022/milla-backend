# Sistema de Permisos Granulares

## Descripción

Sistema completo de permisos granulares para Laravel que complementa el sistema de permisos básicos (CRUD) existente con permisos específicos por módulo.

## Arquitectura

### Estructura de Tablas

```
User
  → UserRole (pivot)
    → Role
      → Access (permisos básicos CRUD por vista)
      → RolePermission (pivot)
        → Permission (permisos granulares)
```

### Tablas Principales

#### `permission`
Almacena todos los permisos granulares del sistema:
- `code`: Código único (ej: `vehicle_purchase_order.export`)
- `name`: Nombre descriptivo
- `module`: Módulo al que pertenece
- `policy_method`: Método en la Policy correspondiente
- `type`: `basic` | `special` | `custom`

#### `role_permission` (pivot)
Relación entre roles y permisos:
- `role_id`: FK a `config_roles`
- `permission_id`: FK a `permission`
- `granted`: boolean (permite denegar permisos)

#### `config_asigxvistaxrole` (Access)
Permisos básicos CRUD (existente):
- `crear`, `ver`, `editar`, `anular`

---

## Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las tablas:
- `permission`
- `role_permission`

### 2. Ejecutar Seeder

```bash
php artisan db:seed --class=PermissionSeeder
```

Esto poblará la tabla `permission` con permisos iniciales organizados por módulo.

### 3. Registrar Middleware

En `bootstrap/app.php` o `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... otros middlewares
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

---

## Uso

### 1. En Policies

```php
use App\Policies\BasePolicy;

class VehiclePurchaseOrderPolicy extends BasePolicy
{
    protected string $module = 'vehicle_purchase_order';

    // Permisos CRUD básicos (heredados de BasePolicy)
    // viewAny(), view(), create(), update(), delete()

    // Permisos granulares personalizados
    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'export');
    }

    public function resend(User $user, VehiclePurchaseOrder $order): bool
    {
        // Combinar permiso + lógica de negocio
        return $this->hasPermission($user, 'resend')
            && $order->status === 'anulado';
    }
}
```

### 2. En Controllers

```php
class VehiclePurchaseOrderController extends Controller
{
    public function export(Request $request)
    {
        // Opción 1: Usar Policy
        $this->authorize('export', VehiclePurchaseOrder::class);

        return $this->service->export($request);
    }

    public function resend($id)
    {
        $order = VehiclePurchaseOrder::findOrFail($id);

        // Opción 2: Usar Policy con modelo
        $this->authorize('resend', $order);

        return $this->service->resend($order);
    }
}
```

### 3. En Rutas (Middleware)

```php
use App\Http\Middleware\CheckPermission;

// Opción 1: Middleware directo
Route::middleware(['auth:sanctum', 'permission:vehicle_purchase_order.export'])
    ->get('/vehicle-purchase-orders/export', [VehiclePurchaseOrderController::class, 'export']);

// Opción 2: En grupo
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('vehicle-purchase-orders')->group(function () {
        Route::get('/', [VehiclePurchaseOrderController::class, 'index']);
        Route::middleware('permission:vehicle_purchase_order.export')
            ->get('/export', [VehiclePurchaseOrderController::class, 'export']);
    });
});
```

### 4. En Blade/Frontend

```blade
{{-- Verificar permiso granular --}}
@permission('vehicle_purchase_order.export')
    <button>Exportar</button>
@endpermission

{{-- Verificar cualquiera de varios permisos --}}
@anyPermission(['vehicle_purchase_order.export', 'vehicle_purchase_order.import'])
    <div>Opciones de importación/exportación</div>
@endanyPermission

{{-- Verificar todos los permisos --}}
@allPermissions(['vehicle_purchase_order.approve', 'vehicle_purchase_order.reject'])
    <div>Panel de aprobación</div>
@endallPermissions

{{-- Verificar permisos básicos CRUD --}}
@canView('vehicle_purchase_order')
    <a href="/vehicle-purchase-orders">Ver órdenes</a>
@endcanView

@canCreate('vehicle_purchase_order')
    <button>Nueva orden</button>
@endcanCreate

@canEdit('vehicle_purchase_order')
    <button>Editar</button>
@endcanEdit

@canDelete('vehicle_purchase_order')
    <button>Anular</button>
@endcanDelete
```

### 5. En Código PHP

```php
// Verificar permiso granular
if (auth()->user()->hasPermission('vehicle_purchase_order.export')) {
    // ...
}

// Verificar permiso básico
if (auth()->user()->hasAccessToView('vehicle_purchase_order', 'crear')) {
    // ...
}

// Verificar cualquiera de varios permisos
if (auth()->user()->hasAnyPermission([
    'vehicle_purchase_order.export',
    'vehicle_purchase_order.import'
])) {
    // ...
}

// Verificar todos los permisos
if (auth()->user()->hasAllPermissions([
    'vehicle_purchase_order.approve',
    'vehicle_purchase_order.reject'
])) {
    // ...
}

// Obtener todos los permisos del usuario
$permissions = auth()->user()->getAllPermissions();

// Obtener permisos de un módulo específico
$permissions = auth()->user()->getPermissionsByModule('vehicle_purchase_order');
```

---

## Gestión de Permisos

### Asignar Permiso a Rol

```php
use App\Http\Services\gp\gestionsistema\PermissionService;

$service = app(PermissionService::class);

// Asignar un solo permiso
$service->assignPermissionToRole(
    roleId: 1,
    permissionId: 5,
    granted: true
);

// Asignar múltiples permisos
$service->assignMultiplePermissionsToRole(
    roleId: 1,
    permissions: [1, 2, 3, 4, 5]
);

// Sincronizar permisos (reemplaza todos)
$service->syncPermissionsToRole(
    roleId: 1,
    permissionIds: [1, 2, 3]
);

// Remover permiso
$service->removePermissionFromRole(
    roleId: 1,
    permissionId: 5
);
```

### API Endpoints

```bash
# Listar permisos
GET /api/permissions

# Crear permiso
POST /api/permissions
{
  "code": "vehicle_purchase_order.custom_action",
  "name": "Acción Personalizada",
  "description": "Descripción del permiso",
  "module": "vehicle_purchase_order",
  "policy_method": "customAction",
  "type": "custom"
}

# Actualizar permiso
PUT /api/permissions/{id}

# Obtener permisos agrupados por módulo
GET /api/permissions/grouped-by-module

# Obtener permisos de un módulo
GET /api/permissions/by-module?module=vehicle_purchase_order

# Asignar permiso a rol
POST /api/permissions/assign-to-role
{
  "role_id": 1,
  "permission_id": 5,
  "granted": true
}

# Sincronizar permisos de un rol
POST /api/permissions/sync-to-role
{
  "role_id": 1,
  "permissions": [1, 2, 3, 4, 5]
}

# Obtener permisos de un rol
GET /api/permissions/by-role?role_id=1

# Activar/desactivar permiso
PATCH /api/permissions/{id}/toggle-active
```

---

## Convenciones

### Nomenclatura de Permisos

Formato: `{module}.{action}`

Ejemplos:
- `vehicle_purchase_order.export`
- `vehicle_purchase_order.resend`
- `opportunity.assign`
- `user.reset_password`

### Tipos de Permisos

- **basic**: Permisos CRUD estándar (ya cubiertos por `Access`)
- **special**: Permisos comunes (export, import, approve, reject, publish)
- **custom**: Permisos específicos del negocio (resend, transfer, assign)

### Estructura de Policy

Todas las Policies deben extender `BasePolicy` y definir:

```php
class MyModulePolicy extends BasePolicy
{
    protected string $module = 'my_module'; // REQUERIDO

    // Métodos CRUD heredados automáticamente:
    // - viewAny(), view(), create(), update(), delete()

    // Agregar métodos para permisos granulares:
    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'export');
    }
}
```

---

## Helpers Disponibles

### En User Model (vía ChecksPermissions Trait)

```php
$user->hasPermission(string $permissionCode): bool
$user->hasAccessToView(string $vistaSlug, string $action): bool
$user->hasAnyPermission(array $permissions): bool
$user->hasAllPermissions(array $permissions): bool
$user->getAllPermissions(): Collection
$user->getPermissionsByModule(string $module): Collection
$user->canAccessView(string $vistaSlug, string $action): bool
```

### En Policies (vía BasePolicy)

```php
$this->hasPermission(User $user, string $action): bool
$this->hasAnyPermission(User $user, array $actions): bool
$this->hasAllPermissions(User $user, array $actions): bool
$this->hasBasicAndGranularPermission(User $user, string $basicAction, string $granularAction): bool
```

---

## Ejemplos Completos

### Ejemplo 1: Crear Policy Personalizada

```php
<?php

namespace App\Policies\ap\comercial;

use App\Models\ap\comercial\Opportunity;
use App\Models\User;
use App\Policies\BasePolicy;

class OpportunityPolicy extends BasePolicy
{
    protected string $module = 'opportunity';

    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'export');
    }

    public function assign(User $user, Opportunity $opportunity): bool
    {
        // Solo puede asignar si tiene el permiso Y no es su propia oportunidad
        return $this->hasPermission($user, 'assign')
            && $opportunity->assigned_user_id !== $user->id;
    }

    public function transfer(User $user, Opportunity $opportunity): bool
    {
        // Requiere permiso + debe ser gerente
        return $this->hasPermission($user, 'transfer')
            && $user->role->nombre === 'Gerente';
    }
}
```

### Ejemplo 2: Controller con Múltiples Permisos

```php
<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Models\ap\comercial\Opportunity;

class OpportunityController extends Controller
{
    public function export(Request $request)
    {
        $this->authorize('export', Opportunity::class);
        // ... lógica de exportación
    }

    public function assign(Request $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $this->authorize('assign', $opportunity);

        $opportunity->assigned_user_id = $request->user_id;
        $opportunity->save();

        return $this->success($opportunity);
    }

    public function bulkExport(Request $request)
    {
        // Verificar múltiples permisos
        if (!auth()->user()->hasAllPermissions([
            'opportunity.export',
            'opportunity.view_analytics'
        ])) {
            abort(403, 'Permisos insuficientes');
        }

        // ... lógica
    }
}
```

### Ejemplo 3: Agregar Nuevos Permisos

```php
// En PermissionSeeder o en migración/seeder separado

use App\Models\gp\gestionsistema\Permission;

Permission::create([
    'code' => 'vehicle.register_sale',
    'name' => 'Registrar Venta de Vehículo',
    'description' => 'Permite registrar la venta final de un vehículo',
    'module' => 'vehicle',
    'policy_method' => 'registerSale',
    'type' => 'custom',
    'is_active' => true,
]);

// Luego en VehiclePolicy:
public function registerSale(User $user, Vehicle $vehicle): bool
{
    return $this->hasPermission($user, 'register_sale')
        && $vehicle->status === 'disponible';
}

// En VehicleController:
public function registerSale(Request $request, $id)
{
    $vehicle = Vehicle::findOrFail($id);
    $this->authorize('registerSale', $vehicle);

    // ... lógica
}
```

---

## Troubleshooting

### El middleware no reconoce el permiso

1. Verifica que el middleware esté registrado en `bootstrap/app.php`
2. Verifica que el usuario esté autenticado
3. Verifica que el permiso existe en la tabla `permission`
4. Verifica que el rol del usuario tiene asignado el permiso en `role_permission`

### La Policy no funciona

1. Verifica que la Policy esté registrada en `AuthServiceProvider` (Laravel auto-discover)
2. Verifica que el `$module` esté definido correctamente
3. Verifica que el método existe y retorna boolean
4. Usa `php artisan policy:make` para generar nuevas policies

### Los Blade directives no funcionan

1. Ejecuta `php artisan view:clear`
2. Verifica que `AppServiceProvider` esté registrando los directives en `boot()`
3. Verifica la sintaxis: `@permission('code')` no `@permission($variable)`

---

## Mantenimiento

### Agregar Nuevo Módulo

1. Crear permisos en seeder
2. Crear Policy extendiendo `BasePolicy`
3. Usar en Controller con `$this->authorize()`
4. Asignar permisos a roles desde admin

### Auditoría de Permisos

```php
// Ver todos los permisos de un usuario
$user->getAllPermissions()->groupBy('module');

// Ver qué usuarios tienen un permiso
$permission = Permission::where('code', 'vehicle_purchase_order.export')->first();
$usersWithPermission = User::whereHas('roles.permissions', function($q) use ($permission) {
    $q->where('permission_id', $permission->id);
})->get();
```

---

## Archivos Creados

```
database/migrations/
  └── 2025_10_20_085003_create_permission_table.php
  └── 2025_10_20_085004_create_role_permission_table.php

app/Models/gp/gestionsistema/
  └── Permission.php
  └── RolePermission.php

app/Traits/
  └── ChecksPermissions.php

app/Policies/
  └── BasePolicy.php
  └── ap/comercial/
      └── VehiclePurchaseOrderPolicy.php (ejemplo)

app/Http/Services/gp/gestionsistema/
  └── PermissionService.php

app/Http/Controllers/gp/gestionsistema/
  └── PermissionController.php

app/Http/Requests/gp/gestionsistema/
  └── IndexPermissionRequest.php
  └── StorePermissionRequest.php
  └── UpdatePermissionRequest.php

app/Http/Resources/gp/gestionsistema/
  └── PermissionResource.php

app/Http/Middleware/
  └── CheckPermission.php

app/Providers/
  └── AppServiceProvider.php (modificado)

database/seeders/
  └── PermissionSeeder.php
```

---

## Soporte

Para dudas o problemas, consulta:
- Documentación de Laravel Policies: https://laravel.com/docs/authorization
- Código fuente en: `app/Policies/BasePolicy.php`
- Ejemplos en: `app/Policies/ap/comercial/VehiclePurchaseOrderPolicy.php`
