# Guía Rápida - Sistema de Migración de OCs

## Setup Inicial

### 1. Ejecutar Migraciones
```bash
php artisan migrate
```

### 2. Configurar Cola (Elige una opción)

#### Opción A: Para Desarrollo Rápido
En `.env`:
```env
QUEUE_CONNECTION=sync
```
✅ Los jobs se ejecutan inmediatamente (no necesitas workers)

#### Opción B: Para Producción/Testing Real
En `.env`:
```env
QUEUE_CONNECTION=database
```

Ejecutar:
```bash
php artisan queue:table
php artisan migrate
```

Iniciar worker en terminal separada:
```bash
php artisan queue:work --queue=sync --tries=3 --timeout=300
```

---

## Uso Diario

### Para Testing/Debug (una OC específica)

```bash
# Ver estado actual
php artisan po:migration-status --id=1

# Ejecutar verificación INMEDIATAMENTE (sin worker)
php artisan po:verify-migration --id=1 --sync

# Ver resultado
php artisan po:migration-status --id=1
```

### Para Producción (muchas OCs)

```bash
# Iniciar worker (terminal 1)
php artisan queue:work --queue=sync

# Despachar verificación de todas las pendientes (terminal 2)
php artisan po:verify-migration --all

# Ver estado general
php artisan po:migration-status
```

---

## Cron Automático

En `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Cada 5 minutos, verifica OCs pendientes
    $schedule->command('po:verify-migration --all')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
```

**Importante**: Mantén workers corriendo con Supervisor en producción.

---

## Comandos Útiles

```bash
# Ver estado de una OC
php artisan po:migration-status --id=<ID>

# Ver resumen general
php artisan po:migration-status

# Verificar una OC (despacha a cola)
php artisan po:verify-migration --id=<ID>

# Verificar todas (despacha a cola)
php artisan po:verify-migration --all

# Verificar inmediatamente sin cola (debug)
php artisan po:verify-migration --id=<ID> --sync

# Ver jobs en cola
php artisan queue:monitor

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Ver logs
tail -f storage/logs/laravel.log
```

---

## Troubleshooting

### El comando no hace nada
- ✅ ¿Tienes workers corriendo? → `php artisan queue:work --queue=sync`
- ✅ O usa `--sync`: `php artisan po:verify-migration --id=1 --sync`

### Los logs no se actualizan
- ✅ Verifica que el worker esté procesando jobs
- ✅ Revisa `storage/logs/laravel.log`

### Una OC está en 'failed'
```bash
# Ver detalle del error
php artisan po:migration-status --id=<ID>

# Reintentar
php artisan po:verify-migration --id=<ID> --sync
```

### Verificar estado en BD intermedia
```sql
-- Ver proveedor
SELECT * FROM neInTbProveedor
WHERE EmpresaId = 'CTEST' AND NumeroDocumento = '<num_doc>';

-- Ver artículo
SELECT * FROM neInTbArticulo
WHERE EmpresaId = 'CTEST' AND Articulo = '<codigo>';

-- Ver OC
SELECT * FROM neInTbOrdenCompra
WHERE EmpresaId = 'CTEST' AND OrdenCompraId = '<numero_oc>';

-- Ver recepción
SELECT * FROM neInTbRecepcion
WHERE EmpresaId = 'CTEST' AND RecepcionId = '<numero_ni>';
```

---

## Flujo Completo

1. **Se crea una OC** → Se establece `migration_status = 'pending'`
2. **Se crean 8 logs** → Uno por cada paso (proveedor, artículo, OC, recepción, etc.)
3. **Cron/Comando ejecuta** → `po:verify-migration --all` cada 5 minutos
4. **Workers procesan** → Verifican y sincronizan lo que falta
5. **Job actualiza logs** → Estado, intentos, errores
6. **Cuando todo está en ProcesoEstado=1** → OC marca como `completed`

---

## Configuración Recomendada

### Desarrollo
```env
QUEUE_CONNECTION=sync  # No necesita workers
```

### Producción
```env
QUEUE_CONNECTION=database  # Usa workers con Supervisor
```

Con 3 workers en Supervisor procesando en paralelo.
