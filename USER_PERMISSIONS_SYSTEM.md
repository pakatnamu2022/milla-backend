# Sistema de Permisos de Usuario

## Descripción General

Sistema completo de gestión de permisos de usuario basado en roles para el backend de Milla. Este sistema permite consultar, verificar y gestionar permisos de usuario de forma granular, con soporte para agrupación por módulos y caché para optimización de rendimiento.

## Arquitectura

El sistema sigue el patrón MVC con capa de servicio:

- **Controlador**: `UserPermissionController` - Maneja las peticiones HTTP y respuestas JSON
- **Servicio**: `UserPermissionService` - Contiene la lógica de negocio y consultas complejas
- **Modelo**: `Permission` - Representa los permisos en la base de datos

### Archivos Principales

```
app/Http/Controllers/gp/gestionsistema/UserPermissionController.php
app/Http/Services/gp/gestionsistema/UserPermissionService.php
app/Models/gp/gestionsistema/Permission.php
```

## Características Principales

### 1. Consulta de Permisos de Usuario

- Obtener todos los permisos del usuario autenticado
- Obtener permisos de un usuario específico (por ID)
- Permisos agrupados por módulos/vistas
- Incluye información de roles y acciones disponibles

### 2. Permisos por Módulo

- Filtrar permisos por código de módulo
- Obtener acciones específicas de cada módulo
- Información completa de la vista asociada

### 3. Verificación de Permisos

- Verificar si un usuario tiene un permiso específico
- Validación en tiempo real
- Soporte para verificaciones rápidas

### 4. Gestión de Módulos

- Listar todos los módulos disponibles en el sistema
- Información de rutas, iconos y jerarquía

### 5. Sistema de Caché

- Caché de permisos por usuario (1 hora)
- Endpoint para limpiar caché manualmente
- Optimización de consultas repetitivas

## Endpoints API

Todos los endpoints requieren autenticación mediante Sanctum.

### 1. Obtener Permisos del Usuario Autenticado

```http
GET /api/users/permissions
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Permisos obtenidos exitosamente",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "role": {
        "id": 2,
        "name": "Administrador",
        "description": "Rol con permisos completos"
      }
    },
    "modules": [
      {
        "id": 15,
        "code": "vehicle_purchase_order",
        "name": "Órdenes de Compra de Vehículos",
        "description": "Gestión de compras",
        "icon": "shopping-cart",
        "route": "/compras/ordenes",
        "parent_id": 10,
        "actions": [
          {
            "id": 45,
            "action_code": "create",
            "permission_code": "vehicle_purchase_order.create",
            "name": "Crear Orden de Compra",
            "type": "action"
          },
          {
            "id": 46,
            "action_code": "edit",
            "permission_code": "vehicle_purchase_order.edit",
            "name": "Editar Orden de Compra",
            "type": "action"
          }
        ]
      }
    ],
    "permissions_by_module": {
      "vehicle_purchase_order": [
        "vehicle_purchase_order.create",
        "vehicle_purchase_order.edit",
        "vehicle_purchase_order.view"
      ]
    },
    "permissions": {
      "vehicle_purchase_order.create": true,
      "vehicle_purchase_order.edit": true,
      "vehicle_purchase_order.view": true
    }
  }
}
```

### 2. Obtener Permisos de un Usuario Específico

```http
GET /api/users/{userId}/permissions
```

**Parámetros:**
- `userId` (int, path) - ID del usuario

**Nota**: Este endpoint típicamente requiere permisos de administrador.

### 3. Obtener Permisos de un Módulo

```http
GET /api/users/permissions/module/{moduleCode}
```

**Parámetros:**
- `moduleCode` (string, path) - Código del módulo (ej: `vehicle_purchase_order`)

**Respuesta:**
```json
{
  "success": true,
  "message": "Permisos del módulo obtenidos exitosamente",
  "data": {
    "module": {
      "id": 15,
      "code": "vehicle_purchase_order",
      "name": "Órdenes de Compra de Vehículos",
      "icon": "shopping-cart",
      "route": "/compras/ordenes"
    },
    "actions": [
      {
        "id": 45,
        "action_code": "create",
        "permission_code": "vehicle_purchase_order.create",
        "name": "Crear Orden de Compra",
        "type": "action"
      }
    ],
    "permissions": [
      "vehicle_purchase_order.create",
      "vehicle_purchase_order.edit"
    ]
  }
}
```

### 4. Verificar Permiso Específico

```http
POST /api/users/permissions/check
Content-Type: application/json

{
  "permission_code": "vehicle_purchase_order.create"
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "permission_code": "vehicle_purchase_order.create",
    "has_permission": true
  }
}
```

### 5. Obtener Todos los Módulos

```http
GET /api/modules
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Módulos obtenidos exitosamente",
  "data": [
    {
      "id": 15,
      "code": "vehicle_purchase_order",
      "name": "Órdenes de Compra de Vehículos",
      "description": "Gestión de compras",
      "icon": "shopping-cart",
      "route": "/compras/ordenes",
      "parent_id": 10
    }
  ]
}
```

### 6. Limpiar Caché de Permisos

```http
POST /api/users/permissions/clear-cache
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Caché de permisos limpiado exitosamente"
}
```

## Modelo de Datos

### Tabla: `permission`

Campos principales:
- `id` - ID único del permiso
- `code` - Código único del permiso (ej: `vehicle_purchase_order.create`)
- `name` - Nombre descriptivo
- `description` - Descripción detallada
- `module` - Código del módulo al que pertenece
- `vista_id` - ID de la vista asociada (relación con `config_vista`)
- `policy_method` - Método de policy asociado
- `type` - Tipo de permiso (action, view, etc.)
- `is_active` - Estado activo/inactivo

### Relaciones

#### Permission -> Vista (View)
```php
$permission->vista // Obtiene la vista asociada
```

#### Permission -> Roles
```php
$permission->roles // Obtiene roles que tienen este permiso
$permission->isGrantedToRole($roleId) // Verifica si está asignado a un rol
```

## Convenciones de Nomenclatura

### Códigos de Permiso

Formato: `{modulo}.{accion}`

Ejemplos:
- `vehicle_purchase_order.create` - Crear orden de compra
- `vehicle_purchase_order.edit` - Editar orden de compra
- `vehicle_purchase_order.view` - Ver orden de compra
- `vehicle_purchase_order.delete` - Eliminar orden de compra
- `vehicle_purchase_order.approve` - Aprobar orden de compra

### Tipos de Permiso

- `action` - Acción específica (crear, editar, etc.)
- `view` - Acceso a visualización de módulo
- `custom` - Permisos personalizados

## Sistema de Caché

### Estrategia de Caché

El servicio implementa caché automático para optimizar el rendimiento:

```php
// Los permisos se cachean por 1 hora (3600 segundos)
Cache::remember("user_permissions_{$userId}", 3600, function () {
    // Consultas complejas...
});
```

### Invalidación de Caché

#### Manualmente (Usuario Individual)
```php
$service->clearUserPermissionsCache($userId);
```

#### Manualmente (Todos los usuarios de un rol)
```php
$service->clearRolePermissionsCache($roleId);
```

#### A través de API
```http
POST /api/users/permissions/clear-cache
```

### Cuándo Invalidar el Caché

El caché debe ser invalidado cuando:
- Se modifican los permisos de un usuario
- Se modifican los permisos de un rol
- Se cambia el rol de un usuario
- Se activa/desactiva un permiso

## Uso en el Frontend

### Obtener Permisos al Iniciar Sesión

```javascript
// Al autenticarse el usuario
const response = await axios.get('/api/users/permissions');
const { user, modules, permissions } = response.data.data;

// Guardar en store de estado (Vuex, Pinia, Redux, etc.)
store.commit('setUserPermissions', permissions);
store.commit('setUserModules', modules);
```

### Verificar Permisos en Componentes

```javascript
// Verificación local (rápida)
if (permissions['vehicle_purchase_order.create']) {
  // Mostrar botón de crear
}

// Verificación en servidor (definitiva)
const response = await axios.post('/api/users/permissions/check', {
  permission_code: 'vehicle_purchase_order.create'
});

if (response.data.data.has_permission) {
  // Permitir acción
}
```

### Filtrar Menú por Permisos

```javascript
const filteredMenu = modules.map(module => ({
  ...module,
  visible: module.actions.length > 0, // Solo mostrar si tiene acciones
  actions: module.actions.filter(action =>
    permissions[action.permission_code]
  )
}));
```

## Integración con Middleware

### Verificación de Permisos en Rutas

```php
// En routes/api.php
Route::middleware(['auth:sanctum', 'permission:vehicle_purchase_order.create'])
  ->post('/vehicle-purchase-orders', [VehiclePurchaseOrderController::class, 'store']);
```

### Middleware Personalizado

```php
// app/Http/Middleware/CheckPermission.php
public function handle($request, Closure $next, $permission)
{
    if (!auth()->user()->hasPermission($permission)) {
        return response()->json([
            'success' => false,
            'message' => 'No tiene permisos para realizar esta acción'
        ], 403);
    }

    return $next($request);
}
```

## Servicios Disponibles

### UserPermissionService

#### Métodos Públicos

```php
// Obtener permisos del usuario
public function getUserPermissions(?int $userId = null): array

// Obtener permisos de un módulo
public function getModulePermissions(string $moduleCode, ?int $userId = null): array

// Verificar permiso
public function hasPermission(string $permissionCode, ?int $userId = null): bool

// Limpiar caché de usuario
public function clearUserPermissionsCache(int $userId): void

// Limpiar caché de rol
public function clearRolePermissionsCache(int $roleId): void

// Obtener todos los módulos
public function getAllModules(): Collection
```

### Uso del Servicio en Código

```php
use App\Http\Services\gp\gestionsistema\UserPermissionService;

class SomeController extends Controller
{
    protected UserPermissionService $permissionService;

    public function __construct(UserPermissionService $service)
    {
        $this->permissionService = $service;
    }

    public function someMethod()
    {
        // Verificar permiso
        if ($this->permissionService->hasPermission('vehicle_purchase_order.create')) {
            // Realizar acción
        }

        // Obtener permisos de módulo
        $modulePermissions = $this->permissionService->getModulePermissions('vehicle_purchase_order');
    }
}
```

## Modelo de Permisos en User

### Métodos Requeridos en el Modelo User

El modelo `User` debe implementar los siguientes métodos:

```php
// Obtener todos los permisos del usuario (directos + de roles)
public function getAllPermissions(): Collection

// Verificar si tiene un permiso específico
public function hasPermission(string $permissionCode): bool
```

## Estructura de Base de Datos

### Tablas Relacionadas

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│     users       │         │  config_asig_   │         │     roles       │
│                 │────────>│   role_user     │<────────│                 │
│  - id           │         │  - user_id      │         │  - id           │
│  - name         │         │  - role_id      │         │  - nombre       │
│  - email        │         │  - status       │         │  - descripcion  │
└─────────────────┘         └─────────────────┘         └─────────────────┘
                                                                  │
                                                                  │
                                                                  v
                            ┌─────────────────┐         ┌─────────────────┐
                            │     role_       │         │   permission    │
                            │   permission    │<────────│                 │
                            │  - role_id      │         │  - id           │
                            │  - permission_id│         │  - code         │
                            │  - granted      │         │  - name         │
                            └─────────────────┘         │  - module       │
                                                        │  - vista_id     │
                                                        │  - is_active    │
                                                        └─────────────────┘
                                                                  │
                                                                  │
                                                                  v
                                                        ┌─────────────────┐
                                                        │  config_vista   │
                                                        │                 │
                                                        │  - id           │
                                                        │  - descripcion  │
                                                        │  - slug         │
                                                        │  - ruta         │
                                                        │  - icono        │
                                                        └─────────────────┘
```

## Migraciones

### Migración: Agregar vista_id a permission

Archivo: `database/migrations/2025_10_24_163329_add_vista_id_to_permission_table.php`

```php
public function up()
{
    Schema::table('permission', function (Blueprint $table) {
        $table->unsignedBigInteger('vista_id')->nullable()->after('module');
        $table->foreign('vista_id')->references('id')->on('config_vista')->onDelete('set null');
    });
}
```

## Testing

### Ejemplos de Tests

```php
// tests/Feature/UserPermissionTest.php

public function test_user_can_get_own_permissions()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/users/permissions');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'modules',
                'permissions_by_module',
                'permissions'
            ]
        ]);
}

public function test_user_can_check_specific_permission()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/users/permissions/check', [
            'permission_code' => 'vehicle_purchase_order.create'
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'permission_code' => 'vehicle_purchase_order.create',
                'has_permission' => true
            ]
        ]);
}
```

## Documentación Swagger/OpenAPI

Todos los endpoints están documentados con anotaciones OpenAPI en el controlador. Para generar la documentación:

```bash
php artisan l5-swagger:generate
```

Acceder a la documentación en: `/api/documentation`

## Consideraciones de Seguridad

1. **Autenticación Requerida**: Todos los endpoints requieren autenticación mediante Sanctum
2. **Validación de Permisos**: Validar permisos en backend, no solo en frontend
3. **Caché Seguro**: El caché usa el ID de usuario para evitar fugas de información
4. **Soft Deletes**: Los permisos usan soft deletes para mantener historial
5. **Solo Permisos Activos**: Solo se devuelven permisos con `is_active = true`

## Mejores Prácticas

1. **Usar Caché Inteligentemente**: El caché mejora el rendimiento, pero debe invalidarse correctamente
2. **Granularidad Adecuada**: Crear permisos específicos pero no excesivamente granulares
3. **Nomenclatura Consistente**: Seguir el patrón `modulo.accion`
4. **Verificación en Backend**: Siempre verificar permisos en el servidor, no solo en el cliente
5. **Documentación de Permisos**: Mantener documentados todos los permisos del sistema

## Troubleshooting

### Problema: Permisos no se actualizan

**Solución**: Limpiar el caché de permisos
```http
POST /api/users/permissions/clear-cache
```

### Problema: Usuario no ve sus permisos

**Causas posibles**:
1. Usuario no tiene rol asignado
2. Rol no tiene permisos asignados
3. Permisos están inactivos (`is_active = false`)
4. Vista asociada está eliminada (`status_deleted = 0`)

### Problema: Caché desactualizado

**Solución**: Implementar listeners para invalidar caché automáticamente cuando cambien permisos o roles.

## Roadmap / Mejoras Futuras

- [ ] Implementar listeners para invalidación automática de caché
- [ ] Agregar historial de cambios de permisos
- [ ] Implementar permisos temporales con expiración
- [ ] Agregar permisos a nivel de datos (row-level permissions)
- [ ] Dashboard de auditoría de permisos
- [ ] Exportación de matriz de permisos (usuarios x permisos)

## Referencias

- Controlador: `app/Http/Controllers/gp/gestionsistema/UserPermissionController.php:10`
- Servicio: `app/Http/Services/gp/gestionsistema/UserPermissionService.php:12`
- Modelo: `app/Models/gp/gestionsistema/Permission.php:10`
- Rutas API: `routes/api.php:162-167`
- Migración: `database/migrations/2025_10_24_163329_add_vista_id_to_permission_table.php`

---

**Última actualización**: 24 de octubre de 2025
**Versión**: 1.0
**Autor**: Sistema de Gestión Milla