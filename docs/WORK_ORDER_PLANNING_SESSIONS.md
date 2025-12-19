# Sistema de Planificación de Órdenes de Trabajo con Sesiones

## 📋 Descripción General

Este sistema permite gestionar órdenes de trabajo con planificación de tareas que pueden tener **múltiples sesiones de
trabajo con pausas**. Las horas trabajadas se acumulan a través de diferentes sesiones, permitiendo que un trabajo se
complete fuera de la fecha planificada.

### ⚡ Optimizado para Rapidez

El sistema está diseñado para que los trabajadores de taller registren tiempos de forma **rápida y sencilla**:
- Campos opcionales minimizados
- Endpoints simples para acciones comunes
- Validaciones ligeras
- Interfaz enfocada en lo esencial

## 🗂️ Estructura de Tablas

### 1. `work_order_planning`

Tabla principal de planificación de tareas en una orden de trabajo.

**Campos clave:**

- `description`: Descripción de la tarea (obligatorio, máx 255 caracteres)
- `worker_id`: ID del trabajador asignado (obligatorio)
- `work_order_id`: ID de la orden de trabajo (obligatorio)
- `estimated_hours`: Horas estimadas (opcional, para flexibilidad)
- `planned_start_datetime`: Fecha/hora planificada de inicio (opcional)
- `planned_end_datetime`: Fecha/hora planificada de finalización (opcional)
- `actual_hours`: **Suma acumulada** de horas trabajadas (auto-calculado)
- `actual_start_datetime`: Fecha/hora real del primer inicio (auto-calculado)
- `actual_end_datetime`: Fecha/hora real de finalización (auto-calculado)
- `status`: `planned`, `in_progress`, `completed`, `canceled` (auto-actualizado)

### 2. `work_order_planning_sessions`

Tabla de sesiones individuales de trabajo (cada inicio/pausa/reanudación).

**Campos clave:**

- `start_datetime`: Momento en que se inicia esta sesión
- `end_datetime`: Momento en que se pausa/finaliza esta sesión
- `hours_worked`: Horas trabajadas en **esta sesión específica**
- `status`: `in_progress`, `paused`, `completed`
- `pause_reason`: Razón de la pausa (ej: "Esperando repuesto")
- `notes`: Notas adicionales sobre esta sesión

## 📊 Ejemplo de Flujo de Trabajo

### Escenario

Un mecánico debe hacer un mantenimiento estimado en **3 horas**, planificado para completarse hoy.

### Sesión 1: Hoy 9:00 AM - 11:00 AM

```php
$planning = ApWorkOrderPlanning::find(1);
$planning->startSession("Iniciando revisión del motor");
// El mecánico trabaja 2 horas...
// Nota que falta un repuesto
$planning->pauseWork("Falta repuesto - llegará mañana");
```

**Resultado:**

- Sesión 1: 2 horas trabajadas
- `actual_hours` de planning: 2.00
- `status` de planning: `in_progress`
- El trabajo sigue abierto

### Sesión 2: Mañana 10:00 AM - 10:30 AM

```php
$planning = ApWorkOrderPlanning::find(1);
$planning->startSession("Repuesto llegó, continuando trabajo");
// El mecánico trabaja 30 minutos...
$planning->completeWork();
```

**Resultado:**

- Sesión 2: 0.50 horas trabajadas (30 minutos)
- `actual_hours` de planning: 2.50 (2.00 + 0.50)
- `actual_end_datetime`: Fecha de mañana
- `status` de planning: `completed`
- **Trabajo completado en 2.5 horas reales** vs 3 horas estimadas
- **Completado fuera de la fecha planificada** pero con tiempo real correcto

## 🔧 Uso del Modelo

### Iniciar una nueva sesión de trabajo

```php
$planning = ApWorkOrderPlanning::find(1);
$session = $planning->startSession("Notas opcionales sobre esta sesión");
```

### Pausar el trabajo actual

```php
$planning->pauseWork("Esperando autorización del cliente");
```

### Reanudar el trabajo (crear nueva sesión)

```php
$planning->startSession("Reanudando trabajo con autorización");
```

### Completar el trabajo

```php
$planning->completeWork();
```

### Obtener total de horas trabajadas

```php
$totalHours = $planning->calculateTotalHoursWorked();
```

### Verificar si hay una sesión activa

```php
$activeSession = $planning->activeSession();
if ($activeSession) {
    echo "Hay trabajo en progreso desde: " . $activeSession->start_datetime;
}
```

### Obtener todas las sesiones de una planificación

```php
$sessions = $planning->sessions()->orderBy('start_datetime')->get();
foreach ($sessions as $session) {
    echo "Sesión: {$session->hours_worked} horas - {$session->status}";
    if ($session->pause_reason) {
        echo " (Razón: {$session->pause_reason})";
    }
}
```

## 📈 Beneficios del Sistema

1. **Trazabilidad Completa**: Cada pausa y reanudación queda registrada
2. **Horas Reales vs Estimadas**: Comparación precisa del tiempo real usado
3. **Justificación de Pausas**: Razones documentadas para cada pausa
4. **Flexibilidad Temporal**: El trabajo puede completarse en días diferentes
5. **Cálculo Automático**: Las horas se suman automáticamente
6. **Control de Sesiones**: Previene sesiones duplicadas activas

## 🔍 Consultas Útiles

### Obtener tiempo total trabajado vs estimado

```php
$planning = ApWorkOrderPlanning::with('sessions')->find(1);
$estimated = $planning->estimated_hours;
$actual = $planning->actual_hours;
$efficiency = ($estimated / $actual) * 100;
```

### Listar todas las pausas con razones

```php
$pausedSessions = $planning->sessions()
    ->where('status', 'paused')
    ->whereNotNull('pause_reason')
    ->get();
```

### Calcular tiempo promedio de sesiones

```php
$avgSessionTime = $planning->sessions()
    ->whereNotNull('hours_worked')
    ->avg('hours_worked');
```

## ⚠️ Validaciones Importantes

1. **No se puede iniciar una sesión si ya hay una activa**
   ```php
   // Esto lanzará una excepción
   $planning->startSession(); // Primera sesión
   $planning->startSession(); // ERROR: Ya existe una sesión activa
   ```

2. **Las horas se calculan automáticamente** al finalizar una sesión
3. **El estado de la planificación se actualiza automáticamente**

## 🌐 API Endpoints

### Gestión de Planificaciones (CRUD)

#### Listar planificaciones
```http
GET /api/work-order-planning
```

#### Crear planificación
```http
POST /api/work-order-planning
Content-Type: application/json

{
  "work_order_id": 1,
  "worker_id": 5,
  "description": "Cambio de aceite y filtros",
  "estimated_hours": 2.5,  // Opcional
  "planned_start_datetime": "2025-12-18 09:00:00",  // Opcional
  "planned_end_datetime": "2025-12-18 11:30:00"  // Opcional
}
```

#### Ver detalles de planificación
```http
GET /api/work-order-planning/{id}
```

#### Actualizar planificación
```http
PUT /api/work-order-planning/{id}
Content-Type: application/json

{
  "description": "Cambio de aceite, filtros y revisión de frenos",
  "estimated_hours": 3.5
}
```

#### Eliminar planificación
```http
DELETE /api/work-order-planning/{id}
```

### Acciones Rápidas de Sesiones (Para el Trabajador)

#### ⏱️ Iniciar trabajo
```http
POST /api/work-order-planning/{id}/start
Content-Type: application/json

{
  "notes": "Iniciando revisión"  // Opcional
}
```

#### ⏸️ Pausar trabajo
```http
POST /api/work-order-planning/{id}/pause
Content-Type: application/json

{
  "pause_reason": "Esperando repuesto"  // Opcional
}
```

#### ▶️ Reanudar trabajo
```http
POST /api/work-order-planning/{id}/start
Content-Type: application/json

{
  "notes": "Reanudando con repuesto"  // Opcional
}
```

#### ✅ Completar trabajo
```http
POST /api/work-order-planning/{id}/complete
```

#### 📊 Ver estado actual
```http
GET /api/work-order-planning/{id}/status
```

#### 📋 Ver historial de sesiones
```http
GET /api/work-order-planning/{id}/sessions
```

### Respuestas de la API

#### Estructura de respuesta de planificación
```json
{
  "id": 1,
  "work_order_id": 5,
  "worker_id": 3,
  "worker_name": "Juan Pérez",
  "description": "Cambio de aceite y filtros",
  "estimated_hours": 2.5,
  "actual_hours": 2.75,
  "planned_start_datetime": "2025-12-18 09:00:00",
  "planned_end_datetime": "2025-12-18 11:30:00",
  "actual_start_datetime": "2025-12-18 09:15:00",
  "actual_end_datetime": "2025-12-18 12:00:00",
  "status": "completed",
  "has_active_session": false,
  "sessions_count": 2,
  "sessions": [
    {
      "id": 1,
      "start_datetime": "2025-12-18 09:15:00",
      "end_datetime": "2025-12-18 11:00:00",
      "hours_worked": 1.75,
      "status": "paused",
      "pause_reason": "Esperando repuesto",
      "notes": "Iniciando revisión"
    },
    {
      "id": 2,
      "start_datetime": "2025-12-18 11:30:00",
      "end_datetime": "2025-12-18 12:00:00",
      "hours_worked": 1.0,
      "status": "completed",
      "pause_reason": null,
      "notes": "Reanudando con repuesto"
    }
  ]
}
```

## 🎯 Resumen

Este sistema resuelve el problema de que:

- ✅ Un trabajo estimado en 3 horas puede tomar múltiples sesiones
- ✅ Las pausas están documentadas con razones
- ✅ El tiempo real acumulado es preciso (2.5h en el ejemplo)
- ✅ El trabajo puede completarse fuera de la fecha planificada
- ✅ Cada sesión de trabajo queda registrada individualmente
- ✅ **Interfaz simple y rápida** para el trabajador de taller
- ✅ **Validaciones mínimas** para no demorar el registro

