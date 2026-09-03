# ✅ REFACTORIZACIÓN COMPLETA - Sistema de Salida de Inventario

## 🎯 Resumen Ejecutivo

Se ha completado exitosamente la refactorización del sistema de salida de inventario para las áreas de **Taller (881)** y **Mesón/Repuestos (882)**.

### Problema Original

El comprobante 3304 (FV52-00000101) tenía 25 OTs relacionadas, pero solo 19 generaron salida de inventario. Las 6 OTs restantes fallaron debido a:

**Causa Raíz**: Bug en el flujo de liberación y remoción de stock
- El método `removeStock()` validaba contra `available_quantity` DESPUÉS de liberar la reserva
- Cuando había sobre-reservas, `available_quantity` podía ser 0, causando que la validación fallara incorrectamente

**OTs afectadas**: 140, 142, 410, 930, 933 (producto 833 - LUBRICANTE 75W90 GL4)
- Todas requerían 3 unidades del producto 833
- Producto tenía: `quantity=15, reserved=18, available=-3` (sobre-reserva)
- El flujo antiguo fallaba al validar después de liberar la reserva

## 🔧 Soluciones Implementadas

### 1. Métodos Centralizados (ÚNICA FUENTE DE VERDAD)

**Archivo**: `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`

#### a) `releaseReservedStockAndRemove()` - Para salidas CON reserva
```php
// Casos de uso:
// - Órdenes de Trabajo: SIEMPRE tienen reserva previa
// - Cotizaciones con supply_type='STOCK'

Flujo:
1. Valida reserved_quantity >= cantidad
2. Valida quantity >= cantidad
3. Libera reserva y remueve stock atómicamente
4. Mantiene invariante: quantity = available_quantity + reserved_quantity
```

#### b) `removeStockWithoutReservation()` - Para salidas SIN reserva
```php
// Casos de uso:
// - Cotizaciones con supply_type!='STOCK' (LOCAL, CENTRAL, IMPORTACION)

Flujo:
1. Valida available_quantity >= cantidad
2. Remueve stock
3. Mantiene invariante: quantity = available_quantity + reserved_quantity
```

#### c) `removeStockFromSale()` - Método de alto nivel
```php
// Decide automáticamente qué flujo usar según $hasReservation
```

### 2. Refactorización de `InventoryMovementService`

**Archivo**: `app/Http/Services/ap/postventa/gestionProductos/InventoryMovementService.php`

#### Órdenes de Trabajo (líneas 1931-1944)
```php
// ANTES: releaseReservedStock() + updateStockFromMovement() ❌
// DESPUÉS: removeStockFromSale(hasReservation=true) ✅
```

#### Cotizaciones de Mesón (líneas 1802-1817)
```php
// ANTES: if(supply_type=STOCK) releaseReservedStock() + updateStockFromMovement() ❌
// DESPUÉS: removeStockFromSale(hasReservation según supply_type) ✅
```

### 3. Exclusión de Áreas 881 y 882 en Job

**Archivo**: `app/Jobs/SyncAccountingStatusJob.php` (líneas 48-56)

```php
// El Job ahora excluye áreas 881 y 882 del procesamiento masivo automático
// Estas áreas solo se procesan cuando se invoca con documentId específico
whereNotIn('area_id', [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])
```

**Razón**: Requieren validaciones de stock específicas antes de contabilizar.

### 4. Comando de Simulación

**Archivo**: `app/Console/Commands/SimulateInventoryOutputCommand.php`

```bash
# Uso
php artisan inventory:simulate-output {document_id}

# Ejemplo
php artisan inventory:simulate-output 3304
```

**Características**:
- ✅ 100% seguro (NO modifica la base de datos)
- ✅ Muestra estado antes→después de cada producto
- ✅ Identifica problemas de stock, reservas, almacenes
- ✅ Funciona para comprobantes antiguos y nuevos
- ✅ Soporta OTs individuales, cotizaciones y facturas masivas

## 📊 Resultados

### Prueba con Comprobante 3304

```bash
php artisan inventory:simulate-output 3304
```

**Resultados**:
- ✅ 25 OTs analizadas
- ✅ 19 OTs ya procesadas (omitidas)
- ✅ 6 OTs pendientes ahora muestran que se procesarían correctamente
- ✅ 70 productos analizados
- ✅ 0 errores actuales (el stock se corrigió desde entonces)

### Comparación: Antes vs Después

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| **Validación de stock** | Contra `available_quantity` después de liberar reserva ❌ | Atómica: Valida reserva Y stock físico ANTES de procesar ✅ |
| **Flujo con reserva** | 2 pasos separados (liberar + remover) con bug ❌ | 1 operación atómica `releaseReservedStockAndRemove()` ✅ |
| **Flujo sin reserva** | Mismo método que con reserva (confuso) ❌ | Método específico `removeStockWithoutReservation()` ✅ |
| **Cotizaciones mesón** | No distinguía supply_type correctamente ❌ | Flujo específico según supply_type ✅ |
| **Job áreas 881/882** | Procesamiento masivo automático ❌ | Solo por ID específico ✅ |
| **Diagnóstico** | Solo logs de errores ❌ | Comando de simulación completo ✅ |
| **Mantenibilidad** | Lógica duplicada en múltiples lugares ❌ | Una sola fuente de verdad ✅ |

## 📝 Ejemplos Prácticos

### Ejemplo 1: Orden de Trabajo

**Estado inicial:**
```
Producto A:
  quantity = 20
  reserved_quantity = 5
  available_quantity = 15
```

**OT requiere**: 5 unidades

**Proceso (releaseReservedStockAndRemove):**
1. ✅ Valida `reserved_quantity (5) >= 5`
2. ✅ Valida `quantity (20) >= 5`
3. Libera: `reserved_quantity = 0`
4. Remueve: `quantity = 15`
5. Actualiza: `available_quantity = 15`

**Resultado:**
```
quantity = 15
reserved_quantity = 0
available_quantity = 15
✅ INVARIANTE OK: 15 = 15 + 0
```

### Ejemplo 2: Cotización Mesón (supply_type='STOCK')

**Estado inicial:**
```
Producto B:
  quantity = 25
  reserved_quantity = 4
  available_quantity = 21
```

**Cotización requiere**: 4 unidades (supply_type='STOCK')

**Proceso (releaseReservedStockAndRemove):**
- Mismo flujo que OT (tiene reserva previa)

**Resultado:**
```
quantity = 21
reserved_quantity = 0
available_quantity = 21
✅ INVARIANTE OK: 21 = 21 + 0
```

### Ejemplo 3: Cotización Mesón (supply_type='LOCAL')

**Estado inicial:**
```
Producto C:
  quantity = 30
  reserved_quantity = 5
  available_quantity = 25
```

**Cotización requiere**: 6 unidades (supply_type='LOCAL')

**Proceso (removeStockWithoutReservation):**
1. ✅ Valida `available_quantity (25) >= 6`
2. Remueve: `quantity = 24`
3. Actualiza: `available_quantity = 19`

**Resultado:**
```
quantity = 24
reserved_quantity = 5  (sin cambios)
available_quantity = 19
✅ INVARIANTE OK: 24 = 19 + 5
```

## 🔍 Cómo Usar el Sistema Refactorizado

### Para Desarrolladores

```php
// En cualquier servicio que necesite remover stock:

// 1. Para OTs (siempre tienen reserva)
$this->stockService->removeStockFromSale(
    $productId,
    $warehouseId,
    $quantity,
    true  // hasReservation = true
);

// 2. Para cotizaciones (depende del supply_type)
$hasReservation = $detail->supply_type === ApOrderQuotationDetails::SUPPLY_TYPE_STOCK;
$this->stockService->removeStockFromSale(
    $productId,
    $warehouseId,
    $quantity,
    $hasReservation
);
```

### Para Administradores/QA

```bash
# Antes de contabilizar un comprobante en Dynamics,
# simular para verificar que no habrá problemas:
php artisan inventory:simulate-output {document_id}

# Auditar comprobante antiguo:
php artisan inventory:simulate-output 3304

# Diagnosticar problema reportado por usuario:
php artisan inventory:simulate-output {document_id}
```

## ⚠️ Notas Importantes

### Compatibilidad

✅ **Método antiguo `removeStock()` sigue existiendo**
- Ahora usa el nuevo flujo internamente
- Código existente sigue funcionando
- Marcado como `@deprecated` para futura migración

✅ **No afecta otros módulos**
- Solo áreas 881 (Taller) y 882 (Mesón)
- Área Comercial sin cambios

✅ **Job modificado de forma segura**
- Sigue procesando otras áreas automáticamente
- Solo excluye 881 y 882 del procesamiento masivo
- Estas áreas se procesan por ID cuando se invoca específicamente

### Invariante Crítico

**Siempre se mantiene**: `quantity = available_quantity + reserved_quantity`

Este invariante es FUNDAMENTAL para la integridad del sistema de inventario.

## 📚 Documentación Adicional

- **`REFACTORING_SUMMARY.md`**: Detalles técnicos completos
- **`INVENTORY_SIMULATION_EXAMPLES.md`**: Guía de uso del comando de simulación
- **Código fuente**: Todos los métodos tienen documentación inline completa

## ✅ Checklist de Verificación

- [x] Métodos centralizados creados
- [x] `InventoryMovementService` refactorizado
- [x] `SyncAccountingStatusJob` modificado
- [x] Comando de simulación creado y probado
- [x] Documentación completa
- [ ] Testing en ambiente de desarrollo
- [ ] Testing en ambiente de QA
- [ ] Desplegar a producción
- [ ] Monitorear logs primeros días
- [ ] Auditar comprobantes antiguos con el comando

## 🎓 Lecciones Aprendidas

1. **Operaciones atómicas son cruciales**: Liberar reserva y remover stock debe ser una sola operación
2. **Validar en el orden correcto**: Validar disponibilidad ANTES de modificar estado
3. **Una sola fuente de verdad**: Centralizar lógica evita duplicación y bugs
4. **Herramientas de diagnóstico**: Comandos de simulación facilitan debugging y prevención
5. **Distinción de flujos**: Con reserva vs sin reserva son casos diferentes que requieren lógica específica

---

**Fecha de refactorización**: 2026-08-31
**Autor**: Claude (Anthropic)
**Versión**: 1.0.0
