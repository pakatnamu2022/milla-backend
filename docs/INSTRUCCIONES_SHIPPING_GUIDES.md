# Instrucciones para Sincronización de Guías de Remisión

## Resumen del Sistema

Se implementó el mismo patrón que usan las Purchase Orders para sincronizar Shipping Guides a Dynamics.

## Archivos Creados/Modificados

### ✅ Archivos Modificados:
1. `app/Jobs/VerifyAndMigrateShippingGuideJob.php` - Job unificado que verifica y sincroniza
2. `app/Models/ap/comercial/ShippingGuides.php` - Agregada relación `migrationLogs()`
3. `app/Http/Services/ap/comercial/ApVehicleDeliveryService.php` - Usa VerifyAndMigrateShippingGuideJob
4. `routes/console.php` - Agregado comando programado cada 30 segundos

### ✅ Archivos Nuevos:
1. `app/Console/Commands/VerifyShippingGuideMigrationCommand.php` - Comando de verificación
2. `app/Console/Commands/ShowShippingGuideMigrationStatusCommand.php` - Comando para ver estado

### ❌ Archivos Eliminados:
1. `app/Jobs/SyncShippingGuideSaleJob.php` - Consolidado en VerifyAndMigrateShippingGuideJob
2. `app/Jobs/SyncShippingGuideJob.php` - Consolidado en VerifyAndMigrateShippingGuideJob

---

## Comandos Necesarios para que Funcione

### 1. Iniciar el Queue Worker (OBLIGATORIO)
```bash
php artisan queue:work --queue=sync --tries=3
```
Este comando procesa los jobs en segundo plano. **DEBE estar corriendo siempre**.

### 2. Iniciar el Scheduler (OBLIGATORIO)
```bash
php artisan schedule:work
```
Este comando ejecuta el verificador cada 30 segundos automáticamente. **DEBE estar corriendo siempre**.

---

## Comandos de Utilidad

### Ver estado de todas las guías:
```bash
php artisan shipping-guide:migration-status
```

### Ver estado de una guía específica:
```bash
php artisan shipping-guide:migration-status --id=123
```

### Verificar manualmente UNA guía (sin esperar 30 segundos):
```bash
php artisan shipping-guide:verify-migration --id=123 --sync
```

### Verificar manualmente TODAS las guías pendientes:
```bash
php artisan shipping-guide:verify-migration --all --sync
```

---

## Flujo Completo

### Cuando se crea una guía de remisión:

1. **Frontend/API** llama al endpoint `sendToDynamic()`
2. Se ejecuta `VerifyAndMigrateShippingGuideJob::dispatchSync($shippingGuideId)`
3. El job:
   - **Verifica** si ya existe en BD intermedia
   - **Si NO existe** → Sincroniza (envía los 3 registros con `proceso_estado = 0`)
   - **Si existe** → Actualiza el estado según `ProcesoEstado`
4. El job termina ✓

### Verificación automática (cada 30 segundos):

5. El **scheduler** ejecuta: `php artisan shipping-guide:verify-migration --all`
6. El comando despacha `VerifyAndMigrateShippingGuideJob`
7. Este job repite el mismo proceso:
   - Verifica si existe en BD intermedia
   - Si no existe → Sincroniza
   - Si existe → Lee `ProcesoEstado` y actualiza logs
   - Si todos tienen `ProcesoEstado = 1` → marca guía como `completed`

---

## Verificar que todo esté funcionando

### 1. Verificar que el queue worker esté corriendo:
```bash
ps aux | grep "queue:work"
```

### 2. Verificar que el scheduler esté corriendo:
```bash
ps aux | grep "schedule:work"
```

### 3. Ver logs en tiempo real:
```bash
tail -f storage/logs/laravel.log
```

### 4. Probar con una guía:
```bash
# Ver estado inicial
php artisan shipping-guide:migration-status --id=123

# Esperar 30-60 segundos (para que el scheduler ejecute la verificación)

# Ver estado actualizado
php artisan shipping-guide:migration-status --id=123
```

---

## Producción (Recomendado)

En producción, usa **Supervisor** para mantener los procesos corriendo siempre:

### `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/tu/proyecto/artisan queue:work --queue=sync --tries=3 --timeout=180
autostart=true
autorestart=true
user=tu-usuario
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/a/tu/proyecto/storage/logs/worker.log

[program:laravel-scheduler]
process_name=%(program_name)s
command=php /ruta/a/tu/proyecto/artisan schedule:work
autostart=true
autorestart=true
user=tu-usuario
redirect_stderr=true
stdout_logfile=/ruta/a/tu/proyecto/storage/logs/scheduler.log
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## Troubleshooting

### Las guías se quedan en "in_progress":
- ✅ Verifica que el scheduler esté corriendo
- ✅ Verifica que el queue worker esté corriendo
- ✅ Revisa los logs: `tail -f storage/logs/laravel.log`
- ✅ Verifica la BD intermedia manualmente

### Error "Class not found":
```bash
composer dump-autoload
```

### Los jobs no se procesan:
```bash
# Verifica la cola
php artisan queue:failed

# Reinicia el worker
php artisan queue:restart
```

---

## Diferencia con el Sistema Anterior (El Problema)

### ❌ Sistema Anterior (NO funcionaba):
```
Había DOS jobs separados:
- SyncShippingGuideSaleJob: Solo sincronizaba guías de VENTA
- SyncShippingGuideJob: Solo sincronizaba guías de TRANSFERENCIA
- VerifyAndMigrateShippingGuideJob: Solo verificaba

Problemas:
1. Duplicación de código
2. Sincronización inmediata → NUNCA verificaba si proceso_estado = 1
3. No había re-intentos automáticos si fallaba la sincronización
```

### ✅ Sistema Nuevo (Funciona):
```
UN SOLO job unificado: VerifyAndMigrateShippingGuideJob

1. Verifica si existe en BD intermedia
2. SI NO EXISTE → Sincroniza (envía datos con proceso_estado = 0)
3. SI EXISTE → Lee ProcesoEstado y actualiza logs
4. Si todos están en ProcesoEstado = 1 → marcar como completed ✓

Ventajas:
- Un solo job para VENTA y TRANSFERENCIA
- Idempotente: se puede ejecutar múltiples veces sin efectos secundarios
- Verifica antes de sincronizar → evita duplicados
- Código más limpio y mantenible
```

---

## Comandos para iniciar TODO

Abre **2 terminales** y ejecuta:

### Terminal 1 - Queue Worker:
```bash
cd C:\laragon\www\milla-backend
php artisan queue:work --queue=sync --tries=3
```

### Terminal 2 - Scheduler:
```bash
cd C:\laragon\www\milla-backend
php artisan schedule:work
```

**¡Listo! El sistema está funcionando.**