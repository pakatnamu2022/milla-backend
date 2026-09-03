# 📋 Re-reserva de Stock después de Notas de Crédito - Solución Manual

## 🐛 Problema Identificado

### Contexto

Cuando se genera una **Nota de Crédito (NC)** para una factura ya contabilizada, el sistema:

1. **Crea movimiento RETURN_IN** → Regresa `quantity` al almacén
2. **NO toca `reserved_quantity`** → Las reservas NO se restauran

### Flujo Completo de Factura + NC

```
Estado inicial (OT/Cotización creada):
  quantity=100, reserved=15, available=85

1. FACTURA CONTABILIZADA (createSaleFromQuotation):
  → releaseReservedStockAndRemove(10)
  → reserved_quantity = 15 - 10 = 5   (libera reserva ✓)
  → quantity = 100 - 10 = 90
  → available_quantity = 90 - 5 = 85

Después de facturar:
  quantity=90, reserved=5, available=85

2. NOTA DE CRÉDITO (createReturnMovementForQuotation):
  → addStock(10) via updateStockFromMovement()
  → quantity = 90 + 10 = 100
  → reserved_quantity = 5  (NO se modifica ❌)
  → available_quantity = 100 - 5 = 95

Después de NC:
  quantity=100, reserved=5, available=95

PROBLEMA: Se perdieron 10 de reserved_quantity
```

### ¿Cuándo es Problema?

**Depende del caso de negocio:**

#### CASO A: NO vuelven a facturar
```
✅ NO hay problema
- Stock queda disponible (available=95)
- Otras operaciones pueden usar ese stock
- Las reservas "perdidas" no importan porque no van a re-facturar
```

#### CASO B: SÍ vuelven a facturar (error en datos, etc.)
```
❌ PROBLEMA
- Al intentar re-facturar, valida que NO hay stock reservado suficiente
- O peor: toma stock de otras reservas (otras OTs/Cotizaciones)
- Puede generar desbalances en otras operaciones
```

## ✅ Solución Implementada: Re-reserva Manual

### Concepto

Implementar **endpoint manual** para re-reservar stock cuando **confirmen que SÍ van a re-facturar**.

**Características:**
- **Manual**: Solo cuando el usuario confirma que va a re-facturar
- **Trackeable**: Campos `had_credit_note` y `stock_re_reserved` en BD
- **Validación**: Antes de facturar, valida si tuvo NC sin re-reservar

### Campos Agregados (Migración)

**Tabla `ap_order_quotations`:**
```sql
ALTER TABLE ap_order_quotations
ADD COLUMN had_credit_note BOOLEAN DEFAULT FALSE
  COMMENT 'TRUE si se generó nota de crédito para esta cotización',
ADD COLUMN stock_re_reserved BOOLEAN DEFAULT FALSE
  COMMENT 'TRUE si se ejecutó manualmente la re-reserva de stock después de NC';
```

**Tabla `ap_work_orders`:**
```sql
ALTER TABLE ap_work_orders
ADD COLUMN had_credit_note BOOLEAN DEFAULT FALSE
  COMMENT 'TRUE si se generó nota de crédito para esta orden de trabajo',
ADD COLUMN stock_re_reserved BOOLEAN DEFAULT FALSE
  COMMENT 'TRUE si se ejecutó manualmente la re-reserva de stock después de NC';
```

## 📡 Nuevo Endpoint: Re-reservar Stock

### Endpoint

```
POST /api/ap/postVenta/productWarehouseStock/re-reserve-after-credit-note
```

### Request Body

**Opción 1: Re-reservar para Orden de Trabajo**
```json
{
  "work_order_id": 123
}
```

**Opción 2: Re-reservar para Cotización de Mesón**
```json
{
  "quotation_id": 456
}
```

**NOTA**: Debe proporcionar **SOLO UNO** (`work_order_id` O `quotation_id`), no ambos.

### Response Success

```json
{
  "success": true,
  "message": "Stock re-reservado exitosamente para OT OT-2026-07-0140",
  "work_order_id": 123,
  "correlative": "OT-2026-07-0140",
  "products_re_reserved": [
    {
      "product_id": 638,
      "product_name": "Filtro de Aceite ABC123",
      "quantity_re_reserved": 5.0,
      "stock_before": {
        "quantity": 100,
        "reserved": 10,
        "available": 90
      },
      "stock_after": {
        "quantity": 100,
        "reserved": 15,
        "available": 85
      }
    }
  ]
}
```

### Response Error: Stock Insuficiente

```json
{
  "success": false,
  "message": "No se pudo completar la re-reserva debido a errores en algunos productos",
  "errors": [
    {
      "product_id": 638,
      "product_name": "Filtro de Aceite ABC123",
      "quantity_required": 10,
      "available_quantity": 5,
      "error": "Stock disponible insuficiente para re-reservar"
    }
  ],
  "products_re_reserved": []
}
```

### Response Error: Ya Re-reservado

```json
{
  "success": false,
  "message": "Error al re-reservar stock",
  "error": "La OT OT-2026-07-0140 YA tiene stock re-reservado. No se puede re-reservar nuevamente."
}
```

### Response Error: No Tuvo NC

```json
{
  "success": false,
  "message": "Error al re-reservar stock",
  "error": "La OT OT-2026-07-0140 NO tiene nota de crédito registrada. No requiere re-reserva."
}
```

## 🔍 Validación Automática al Facturar

Cuando se intenta facturar una OT/Cotización que tuvo NC sin re-reservar, el sistema **BLOQUEA** la facturación:

### Endpoint de Validación

```
POST /api/ap/facturacion/electronic-documents/{id}/sync-accounting-status
```

### Response Error: Requiere Re-reserva

```json
{
  "message": "No se puede procesar el comprobante debido a problemas de inventario",
  "errors": [
    {
      "work_order_id": 123,
      "correlative": "OT-2026-07-0140",
      "error": "⚠️ Esta OT tuvo NOTA DE CRÉDITO y NO se ha re-reservado el stock. Debe ejecutar el endpoint POST /api/ap/postVenta/productWarehouseStock/re-reserve-after-credit-note con work_order_id=123 antes de volver a facturar."
    }
  ],
  "details": {
    "type": "work_order",
    "id": 123,
    "correlative": "OT-2026-07-0140",
    "requires_re_reservation": true,
    "products": []
  }
}
```

## 🔄 Flujo Completo: Factura → NC → Re-factura

### Paso 1: Factura Inicial Contabilizada

```
OT creada con repuestos:
  Producto X: quantity=100, reserved=15, available=85

Se factura (Job SyncAccountingStatusJob):
  → createSaleFromWorkOrder()
  → releaseReservedStockAndRemove(10)
  → reserved_quantity = 15 - 10 = 5
  → quantity = 100 - 10 = 90
  → available_quantity = 90 - 5 = 85

Estado: quantity=90, reserved=5, available=85
```

### Paso 2: Generar Nota de Crédito

```
Se genera NC (error en datos):
  → SyncAccountingStatusJob procesa NC
  → ApWorkOrderReversalService::reverseWorkOrderStatus()
  → createReturnMovementForWorkOrder()
  → addStock(10) via updateStockFromMovement()
  → quantity = 90 + 10 = 100
  → reserved_quantity = 5 (sin cambios)
  → available_quantity = 100 - 5 = 95
  → work_orders.had_credit_note = TRUE ✓
  → work_orders.stock_re_reserved = FALSE

Estado: quantity=100, reserved=5, available=95
Flags: had_credit_note=TRUE, stock_re_reserved=FALSE
```

### Paso 3: Intentar Re-facturar (SIN re-reservar)

```
POST /api/ap/facturacion/electronic-documents/{id}/sync-accounting-status

Validación detecta:
  - work_order.had_credit_note = TRUE
  - work_order.stock_re_reserved = FALSE
  ❌ BLOQUEA facturación

Response 422:
  "error": "⚠️ Esta OT tuvo NOTA DE CRÉDITO y NO se ha re-reservado el stock..."
```

### Paso 4: Re-reservar Stock Manualmente

```
POST /api/ap/postVenta/productWarehouseStock/re-reserve-after-credit-note
{
  "work_order_id": 123
}

Proceso:
  1. Valida: had_credit_note = TRUE ✓
  2. Valida: stock_re_reserved = FALSE ✓
  3. Por cada producto de la OT:
     - Valida: available_quantity >= quantity requerida
     - Incrementa: reserved_quantity += quantity
     - Recalcula: available_quantity = quantity - reserved_quantity
  4. Marca: work_orders.stock_re_reserved = TRUE

Estado después:
  Producto X: quantity=100, reserved=15, available=85
  Flags: had_credit_note=TRUE, stock_re_reserved=TRUE ✓
```

### Paso 5: Re-facturar (CON re-reserva)

```
POST /api/ap/facturacion/electronic-documents/{id}/sync-accounting-status

Validación detecta:
  - work_order.had_credit_note = TRUE
  - work_order.stock_re_reserved = TRUE ✓
  ✅ PERMITE facturación

Proceso normal:
  → createSaleFromWorkOrder()
  → releaseReservedStockAndRemove(10)
  → reserved_quantity = 15 - 10 = 5
  → quantity = 100 - 10 = 90
  → available_quantity = 90 - 5 = 85

Estado final: quantity=90, reserved=5, available=85 ✓
```

## 📊 Casos de Uso

### Caso 1: NC por Error en Datos → Re-facturar

**Escenario:**
- Facturaron OT con datos incorrectos
- Generan NC para corregir
- Van a volver a facturar con datos correctos

**Acción:**
1. Generan NC → `had_credit_note = TRUE`
2. Ejecutan endpoint re-reserva → `stock_re_reserved = TRUE`
3. Vuelven a facturar → ✓ Funciona correctamente

### Caso 2: NC Definitiva → NO Re-facturar

**Escenario:**
- Cliente cancela servicio definitivamente
- Generan NC
- NO van a volver a facturar

**Acción:**
1. Generan NC → `had_credit_note = TRUE`
2. **NO ejecutan re-reserva**
3. Stock queda disponible para otras operaciones → ✓ OK

### Caso 3: NC Parcial por Devolución de Ítems

**Escenario:**
- Cliente devuelve solo algunos productos
- Generan NC parcial
- No aplica re-reserva (ya está facturado el resto)

**Acción:**
- NC parcial NO marca `had_credit_note = TRUE` (solo para NC totales)
- Stock devuelto queda disponible → ✓ OK

## 🔧 Archivos Modificados/Creados

### Nuevos Archivos

1. **Migración**
   - `database/migrations/ap/postventa/2026_08_31_131515_add_credit_note_tracking_to_quotations_and_work_orders.php`

2. **Servicio**
   - `app/Http/Services/ap/postventa/gestionProductos/StockReReservationService.php`

3. **Documentación**
   - `CREDIT_NOTE_STOCK_RE_RESERVATION.md`

### Archivos Modificados

1. **Modelos**
   - `app/Models/ap/postventa/taller/ApOrderQuotations.php` (líneas 72-73)
   - `app/Models/ap/postventa/taller/ApWorkOrder.php` (líneas 84-85, 116-117)

2. **Servicios de Reversión**
   - `app/Http/Services/ap/postventa/taller/ApOrderQuotationsReversalService.php` (líneas 48-59)
   - `app/Http/Services/ap/postventa/taller/ApWorkOrderReversalService.php` (líneas 48-59)

3. **Validación de Inventario**
   - `app/Http/Services/ap/postventa/gestionProductos/InventoryOutputValidationService.php` (líneas 134-145, 210-221)

4. **Controller**
   - `app/Http/Controllers/ap/postventa/gestionProductos/ProductWarehouseStockController.php` (líneas 8, 211-267)

5. **Rutas**
   - `routes/api.php` (línea 1687)

## ⚙️ Ejecución de Migración

```bash
php artisan migrate --path=database/migrations/ap/postventa/2026_08_31_131515_add_credit_note_tracking_to_quotations_and_work_orders.php
```

## 🔍 Consultas SQL Útiles

### Ver OTs/Cotizaciones con NC sin re-reservar

```sql
-- Órdenes de Trabajo con NC sin re-reservar
SELECT
  id,
  correlative,
  sede_id,
  status_id,
  had_credit_note,
  stock_re_reserved,
  updated_at
FROM ap_work_orders
WHERE had_credit_note = TRUE
  AND stock_re_reserved = FALSE
  AND deleted_at IS NULL;

-- Cotizaciones con NC sin re-reservar
SELECT
  id,
  quotation_number,
  sede_id,
  status_id,
  had_credit_note,
  stock_re_reserved,
  updated_at
FROM ap_order_quotations
WHERE had_credit_note = TRUE
  AND stock_re_reserved = FALSE
  AND deleted_at IS NULL;
```

### Ver productos con discrepancias en reservas

```sql
-- Productos donde reserved_quantity no coincide con reservas reales
SELECT
  p.id,
  p.descripcion,
  pws.quantity,
  pws.reserved_quantity AS reserved_in_db,
  pws.available_quantity,
  -- Sumar reservas reales de OTs activas
  (SELECT COALESCE(SUM(wpp.quantity_used), 0)
   FROM ap_work_order_product_parts wpp
   JOIN ap_work_orders wo ON wo.id = wpp.work_order_id
   WHERE wpp.product_id = p.id
     AND wo.output_generation_warehouse = 0
     AND wo.status_id != (SELECT id FROM ap_masters WHERE code = 'ESTADO_OT_CERRADO')
     AND wo.deleted_at IS NULL
  ) +
  -- Sumar reservas reales de Cotizaciones activas (solo supply_type='STOCK')
  (SELECT COALESCE(SUM(oqd.quantity), 0)
   FROM ap_order_quotation_details oqd
   JOIN ap_order_quotations oq ON oq.id = oqd.quotation_id
   WHERE oqd.product_id = p.id
     AND oq.output_generation_warehouse = 0
     AND oqd.supply_type = 'STOCK'
     AND oq.deleted_at IS NULL
  ) AS expected_reserved
FROM products p
JOIN product_warehouse_stock pws ON pws.product_id = p.id
WHERE pws.warehouse_id = 167 -- Ajustar según almacén
HAVING reserved_in_db != expected_reserved;
```

## ⚠️ Notas Importantes

1. **Re-reserva es OPCIONAL**: Solo ejecutar cuando confirmen que SÍ van a re-facturar.

2. **Stock Insuficiente**: Si al momento de re-reservar no hay `available_quantity` suficiente, el endpoint retorna error parcial. Verificar y ajustar stock antes de re-reservar.

3. **Idempotencia**: No se puede re-reservar dos veces. Si ya se re-reservó (`stock_re_reserved = TRUE`), el endpoint retorna error.

4. **Tracking**: Los campos `had_credit_note` y `stock_re_reserved` permiten auditar y detectar casos pendientes.

5. **Notas de Crédito Parciales**: Actualmente, el flag `had_credit_note` se marca para NC totales. NC parciales NO marcan el flag (TODO: evaluar si es necesario para NC parciales).

6. **Compatible con Movimientos Simbólicos**: Este fix es COMPLEMENTARIO al fix de movimientos simbólicos (`SYMBOLIC_STOCK_MOVEMENTS.md`):
   - **Simbólicos**: Para anulaciones NO contabilizadas (CASO 2)
   - **Re-reserva**: Para NC de facturas YA contabilizadas

## 📚 Referencias

- `SYMBOLIC_STOCK_MOVEMENTS.md` - Fix de anulaciones no contabilizadas
- `INVENTORY_VALIDATION_API.md` - Validación pre-ejecución de inventario
- `app/Http/Services/ap/postventa/gestionProductos/InventoryMovementService.php` - Líneas 2332-2487 (createReturnMovementForQuotation)
- `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php` - Líneas 442-499 (updateStockFromMovement)

---

**Fecha de implementación**: 2026-08-31
**Versión**: 1.0.0
**Autor**: Claude (Anthropic) + Usuario
