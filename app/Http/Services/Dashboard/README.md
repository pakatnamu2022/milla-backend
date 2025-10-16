# Dashboard - Arquitectura de Indicadores

## Estructura del Proyecto

```
app/Http/
├── Services/
│   └── Dashboard/
│       ├── comercial/
│       │   └── DashboardComercialService.php
│       └── README.md (este archivo)
├── Controllers/
│   └── Dashboard/
│       └── DashboardComercialController.php
```

## Arquitectura

### Principios de Diseño

1. **Centralización**: Todos los servicios de dashboard están en `app/Http/Services/Dashboard/`
2. **Modularidad**: Cada módulo (comercial, Ventas, etc.) tiene su propio subdirectorio
3. **Separación de Responsabilidades**:
  - **Servicios**: Contienen la lógica de negocio
  - **Controladores**: Manejan las peticiones HTTP y validaciones
  - **Modelos**: Acceso a datos
4. **Escalabilidad**: Fácil agregar nuevos módulos siguiendo la misma estructura

### Dashboard comercial

#### Ubicación de Archivos

- **Servicio**: `app/Http/Services/Dashboard/comercial/DashboardComercialService.php`
- **Controlador**: `app/Http/Controllers/Dashboard/DashboardComercialController.php`
- **Rutas**: `routes/api.php` (dentro del grupo `ap/commercial/dashboard`)

#### Endpoints Disponibles

| Endpoint                                                | Método | Descripción                             |
|---------------------------------------------------------|--------|-----------------------------------------|
| `/ap/commercial/dashboard/indicators/by-date-range`     | GET    | Indicadores totales por rango de fechas |
| `/ap/commercial/dashboard/indicators/by-sede`           | GET    | Indicadores agrupados por sede          |
| `/ap/commercial/dashboard/indicators/by-sede-and-brand` | GET    | Indicadores por sede y marca            |
| `/ap/commercial/dashboard/indicators/by-advisor`        | GET    | Indicadores por asesor                  |

#### Parámetros

Todos los endpoints requieren:

- `date_from` (string, formato: Y-m-d): Fecha de inicio
- `date_to` (string, formato: Y-m-d): Fecha de fin

#### Ejemplos de Uso

**1. Indicadores por Rango de Fechas**

```bash
GET /api/ap/commercial/dashboard/indicators/by-date-range?date_from=2025-01-01&date_to=2025-01-31
```

**Respuesta:**

```json
{
  "success": true,
  "data": {
    "total_visitas": 150,
    "no_atendidos": 30,
    "atendidos": 100,
    "descartados": 20,
    "por_estado_oportunidad": {
      "FRIO": 40,
      "TEMPLADO": 35,
      "CALIENTE": 15,
      "VENTA CONCRETADA": 8,
      "CERRADA": 2
    }
  },
  "periodo": {
    "fecha_inicio": "2025-01-01",
    "fecha_fin": "2025-01-31"
  }
}
```

**2. Indicadores por Sede**

```bash
GET /api/ap/commercial/dashboard/indicators/by-sede?date_from=2025-01-01&date_to=2025-01-31
```

**Respuesta:**

```json
{
  "success": true,
  "data": [
    {
      "sede_id": 1,
      "sede_nombre": "Sede Chiclayo",
      "sede_abreviatura": "CHI",
      "total_visitas": 75,
      "no_atendidos": 15,
      "atendidos": 50,
      "descartados": 10,
      "por_estado_oportunidad": {
        "FRIO": 20,
        "TEMPLADO": 18,
        "CALIENTE": 8,
        "VENTA CONCRETADA": 4
      }
    },
    ...
  ]
}
```

**3. Indicadores por Sede y Marca**

```bash
GET /api/ap/commercial/dashboard/indicators/by-sede-and-brand?date_from=2025-01-01&date_to=2025-01-31
```

**Respuesta:**

```json
{
  "success": true,
  "data": [
    {
      "sede_id": 1,
      "sede_nombre": "Sede Chiclayo",
      "sede_abreviatura": "CHI",
      "vehicle_brand_id": 3,
      "marca_nombre": "SUZUKI",
      "total_visitas": 45
    },
    ...
  ]
}
```

**4. Indicadores por Asesor**

```bash
GET /api/ap/commercial/dashboard/indicators/by-advisor?date_from=2025-01-01&date_to=2025-01-31
```

**Respuesta:**

```json
{
  "success": true,
  "data": [
    {
      "worker_id": 10,
      "worker_nombre": "Juan Pérez",
      "sede_id": 1,
      "sede_nombre": "Sede Chiclayo",
      "sede_abreviatura": "CHI",
      "vehicle_brand_id": 3,
      "marca_nombre": "SUZUKI",
      "total_visitas": 25,
      "no_atendidos": 5,
      "atendidos": 18,
      "descartados": 2,
      "por_estado_oportunidad": {
        "FRIO": 8,
        "TEMPLADO": 6,
        "CALIENTE": 3,
        "VENTA CONCRETADA": 1
      }
    },
    ...
  ]
}
```

## Modelos Utilizados

### PotentialBuyers

Representa a los posibles compradores (leads).

**Campos Importantes:**

- `registration_date`: Fecha de registro
- `use`: Estado de atención (0 = NO ATENDIDO, 1 = ATENDIDO, 2 = DESCARTADO)
- `sede_id`: Sede donde se registró
- `vehicle_brand_id`: Marca de interés
- `worker_id`: Asesor asignado

### Opportunity

Representa las oportunidades de venta generadas a partir de leads.

**Campos Importantes:**

- `lead_id`: Referencia al PotentialBuyer
- `opportunity_status_id`: Estado de la oportunidad (FRIO, TEMPLADO, CALIENTE, VENTA CONCRETADA, CERRADA)
- `client_status_id`: Estado del cliente
- `opportunity_type_id`: Tipo de oportunidad

### OpportunityAction

Registra las acciones realizadas para captar al cliente.

**Campos Importantes:**

- `opportunity_id`: Referencia a la oportunidad
- `action_type_id`: Tipo de acción
- `datetime`: Fecha y hora de la acción
- `result`: Resultado de la acción

## Flujo de Datos

1. **PotentialBuyers** → Se crea el registro del posible comprador
2. **Opportunity** → Se genera una oportunidad asociada al lead
3. **OpportunityAction** → Se registran las acciones de seguimiento

## Cómo Agregar Nuevos Indicadores

### Paso 1: Agregar Método al Servicio

Editar: `app/Http/Services/Dashboard/comercial/DashboardComercialService.php`

```php
/**
 * Nuevo indicador personalizado
 */
public function getNuevoIndicador($dateFrom, $dateTo)
{
  // Tu lógica aquí
  return [/* tu respuesta */];
}
```

### Paso 2: Agregar Método al Controlador

Editar: `app/Http/Controllers/Dashboard/DashboardComercialController.php`

```php
public function getNuevoIndicador(Request $request)
{
  $request->validate([
    'date_from' => 'required|date|date_format:Y-m-d',
    'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
  ]);

  $data = $this->dashboardService->getNuevoIndicador(
    $request->date_from,
    $request->date_to
  );

  return response()->json([
    'success' => true,
    'data' => $data,
  ]);
}
```

### Paso 3: Agregar Ruta

Editar: `routes/api.php`

```php
Route::group(['prefix' => 'dashboard'], function () {
  // ... rutas existentes
  Route::get('/indicators/nuevo-indicador', [DashboardComercialController::class, 'getNuevoIndicador']);
});
```

## Cómo Agregar Nuevo Módulo de Dashboard

### Ejemplo: Dashboard de Ventas

#### 1. Crear Servicio

```
app/Http/Services/Dashboard/Ventas/DashboardVentasService.php
```

#### 2. Crear Controlador

```
app/Http/Controllers/Dashboard/DashboardVentasController.php
```

#### 3. Agregar Rutas

```php
Route::group(['prefix' => 'ventas'], function () {
  Route::group(['prefix' => 'dashboard'], function () {
    Route::get('/indicators/...', [DashboardVentasController::class, 'metodo']);
  });
});
```

## Buenas Prácticas

1. **Documentar Métodos**: Usar PHPDoc para documentar métodos y parámetros
2. **Validación**: Siempre validar parámetros en el controlador
3. **Nombres Descriptivos**: Usar nombres claros para métodos y variables
4. **Optimización**: Usar `select()` y `groupBy()` para optimizar consultas
5. **Manejo de Errores**: Implementar try-catch en operaciones críticas
6. **Testing**: Crear pruebas unitarias para nuevos indicadores

## Mantenimiento

### Ubicación de Archivos

- **Servicios de Dashboard**: `app/Http/Services/Dashboard/`
- **Controladores de Dashboard**: `app/Http/Controllers/Dashboard/`
- **Documentación**: `app/Http/Services/Dashboard/README.md`

### Contacto

Para modificaciones o dudas sobre el dashboard, revisar este archivo y la estructura de código existente.

---

**Última actualización**: Octubre 2025
