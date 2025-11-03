# Sincronización de Guías de Remisión a Dynamics

## Descripción General

Este documento describe el proceso de sincronización de las guías de remisión (`shipping_guides`) hacia Microsoft Dynamics GP mediante la base de datos intermedia (DBTP). El sistema utiliza Jobs en segundo plano para realizar esta sincronización de forma asíncrona.

## Arquitectura del Sistema

### Componentes Principales

1. **SyncShippingGuideJob**: Job que maneja toda la lógica de sincronización
2. **ShippingGuidesService**: Servicio que expone el método para despachar el Job
3. **VehiclePurchaseOrderMigrationLog**: Modelo para rastrear el estado de cada paso
4. **DatabaseSyncService**: Servicio encargado de enviar datos a la BD intermedia
5. **database_sync.php**: Configuración de mapeo de campos

### Flujo de Datos

```
Shipping Guide (Laravel)
    ↓
SyncShippingGuideJob
    ↓
DatabaseSyncService
    ↓
BD Intermedia (DBTP)
    ↓
Microsoft Dynamics GP
```

## Uso del Sistema

### Desde el Controlador/Servicio

```php
use App\Http\Services\ap\comercial\ShippingGuidesService;

// Instanciar el servicio
$shippingGuidesService = app(ShippingGuidesService::class);

// Sincronizar una guía específica
$response = $shippingGuidesService->syncToDynamics($shippingGuideId);

// Respuesta:
// {
//     "success": true,
//     "message": "La sincronización a Dynamics ha sido programada y se ejecutará en segundo plano",
//     "data": {...}
// }
```

### Desde la Consola Artisan (Para testing)

```bash
# Despachar el job manualmente
php artisan tinker
>>> SyncShippingGuideJob::dispatch(5);

# Ver la cola de Jobs
php artisan queue:work sync --tries=3
```

## Pasos de Sincronización

El Job sincroniza la guía en **3 pasos**:

### 1. Cabecera de Transferencia de Inventario
**Tabla destino**: `neInTbTransferenciaInventario`

**Campos principales**:
- `EmpresaId`: ID de la empresa (Company::AP_DYNAMICS)
- `TransferenciaId`: Número de documento de la guía
- `FechaEmision`: Fecha de emisión
- `FechaContable`: Fecha contable
- `Procesar`: 1 (indica que debe procesarse)
- `ProcesoEstado`: 0 (en proceso)

### 2. Detalle de Transferencia
**Tabla destino**: `neInTbTransferenciaInventarioDet`

**Campos principales**:
- `EmpresaId`: ID de la empresa
- `TransferenciaId`: Número de documento de la guía
- `Linea`: Número de línea (1, 2, 3...)
- `ArticuloId`: Código del artículo/vehículo
- `AlmacenId_Ini`: Almacén origen
- `AlmacenId_Fin`: Almacén destino
- `Cantidad`: Cantidad a transferir
- `UnidadMedidaId`: Unidad de medida

### 3. Serial de Transferencia (VIN)
**Tabla destino**: `neInTbTransferenciaInventarioDtS`

**Campos principales**:
- `EmpresaId`: ID de la empresa
- `TransferenciaId`: Número de documento
- `Linea`: Número de línea
- `Serie`: VIN del vehículo
- `ArticuloId`: Código del artículo
- `DatoUsuario1`: Campo personalizado 1
- `DatoUsuario2`: Campo personalizado 2

## Explicación del Código de Sincronización

### Líneas del método `verifyAndSyncReception` (líneas 376-389)

```php
// Sincronizar cabecera de recepción
$receptionLog->markAsInProgress();
$syncService->sync('ap_vehicle_purchase_order_reception', $receptionData, 'create');
$receptionLog->updateProcesoEstado(0);

// Sincronizar detalle de recepción
$receptionDetailLog->markAsInProgress();
$syncService->sync('ap_vehicle_purchase_order_reception_det', $receptionDetailData, 'create');
$receptionDetailLog->updateProcesoEstado(0);
```

**¿Qué hace cada línea?**

1. **`$receptionLog->markAsInProgress()`**:
   - Marca el paso como "en progreso" en la tabla de logs
   - Actualiza `status = 'in_progress'`
   - Incrementa el contador de intentos (`attempts`)
   - Registra la fecha/hora del intento (`last_attempt_at`)
   - **Propósito**: Rastrear que este paso está siendo ejecutado

2. **`$syncService->sync('ap_vehicle_purchase_order_reception', $receptionData, 'create')`**:
   - Envía los datos a la BD intermedia mediante `DatabaseSyncService`
   - `'ap_vehicle_purchase_order_reception'`: Nombre de la entidad en `database_sync.php`
   - `$receptionData`: Array con los datos mapeados
   - `'create'`: Indica que es una operación INSERT
   - **Propósito**: Insertar el registro en la tabla `neInTbRecepcion` de la BD intermedia

3. **`$receptionLog->updateProcesoEstado(0)`**:
   - Actualiza el estado del proceso en el log
   - `0` = En proceso/pendiente en la BD intermedia
   - `1` = Procesado exitosamente por Dynamics
   - `2` = Error al procesar
   - **Propósito**: Registrar que el dato fue enviado a la BD intermedia y está esperando ser procesado

4. **Se repite el proceso para el detalle**:
   - Mismo flujo pero para la tabla `neInTbRecepcionDt` (detalle)
   - Permite rastrear cada paso independientemente

## Sistema de Logs y Seguimiento

### Tabla: `ap_vehicle_purchase_order_migration_log`

Esta tabla rastrea cada paso de la sincronización:

| Campo | Descripción |
|-------|-------------|
| `shipping_guide_id` | ID de la guía de remisión |
| `step` | Paso de sincronización (ej: 'inventory_transfer') |
| `status` | Estado: 'pending', 'in_progress', 'completed', 'failed' |
| `table_name` | Nombre de la tabla en la BD intermedia |
| `external_id` | ID externo (ej: número de documento) |
| `proceso_estado` | Estado del proceso en Dynamics (0, 1, 2) |
| `error_message` | Mensaje de error si falla |
| `attempts` | Número de intentos |
| `last_attempt_at` | Última fecha de intento |
| `completed_at` | Fecha de completado |

### Estados del Proceso

- **`proceso_estado = 0`**: Enviado a BD intermedia, esperando procesamiento
- **`proceso_estado = 1`**: Procesado exitosamente por Dynamics
- **`proceso_estado = 2`**: Error al procesar en Dynamics

## Verificaciones Previas (Comentadas para implementar)

En el Job `SyncShippingGuideJob` hay secciones comentadas con verificaciones que se pueden implementar:

### 1. Verificar que el Vehículo existe en Dynamics

```php
// protected function verifyVehicleExists(ShippingGuides $shippingGuide): bool
// {
//     $vehicle = $shippingGuide->vehicleMovement?->vehicle;
//     if (!$vehicle) {
//         return false;
//     }
//
//     $existsInDynamics = DB::connection('dbtp')
//         ->table('neInTbArticulo')
//         ->where('EmpresaId', Company::AP_DYNAMICS)
//         ->where('Articulo', $vehicle->model->code)
//         ->exists();
//
//     return $existsInDynamics;
// }
```

**¿Para qué sirve?**
- Verifica que el vehículo/artículo ya existe en Dynamics antes de crear la transferencia
- Evita errores de "artículo no encontrado" en Dynamics
- Si no existe, podría disparar un Job para sincronizar el artículo primero

### 2. Verificar que los Almacenes existen

```php
// protected function verifyWarehousesExist(ShippingGuides $shippingGuide): bool
// {
//     // Verificar almacén origen
//     $originExists = DB::connection('dbtp')
//         ->table('neInTbAlmacen')
//         ->where('EmpresaId', Company::AP_DYNAMICS)
//         ->where('AlmacenId', $shippingGuide->sedeTransmitter->warehouse_code)
//         ->exists();
//
//     // Verificar almacén destino
//     $destinationExists = DB::connection('dbtp')
//         ->table('neInTbAlmacen')
//         ->where('EmpresaId', Company::AP_DYNAMICS)
//         ->where('AlmacenId', $shippingGuide->sedeReceiver->warehouse_code)
//         ->exists();
//
//     return $originExists && $destinationExists;
// }
```

**¿Para qué sirve?**
- Verifica que ambos almacenes (origen y destino) existen en Dynamics
- Evita errores de "almacén no encontrado"
- Permite tomar acciones correctivas antes de intentar la sincronización

### 3. Verificar si ya existe en la BD Intermedia

```php
// $existingTransfer = DB::connection('dbtp')
//     ->table('neInTbTransferenciaInventario')
//     ->where('EmpresaId', Company::AP_DYNAMICS)
//     ->where('TransferenciaId', $shippingGuide->document_number)
//     ->first();
//
// if ($existingTransfer) {
//     $transferLog->updateProcesoEstado(
//         $existingTransfer->ProcesoEstado ?? 0,
//         $existingTransfer->ProcesoError ?? null
//     );
//     return;
// }
```

**¿Para qué sirve?**
- Evita duplicados en la BD intermedia
- Si ya existe, solo actualiza el estado del log
- Permite re-ejecutar el Job sin crear registros duplicados

## Campo `status_dynamic` en ShippingGuides

El Job actualiza automáticamente este campo según el resultado:

- **`synced`**: Todos los pasos completados exitosamente
- **`sync_failed`**: Algún paso falló
- **`null`**: No se ha intentado sincronizar

## Configuración en `database_sync.php`

### Entidad: `inventory_transfer`

```php
'inventory_transfer' => [
    'dbtp' => [
        'enabled' => env('SYNC_DBTP_ENABLED', false),
        'connection' => 'dbtp',
        'table' => 'neInTbTransferenciaInventario',
        'mapping' => [
            'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
            'TransferenciaId' => 1,  // Aquí va el document_number
            'FechaEmision' => fn($data) => $data['issue_date'],
            'FechaContable' => fn($data) => $data['issue_date'],
            'Procesar' => 1,
            'FechaProceso' => fn($data) => $data['issue_date'],
        ],
        'sync_mode' => 'insert',
        'unique_key' => 'TransferenciaId',
        'actions' => [
            'create' => true,
            'update' => false,
            'delete' => false,
        ],
    ]
],
```

**Campos a actualizar**:
- Cambiar los valores hardcoded (1, 'TODO', etc.) por funciones que obtengan los valores reales
- Agregar todos los campos requeridos por Dynamics

## Monitoreo y Debugging

### Ver logs de sincronización

```php
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;

// Obtener logs de una guía
$logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', 5)->get();

// Ver logs fallidos
$failedLogs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', 5)
    ->where('status', 'failed')
    ->get();

// Ver estado del proceso en Dynamics
$logs->each(function($log) {
    echo "Paso: {$log->step}\n";
    echo "Estado: {$log->status}\n";
    echo "Proceso Estado: {$log->proceso_estado}\n";
    echo "Error: {$log->error_message}\n\n";
});
```

### Logs de Laravel

Los errores se registran en `storage/logs/laravel.log`:

```
[2025-10-30 16:30:00] local.ERROR: Error en SyncShippingGuideJob {"shipping_guide_id":5,"error":"..."}
```

## Troubleshooting

### Problema: El Job no se ejecuta

**Solución**:
1. Verificar que la cola esté corriendo: `php artisan queue:work`
2. Verificar que la configuración de colas en `.env` sea correcta
3. Ver jobs fallidos: `php artisan queue:failed`

### Problema: ProcesoEstado siempre es 0

**Solución**:
1. Dynamics aún no ha procesado el registro
2. Verificar que el servicio de integración de Dynamics esté corriendo
3. Revisar la tabla `neInTbTransferenciaInventario` en la BD intermedia

### Problema: Error "El vehículo no existe"

**Solución**:
1. Sincronizar primero el artículo/vehículo usando `SyncArticleJob`
2. Implementar las verificaciones comentadas
3. Agregar validación antes de despachar el Job

## Próximos Pasos

1. **Completar el mapeo en `database_sync.php`**: Agregar todos los campos requeridos
2. **Implementar verificaciones**: Descomentar y completar los métodos de verificación
3. **Crear Resources específicos**: Para mapear correctamente los datos de ShippingGuides
4. **Agregar ruta en API**: Para exponer el endpoint de sincronización
5. **Agregar tests**: Para asegurar que la sincronización funciona correctamente

## Ejemplo Completo de Uso

```php
// 1. Crear una guía de remisión
$shippingGuide = ShippingGuides::create([...]);

// 2. Enviar a SUNAT (opcional)
$shippingGuidesService->sendToNubefact($shippingGuide->id);

// 3. Sincronizar a Dynamics
$response = $shippingGuidesService->syncToDynamics($shippingGuide->id);

// 4. Monitorear el progreso
$logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)->get();

foreach ($logs as $log) {
    if ($log->proceso_estado === 1) {
        echo "✓ {$log->step} completado\n";
    } elseif ($log->status === 'failed') {
        echo "✗ {$log->step} falló: {$log->error_message}\n";
    } else {
        echo "⏳ {$log->step} en proceso...\n";
    }
}

// 5. Verificar estado final
$shippingGuide->refresh();
if ($shippingGuide->status_dynamic === 'synced') {
    echo "✓ Sincronización completada exitosamente\n";
}
```

## Resumen

El sistema de sincronización de guías de remisión a Dynamics:

1. ✅ Usa Jobs en segundo plano para no bloquear la aplicación
2. ✅ Rastrea cada paso de la sincronización en una tabla de logs
3. ✅ Maneja reintentos automáticos en caso de fallos temporales
4. ✅ Proporciona visibilidad completa del estado de sincronización
5. ✅ Es extensible para agregar más verificaciones y validaciones
6. ✅ Reutiliza la infraestructura existente de Purchase Orders

El código está preparado para evolucionar con las necesidades del negocio agregando las verificaciones comentadas y completando el mapeo de campos.