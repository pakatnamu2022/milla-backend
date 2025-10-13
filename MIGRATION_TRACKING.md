# Sistema de Trazabilidad de Migración de Órdenes de Compra

Este documento explica el nuevo sistema de migración con trazabilidad completa para las órdenes de compra de vehículos.

## Descripción General

El sistema ahora cuenta con:

1. **Campo de estado de migración** en la tabla de órdenes de compra (`migration_status`)
2. **Tabla de log detallado** (`ap_vehicle_purchase_order_migration_log`) para rastrear cada paso del proceso
3. **Job inteligente** (`VerifyAndMigratePurchaseOrderJob`) que verifica y migra automáticamente
4. **Comandos artisan** para verificar y monitorear el estado

## Campos Agregados

### Tabla: `ap_vehicle_purchase_order`

- `migration_status` (enum): 'pending', 'in_progress', 'completed', 'failed'
- `migrated_at` (timestamp): Fecha y hora de completación de la migración

### Tabla: `ap_vehicle_purchase_order_migration_log`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único del log |
| vehicle_purchase_order_id | bigint | Referencia a la orden de compra |
| step | enum | Paso del proceso (supplier, article, purchase_order, reception, etc.) |
| status | enum | Estado del paso (pending, in_progress, completed, failed) |
| table_name | string | Nombre de la tabla intermedia afectada |
| external_id | string | ID o clave en la tabla intermedia |
| proceso_estado | tinyint | Estado de ProcesoEstado en la BD intermedia (0=pendiente, 1=procesado) |
| error_message | text | Mensaje de error si el paso falló |
| attempts | integer | Número de intentos de sincronización |
| last_attempt_at | timestamp | Fecha y hora del último intento |
| completed_at | timestamp | Fecha y hora en que se completó el paso |

## Pasos del Proceso de Migración

El sistema rastrea 8 pasos independientes:

1. **supplier**: Sincronización del proveedor (`neInTbProveedor`)
2. **supplier_address**: Sincronización de la dirección del proveedor (`neInTbProveedorDireccion`)
3. **article**: Sincronización del artículo/modelo (`neInTbArticulo`)
4. **purchase_order**: Sincronización de la orden de compra (`neInTbOrdenCompra`)
5. **purchase_order_detail**: Sincronización del detalle de la OC (`neInTbOrdenCompraDet`)
6. **reception**: Sincronización de la recepción/NI (`neInTbRecepcion`)
7. **reception_detail**: Sincronización del detalle de la recepción (`neInTbRecepcionDt`)
8. **reception_detail_serial**: Sincronización del serial/VIN (`neInTbRecepcionDtS`)

## Flujo Automático

### Al Crear una Orden de Compra:

1. Se establece `migration_status = 'pending'`
2. Se crean 8 registros de log (uno por cada paso) con estado 'pending'
3. Se validan y sincronizan las dependencias:
   - Proveedor (si no existe en BD intermedia con `EmpresaId = 'CTEST'`)
   - Artículo (si no existe en BD intermedia con `EmpresaId = 'CTEST'`)
4. Se envía la orden de compra
5. Se despacha un job de verificación (`VerifyAndMigratePurchaseOrderJob`) con 30 segundos de delay

### Job de Verificación (`VerifyAndMigratePurchaseOrderJob`):

Este job se ejecuta periódicamente y:

1. **Consulta** todas las OCs con `migration_status != 'completed'`
2. Para cada OC, **verifica en la BD intermedia** (siempre filtrando por `EmpresaId = 'CTEST'`):
   - ¿Existe el proveedor en `neInTbProveedor`? ¿Está procesado (ProcesoEstado = 1)?
   - ¿Existe la dirección en `neInTbProveedorDireccion`? ¿Está procesada?
   - ¿Existe el artículo en `neInTbArticulo`? ¿Está procesado?
   - ¿Existe la orden de compra en `neInTbOrdenCompra`? ¿Está procesada?
   - ¿Existe la recepción en `neInTbRecepcion`? ¿Está procesada?
3. **Sincroniza** lo que falta automáticamente
4. **Actualiza los logs** con el estado real de cada paso
5. Cuando todos los pasos están completados y procesados, marca la OC como `migration_status = 'completed'`

> **IMPORTANTE:** Todas las consultas a las tablas intermedias se filtran por `EmpresaId = Company::AP_DYNAMICS` (valor: 'CTEST') para asegurar que solo se consulten y sincronicen datos de la empresa correcta.

## Comandos Disponibles

### 1. Verificar Migración

Despacha el job de verificación para procesar órdenes pendientes:

```bash
# Verificar todas las órdenes pendientes (USA COLA - requiere workers)
php artisan po:verify-migration --all

# Verificar una orden específica (USA COLA - requiere workers)
php artisan po:verify-migration --id=123

# Despachar job global (USA COLA - requiere workers)
php artisan po:verify-migration

# ===== MODO DEBUG: Ejecutar INMEDIATAMENTE sin cola =====

# Verificar una orden específica de forma SÍNCRONA (sin workers)
php artisan po:verify-migration --id=123 --sync

# Verificar todas de forma SÍNCRONA (sin workers) - ¡LENTO!
php artisan po:verify-migration --all --sync
```

**IMPORTANTE**:
- **Por defecto**: Los comandos usan **cola** y requieren workers corriendo
- **Con `--sync`**: Ejecuta inmediatamente sin cola (útil para debug)
- **Para producción/cron**: Usa **sin `--sync`** y mantén workers corriendo

### 2. Ver Estado de Migración

Muestra el estado de migración de las órdenes:

```bash
# Ver resumen general
php artisan po:migration-status

# Ver detalle de una orden específica
php artisan po:migration-status --id=123
```

## Configuración de Workers (Obligatorio)

Para que los comandos funcionen, **DEBES tener workers corriendo**:

### Opción 1: Worker Manual (Desarrollo)

En una terminal separada, ejecuta:

```bash
# Worker básico
php artisan queue:work --queue=sync

# Worker con reintentos y timeout
php artisan queue:work --queue=sync --tries=3 --timeout=300

# Worker que se detiene cuando no hay jobs (útil para testing)
php artisan queue:work --queue=sync --stop-when-empty
```

### Opción 2: Supervisor (Producción)

Crea un archivo de configuración de Supervisor:

```ini
[program:milla-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/completa/artisan queue:work --queue=sync --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/ruta/completa/storage/logs/worker.log
stopwaitsecs=3600
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start milla-queue-worker:*
```

### Configurar Cola en .env

```env
# Para desarrollo (ejecuta inmediatamente)
QUEUE_CONNECTION=sync

# Para producción (usa tabla database)
QUEUE_CONNECTION=database
```

Si usas `database`, ejecuta primero:
```bash
php artisan queue:table
php artisan migrate
```

## Programación Automática

Puedes programar el job de verificación para que se ejecute periódicamente en `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Ejecutar cada 5 minutos (DESPACHA A COLA)
    $schedule->command('po:verify-migration --all')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
```

**IMPORTANTE**: El comando del cron **despacha jobs a la cola**, los workers los procesan.

## Monitoreo

### Consultar OCs Pendientes:

```php
$pending = VehiclePurchaseOrder::notMigrated()->get();
```

### Consultar OCs Completadas:

```php
$completed = VehiclePurchaseOrder::migrated()->get();
```

### Consultar OCs con Errores:

```php
$failed = VehiclePurchaseOrder::where('migration_status', 'failed')->get();
```

### Ver Logs de una OC:

```php
$purchaseOrder = VehiclePurchaseOrder::with('migrationLogs')->find($id);

foreach ($purchaseOrder->migrationLogs as $log) {
    echo "Paso: {$log->step}\n";
    echo "Estado: {$log->status}\n";
    echo "ProcesoEstado: {$log->proceso_estado}\n";
    echo "Error: {$log->error_message}\n";
}
```

## Ventajas del Nuevo Sistema

1. **Trazabilidad Completa**: Sabes exactamente en qué paso está cada orden
2. **Reintentos Automáticos**: El job verifica constantemente y completa lo que falta
3. **Visibilidad de Errores**: Cada paso registra su error específico
4. **No Duplica Datos**: Verifica antes de enviar
5. **Independiente**: No depende de verificaciones manuales
6. **Escalable**: Procesa múltiples órdenes en paralelo

## Troubleshooting

### Una orden está en 'failed'

1. Revisar los logs específicos:
   ```bash
   php artisan po:migration-status --id=<ID>
   ```

2. Ver el mensaje de error en el campo `error_message` del log

3. Reintentar la migración:
   ```bash
   php artisan po:verify-migration --id=<ID>
   ```

### Una orden está en 'in_progress' por mucho tiempo

- Puede estar esperando que Dynamics procese algún paso anterior
- Verificar en la BD intermedia el estado de `ProcesoEstado`
- El job automáticamente continuará cuando los pasos anteriores estén completos

### Forzar re-verificación de todas las órdenes

```bash
php artisan po:verify-migration --all
```

## Ejecución de las Migraciones

Para aplicar las migraciones en la base de datos:

```bash
php artisan migrate
```

Esto creará:
- Los nuevos campos en `ap_vehicle_purchase_order`
- La tabla `ap_vehicle_purchase_order_migration_log`
