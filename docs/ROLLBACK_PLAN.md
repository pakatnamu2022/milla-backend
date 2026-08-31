# Plan de Rollback - Optimización de Cálculo de Costos

## Fecha: 20 de Agosto de 2026
## Autor: Claude Sonnet 4.5

---

## 🎯 Resumen de Cambios Implementados

Esta optimización reduce las escrituras a disco en la tabla `weighted_average_cost_history` de **millones de operaciones pequeñas** a **pocas operaciones grandes**, eliminando el cuello de botella que causaba picos sostenidos de escritura a disco (8-14 MB/s).

### Cambios principales:

1. **Método incremental** para movimientos recientes (1 INSERT en vez de DELETE + N INSERTs)
2. **BULK INSERT** para recálculos retroactivos (1 query en vez de N queries)
3. **Locks de concurrencia** (`withoutOverlapping` + `lockForUpdate`) para evitar race conditions
4. **Períodos contables cerrados** (opcional, desactivado por defecto)

---

## 🚨 Estrategias de Rollback

### NIVEL 1: Rollback instantáneo vía Feature Flag (SIN deploy)

**Tiempo estimado:** Inmediato (segundos)

**Procedimiento:**

```bash
# En servidor de producción
vim .env

# Cambiar:
COST_CALCULATION_MODE=incremental

# Por:
COST_CALCULATION_MODE=legacy

# Limpiar cache y reiniciar workers
php artisan config:clear
php artisan queue:restart
```

**Efecto:**
- Vuelve al comportamiento antiguo (DELETE + N INSERTs uno por uno)
- No requiere revertir código ni hacer deploy
- Los datos históricos quedan intactos

**Cuándo usar:**
- Detectas inconsistencias en los cálculos de costo promedio
- Los jobs fallan masivamente
- Performance empeora en vez de mejorar (caso inesperado)

---

### NIVEL 2: Rollback de código (con deploy)

**Tiempo estimado:** 5-10 minutos

**Procedimiento:**

```bash
# En tu máquina local
git log --oneline | head -10  # Identificar commits de la optimización

# Revertir commits (ejemplo)
git revert <commit-hash-parte-3>
git revert <commit-hash-parte-2>
git revert <commit-hash-parte-1>

# Hacer commit del revert
git push origin wilmer

# En servidor de producción
git pull
php artisan config:clear
php artisan queue:restart

# Si aplicaste la migración de accounting_periods, hacer rollback
php artisan migrate:rollback --step=1
```

**Efecto:**
- Elimina completamente el código nuevo
- Vuelve al flujo original 100%
- Elimina la tabla `accounting_periods` si se había creado

**Cuándo usar:**
- El feature flag no es suficiente
- Quieres eliminar el código para siempre
- Necesitas liberar memoria/recursos

---

### NIVEL 3: Reconstruir historial completo (emergencia)

**Tiempo estimado:** 1-2 horas (dependiendo del volumen)

⚠️ **SOLO usar si detectas corrupción de datos**

**Procedimiento:**

```bash
# Crear script temporal de reconstrucción
vim rebuild_all_cost_history.php
```

**Contenido del script:**

```php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;

$service = app(ProductWarehouseStockService::class);
$stocks = ProductWarehouseStock::all();
$total = $stocks->count();
$processed = 0;

echo "Reconstruyendo historial de {$total} registros...\n";

foreach ($stocks as $stock) {
    try {
        $service->rebuildWeightedAverageCostHistory(
            $stock->product_id,
            $stock->warehouse_id,
            null // Rebuild completo desde cero
        );

        $processed++;
        if ($processed % 100 == 0) {
            echo "Procesados: {$processed}/{$total}\n";
        }
    } catch (Exception $e) {
        echo "ERROR en producto {$stock->product_id}, almacén {$stock->warehouse_id}: {$e->getMessage()}\n";
    }
}

echo "\nFinalizado. Procesados: {$processed}/{$total}\n";
```

**Ejecutar:**

```bash
php rebuild_all_cost_history.php
```

**Efecto:**
- Recalcula TODO el historial desde cero
- Usa el método actual (sea incremental o legacy según el feature flag)
- Puede tardar horas en producción

**Cuándo usar:**
- Detectas que los costos promedio están incorrectos en múltiples productos
- Sospechas corrupción de datos
- Después de revertir código y quieres asegurar consistencia

---

## 🔍 Validación Post-Rollback

### 1. Verificar que el flujo funciona

```bash
# Crear un movimiento de prueba y verificar que se procesa
# Verificar en Horizon que los jobs se ejecutan sin errores
```

### 2. Comparar números antes/después

```sql
-- En producción, verificar que los costos promedio son consistentes
SELECT
    product_id,
    warehouse_id,
    quantity,
    average_cost,
    sale_price
FROM product_warehouse_stock
WHERE product_id IN (1938, 23, 28)
  AND warehouse_id IN (164, 166);
```

### 3. Monitorear escrituras a disco

```bash
# Verificar que las escrituras vuelven al nivel esperado
# (alto si volviste a legacy, bajo si mantienes incremental)
```

---

## 📊 Indicadores de Éxito/Fracaso

### ✅ Señales de que la optimización funciona bien:

- Escrituras a disco caen de 8-14 MB/s a < 1 MB/s
- Jobs de `product_cost_recalculation` se ejecutan más rápido
- No hay errores en los logs de Horizon
- Los costos promedio coinciden con validaciones manuales

### ❌ Señales de que debes hacer rollback:

- Los costos promedio no coinciden con cálculos manuales
- Jobs fallan masivamente con errores de concurrencia
- Performance empeora (caso inesperado)
- Horizon muestra jobs en estado `failed` constantemente

---

## 📝 Notas Adicionales

### Períodos contables cerrados

Si habilitaste `ACCOUNTING_PERIODS_ENABLED=true`:

```bash
# Para desactivar (sin eliminar datos)
# En .env:
ACCOUNTING_PERIODS_ENABLED=false

# Limpiar cache
php artisan config:clear

# Para eliminar la funcionalidad completamente:
php artisan migrate:rollback --step=1
```

### Datos históricos

**IMPORTANTE:** Los datos en `weighted_average_cost_history` NO se pierden con el rollback. Solo cambia el MÉTODO de escritura (incremental vs legacy).

### Contacto

Si necesitas ayuda con el rollback, contactar al equipo de desarrollo con:
- Logs de Horizon (`storage/logs/laravel.log`)
- Ejemplos de productos afectados (IDs)
- Descripción del problema observado

---

## 🔗 Referencias

- Ticket original: Optimización de escrituras en `weighted_average_cost_history`
- Tests de validación: `tests/validate_incremental_cost_calculation.php`
- Documentación de feature flags: `.env.example`