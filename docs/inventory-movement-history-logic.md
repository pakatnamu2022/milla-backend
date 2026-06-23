# Lógica del Historial de Movimientos de Inventario y Costo Promedio Ponderado

## Índice
1. [Descripción General](#descripción-general)
2. [Tablas Involucradas](#tablas-involucradas)
3. [Configuraciones del Sistema](#configuraciones-del-sistema)
4. [Flujo de Negocio Completo](#flujo-de-negocio-completo)
5. [Lógica del Costo Promedio Ponderado](#lógica-del-costo-promedio-ponderado)
6. [Ejemplos Numéricos](#ejemplos-numéricos)
7. [Casos de Uso y Escenarios](#casos-de-uso-y-escenarios)
8. [Consideraciones Técnicas](#consideraciones-técnicas)

---

## Descripción General

El método `getStockMovementHistory()` en `ProductWarehouseStockService.php` reconstruye el historial completo de movimientos de inventario para un producto específico en un almacén, calculando el **costo promedio ponderado** y el **stock acumulado** después de cada movimiento.

**Ubicación:** `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`

**Propósito:**
- Mostrar cronológicamente todos los movimientos de entrada/salida de un producto
- Calcular el costo promedio ponderado después de cada movimiento de entrada
- Rastrear el stock disponible en tiempo real
- Permitir auditoría y trazabilidad de inventario

---

## Tablas Involucradas

### 1. **inventory_movements** (Tabla Principal)
Almacena todos los movimientos de inventario del sistema.

**Campos clave:**
- `id`: Identificador único
- `movement_number`: Número correlativo (ej: MOV-2026-0158)
- `movement_type`: Tipo de movimiento (PURCHASE_RECEPTION, RETURN_OUT, etc.)
- `movement_date`: Fecha del movimiento
- `warehouse_id`: Almacén donde ocurre el movimiento
- `status`: Estado (APPROVED, PENDING, etc.)
- `reference_type`: Tipo de documento relacionado (PurchaseReception, SupplierCreditNote, etc.)
- `reference_id`: ID del documento relacionado

**Relación:** Polimórfica con `reference_type` y `reference_id`

---

### 2. **inventory_movement_details** (Detalle de Movimientos)
Almacena los productos y cantidades de cada movimiento.

**Campos clave:**
- `inventory_movement_id`: FK a inventory_movements
- `product_id`: Producto afectado
- `quantity`: Cantidad del movimiento (SIEMPRE es la cantidad facturada, no la física)
- `unit_cost`: Costo unitario en moneda original

**IMPORTANTE:** El campo `quantity` en esta tabla SIEMPRE guarda la cantidad facturada (ej: 10), NO la cantidad física recibida (ej: 8). Esto es intencional para que cuadren los saldos fiscales cuando se emita una Nota de Crédito posterior.

---

### 3. **purchase_receptions** (Recepciones de Compra)
Registra las recepciones físicas de mercadería.

**Campos clave:**
- `id`: Identificador único
- `purchase_order_id`: FK a la orden de compra
- `reception_number`: Número de recepción
- `reception_date`: Fecha de recepción
- `status`: Estado (APPROVED, PENDING, REJECTED, ANNULLED)

**Relación:** Una recepción corresponde a una factura/orden de compra

---

### 4. **purchase_reception_details** (Detalle de Recepción)
**Esta es la tabla crítica que guarda las cantidades físicas reales.**

**Campos clave:**
- `purchase_reception_id`: FK a purchase_receptions
- `product_id`: Producto recibido
- `quantity_received`: **Cantidad física recibida en buen estado** (ej: 8)
- `observed_quantity`: Cantidad con observaciones/problemas (ej: 2)
- `is_credit_note`: Boolean que indica si la cantidad observada será devuelta (true) o entregada después (false)
- `reception_type`: ORDERED, BONUS, GIFT, SAMPLE
- `reason_observation`: Razón de la observación (DAMAGED, DEFECTIVE, EXPIRED, etc.)

**Lógica del campo `is_credit_note`:**
- `is_credit_note = true`: La cantidad observada será devuelta mediante una Nota de Crédito
- `is_credit_note = false`: La cantidad observada será entregada posteriormente por el proveedor

---

### 5. **purchase_orders** (Órdenes de Compra)
**Campos clave:**
- `id`: Identificador único
- `number`: Número de OC (ej: OC-2026-001)
- `status`: Boolean (0 = inactiva, 1 = activa)
- `supplier_id`: Proveedor

---

### 6. **supplier_credit_notes** (Notas de Crédito de Proveedor)
Registra las devoluciones/descuentos de proveedores.

**Campos clave:**
- `id`: Identificador único
- `credit_note_number`: Número de NC
- `purchase_order_id`: FK a purchase_orders (puede ser NULL)
- `purchase_reception_id`: FK a purchase_receptions (puede ser NULL)
- `supplier_id`: Proveedor
- `status`: STRING (DRAFT, PENDING_APPROVAL, APPROVED, APPLIED, REJECTED, CANCELLED)
- `credit_note_date`: Fecha de emisión
- `total`: Monto total

---

### 7. **products** (Productos)
Catálogo de productos.

---

### 8. **warehouses** (Almacenes)
Catálogo de almacenes.

---

### 9. **type_currencies** (Monedas)
Catálogo de monedas (USD, PEN, etc.).

---

## Configuraciones del Sistema

El servicio tiene **dos switches de configuración** que controlan el comportamiento del historial:

### 1. `INCLUDE_RETURN_OUT_IN_HISTORY`

**Ubicación:** Línea 28 en `ProductWarehouseStockService.php`

```php
const INCLUDE_RETURN_OUT_IN_HISTORY = 0; // o 1
```

**Valores:**
- `0`: NO incluir movimientos de tipo RETURN_OUT en el historial
- `1`: SÍ incluir movimientos de tipo RETURN_OUT en el historial

**Propósito:**
Controla si las devoluciones a proveedores (Notas de Crédito) deben aparecer como movimientos separados en el historial de inventario.

**Filtros aplicados cuando está en 1:**
Solo se incluyen movimientos RETURN_OUT que cumplan:
1. `SupplierCreditNote.status = 'APPROVED'`
2. `SupplierCreditNote.purchase_order_id IS NOT NULL`
3. `PurchaseOrder.status = true` (orden de compra activa)

---

### 2. `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST`

**Ubicación:** Línea 35 en `ProductWarehouseStockService.php`

```php
const USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true; // o false
```

**Valores:**
- `true`: Usar `quantity_received` (cantidad física recibida en buen estado) para el cálculo del costo promedio
- `false`: Usar la cantidad facturada total (quantity_received + observed_quantity)

**Propósito:**
Define qué cantidad usar para calcular el costo promedio ponderado en recepciones de compra.

**Impacto:**
- `true` → Cálculo basado en inventario físico real (recomendado para gestión de almacén)
- `false` → Cálculo basado en facturación (recomendado para contabilidad fiscal)

---

## Flujo de Negocio Completo

### Escenario Típico: Recepción con Observaciones

**1. Orden de Compra**
```
Orden: OC-2026-001
Proveedor: ABC Corp
Producto: Widget XYZ
Cantidad ordenada: 10 unidades
Precio unitario: USD 60.00
Total factura: USD 600.00
```

**2. Recepción Física**
Al recibir la mercadería, el almacenero inspecciona:
```
Cantidad recibida en buen estado: 8 unidades
Cantidad con observaciones: 2 unidades
Razón: DAMAGED (dañadas)
is_credit_note: true (se devolverán)
```

**Registro en `purchase_reception_details`:**
```
product_id: 123
quantity_received: 8
observed_quantity: 2
is_credit_note: true
reason_observation: DAMAGED
```

**3. Creación del Movimiento de Inventario**
Se crea un registro en `inventory_movements`:
```
movement_type: PURCHASE_RECEPTION
movement_number: MOV-2026-0150
status: APPROVED
reference_type: PurchaseReception
reference_id: 456
```

**Registro en `inventory_movement_details`:**
```
product_id: 123
quantity: 10  ← SIEMPRE la cantidad facturada (8 + 2)
unit_cost: 60.00
```

**¿Por qué se guarda 10 y no 8?**
Porque la factura dice 10. Si se guardara 8, cuando luego se emita la Nota de Crédito por 2 unidades, el saldo no cuadraría fiscalmente.

**4. Cálculo en el Historial (según configuración)**

**Con `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true`:**
```
Cantidad para cálculo: 8 (quantity_received)
Stock incrementa: +8
Costo promedio se calcula con: 8 unidades × USD 60.00
```

**Con `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = false`:**
```
Cantidad para cálculo: 10 (cantidad facturada)
Stock incrementa: +10
Costo promedio se calcula con: 10 unidades × USD 60.00
```

**5. Emisión de Nota de Crédito (Devolución)**
Posteriormente, el proveedor emite una NC por las 2 unidades dañadas:
```
credit_note_number: NC-2026-005
purchase_order_id: (ID de OC-2026-001)
purchase_reception_id: 456
supplier_id: ABC Corp
status: APPROVED
total: USD 120.00 (2 × 60.00)
```

Se crea un movimiento RETURN_OUT:
```
movement_type: RETURN_OUT
movement_number: MOV-2026-0152
status: APPROVED
reference_type: SupplierCreditNote
reference_id: 789
```

**Con `INCLUDE_RETURN_OUT_IN_HISTORY = 1`:**
El historial mostrará:
```
MOV-2026-0150  Recepción de Compra      +8 (o +10)
MOV-2026-0152  Devolución a Proveedor   -2
```

**Con `INCLUDE_RETURN_OUT_IN_HISTORY = 0`:**
El historial solo mostrará:
```
MOV-2026-0150  Recepción de Compra      +8 (o +10)
```

---

## Lógica del Costo Promedio Ponderado

### Fórmula

```
Nuevo Costo Promedio = (Stock Anterior × Costo Promedio Anterior + Cantidad Entrada × Costo Unitario Entrada) / (Stock Anterior + Cantidad Entrada)
```

### Proceso Paso a Paso

1. **Obtener movimientos ordenados cronológicamente**
   - Solo movimientos con `status = APPROVED`
   - Filtrados por producto y almacén
   - Ordenados por `movement_date ASC`

2. **Para cada movimiento:**

   **a. Determinar si es INBOUND (entrada) o OUTBOUND (salida)**
   ```php
   $isInbound = in_array($movement->movement_type, [
     InventoryMovement::TYPE_PURCHASE_RECEPTION,
     InventoryMovement::TYPE_ADJUSTMENT_IN,
     InventoryMovement::TYPE_TRANSFER_IN,
   ]);
   ```

   **b. Obtener cantidad del movimiento**
   ```php
   $quantity = (float)$detail->quantity; // Cantidad facturada
   ```

   **c. Si es PURCHASE_RECEPTION y USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true**
   ```php
   // Buscar la cantidad física recibida
   $receptionDetail = PurchaseReceptionDetail::where('purchase_reception_id', $movement->reference->id)
     ->where('product_id', $productId)
     ->first();

   if ($receptionDetail) {
     $quantityForAverageCost = (float)$receptionDetail->quantity_received; // Ej: 8
   }
   ```

   **d. Si es INBOUND: Calcular nuevo costo promedio**
   ```php
   if ($unitCostInPEN > 0) {
     $stockBeforeMovement = $runningStock;
     $runningStock += $quantityForAverageCost;

     // Aplicar fórmula de promedio ponderado
     if ($stockBeforeMovement + $quantityForAverageCost > 0) {
       $runningAverageCost = (
         ($stockBeforeMovement * $runningAverageCost) +
         ($quantityForAverageCost * $unitCostInPEN)
       ) / ($stockBeforeMovement + $quantityForAverageCost);

       $runningAverageCost = round($runningAverageCost, 2);
     } else {
       $runningAverageCost = $unitCostInPEN;
     }
   }
   ```

   **e. Si es OUTBOUND: Solo reducir stock (no afecta costo promedio)**
   ```php
   $runningStock -= $quantity;
   if ($runningStock < 0) {
     $runningStock = 0; // Safety check
   }
   ```

3. **Agregar al historial**
   ```php
   $history[] = [
     'movement_id' => $movement->id,
     'movement_date' => $movement->movement_date->format('Y-m-d'),
     'movement_number' => $movement->movement_number,
     'movement_type' => $movement->movement_type,
     'movement_type_label' => 'Recepción de Compra',
     'is_inbound' => true,
     'quantity' => $quantityForAverageCost, // 8 o 10 según configuración
     'unit_cost' => 60.00,
     'unit_cost_in_pen' => 203.16, // 60.00 × 3.386
     'total_cost' => 1625.28, // 8 × 203.16
     'stock_after_movement' => 11,
     'average_cost_after_movement' => 199.01,
     'currency' => 'USD',
     'exchange_rate' => 3.386,
   ];
   ```

---

## Ejemplos Numéricos

### Ejemplo 1: Cálculo Simple sin Observaciones

**Stock inicial:** 0 unidades, Costo Promedio: S/ 0.00

**Movimiento 1:** Recepción de Compra
```
Fecha: 2026-06-01
Cantidad: 3 unidades
Costo: USD 55.00
TC: 3.417
Costo en PEN: S/ 187.94 (55.00 × 3.417)
```

**Cálculo:**
```
Stock anterior: 0
Costo promedio anterior: 0
Cantidad entrada: 3
Costo entrada: 187.94

Nuevo costo promedio = (0 × 0 + 3 × 187.94) / (0 + 3)
                     = 563.82 / 3
                     = S/ 187.94

Stock después: 3 unidades
Costo promedio: S/ 187.94
```

**Historial:**
```
2026-06-01  MOV-2026-0158  Recepción de Compra  +3  USD 55.00 (×3.417)  S/ 563.82  3 und.  S/ 187.94
```

---

### Ejemplo 2: Con Configuración `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true`

**Stock inicial:** 3 unidades, Costo Promedio: S/ 187.94

**Movimiento 2:** Recepción con Observaciones
```
Fecha: 2026-06-06
Factura: 10 unidades
Cantidad recibida (buen estado): 8 unidades
Cantidad observada: 2 unidades (dañadas, is_credit_note = true)
Costo: USD 60.00
TC: 3.386
Costo en PEN: S/ 203.16 (60.00 × 3.386)
```

**Cálculo:**
```
Stock anterior: 3
Costo promedio anterior: 187.94
Cantidad entrada: 8 (quantity_received, no los 10 de la factura)
Costo entrada: 203.16

Nuevo costo promedio = (3 × 187.94 + 8 × 203.16) / (3 + 8)
                     = (563.82 + 1625.28) / 11
                     = 2189.10 / 11
                     = S/ 199.01

Stock después: 11 unidades (3 + 8)
Costo promedio: S/ 199.01
```

**Historial:**
```
2026-06-01  MOV-2026-0158  Recepción de Compra  +3   USD 55.00 (×3.417)  S/ 563.82   3 und.  S/ 187.94
2026-06-06  MOV-2026-0150  Recepción de Compra  +8   USD 60.00 (×3.386)  S/ 2031.60  11 und. S/ 199.01
```

**NOTA:** Se suma +8, no +10, porque `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true`

---

### Ejemplo 3: Con Configuración `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = false`

**Mismo escenario, pero con el switch en `false`:**

**Cálculo:**
```
Stock anterior: 3
Costo promedio anterior: 187.94
Cantidad entrada: 10 (cantidad facturada completa)
Costo entrada: 203.16

Nuevo costo promedio = (3 × 187.94 + 10 × 203.16) / (3 + 10)
                     = (563.82 + 2031.60) / 13
                     = 2595.42 / 13
                     = S/ 199.65

Stock después: 13 unidades (3 + 10)
Costo promedio: S/ 199.65
```

**Historial:**
```
2026-06-01  MOV-2026-0158  Recepción de Compra  +3   USD 55.00 (×3.417)  S/ 563.82   3 und.  S/ 187.94
2026-06-06  MOV-2026-0150  Recepción de Compra  +10  USD 60.00 (×3.386)  S/ 2031.60  13 und. S/ 199.65
```

**Más tarde:** Devolución (Nota de Crédito)
```
2026-06-06  MOV-2026-0152  Devolución a Proveedor  -2  S/ 60.00  11 und.  S/ 199.65
```

**Resultado final:** 11 unidades con costo promedio S/ 199.65

---

### Ejemplo 4: Con `INCLUDE_RETURN_OUT_IN_HISTORY = 1`

**Historial completo incluyendo la devolución:**
```
2026-06-01  MOV-2026-0158  Recepción de Compra      +3   USD 55.00 (×3.417)  S/ 563.82   3 und.  S/ 187.94
2026-06-06  MOV-2026-0150  Recepción de Compra      +8   USD 60.00 (×3.386)  S/ 2031.60  11 und. S/ 199.01
2026-06-06  MOV-2026-0152  Devolución a Proveedor   -2   S/ 60.00            —           9 und.  S/ 199.01
```

**NOTA:** La devolución NO cambia el costo promedio, solo reduce el stock.

---

## Casos de Uso y Escenarios

### Caso 1: Solo Gestión de Almacén (Inventario Físico)
**Configuración recomendada:**
```php
const INCLUDE_RETURN_OUT_IN_HISTORY = 0;
const USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true;
```

**Razón:**
- El historial muestra solo lo que físicamente ingresó al almacén (8 unidades)
- No muestra devoluciones como movimientos separados
- El costo promedio refleja solo mercadería disponible

---

### Caso 2: Gestión Fiscal/Contable
**Configuración recomendada:**
```php
const INCLUDE_RETURN_OUT_IN_HISTORY = 1;
const USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = false;
```

**Razón:**
- El historial muestra la cantidad facturada (10 unidades)
- Las devoluciones aparecen como movimientos separados
- Los saldos cuadran con documentos fiscales (facturas y NC)

---

### Caso 3: Gestión Mixta (Recomendado)
**Configuración recomendada:**
```php
const INCLUDE_RETURN_OUT_IN_HISTORY = 0;
const USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true;
```

**Razón:**
- El historial refleja inventario físico real
- No duplica información (la devolución ya está implícita en quantity_received)
- Costo promedio basado en mercadería disponible
- Más simple de entender para usuarios de almacén

---

## Consideraciones Técnicas

### 1. Relaciones Polimórficas
```php
InventoryMovement::morphTo('reference')
```
Permite que `reference_type` y `reference_id` apunten a diferentes modelos:
- `PurchaseReception::class`
- `SupplierCreditNote::class`
- `SaleOrder::class`
- etc.

---

### 2. Filtros de Estado
Solo se procesan movimientos con:
```php
->where('status', InventoryMovement::STATUS_APPROVED)
```

Para PURCHASE_RECEPTION:
```php
->whereHasMorph('reference', [PurchaseReception::class], function ($morphQuery) {
  $morphQuery->where('status', 'APPROVED');
})
```

Para RETURN_OUT (si está habilitado):
```php
->whereHasMorph('reference', [SupplierCreditNote::class], function ($morphQuery) {
  $morphQuery->where('status', SupplierCreditNote::STATUS_APPROVED)
    ->whereNotNull('purchase_order_id')
    ->whereHas('purchaseOrder', function ($poQuery) {
      $poQuery->where('status', true); // PurchaseOrder activa
    });
})
```

---

### 3. Conversión de Moneda
```php
if ($movement->currency_id && $movement->currency->code !== 'PEN') {
  $exchangeRate = $movement->exchange_rate ?? 1;
  $unitCostInPEN = $detail->unit_cost * $exchangeRate;
} else {
  $unitCostInPEN = $detail->unit_cost;
}
```

---

### 4. Orden Cronológico
```php
->orderBy('movement_date', 'ASC')
->orderBy('id', 'ASC')
```
Esencial para calcular correctamente el costo promedio ponderado progresivo.

---

### 5. Validación de Stock Negativo
```php
if ($runningStock < 0) {
  $runningStock = 0; // Safety check
}
```
Previene saldos negativos por errores de datos.

---

### 6. Redondeo de Costos
```php
$runningAverageCost = round($runningAverageCost, 2);
```
Mantiene precisión decimal consistente.

---

## Resumen de Flujo de Datos

```
┌─────────────────────────────────────────────────────────────────┐
│                    ORDEN DE COMPRA (OC)                         │
│  - Cantidad ordenada: 10 unidades                               │
│  - Precio unitario: USD 60.00                                   │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              RECEPCIÓN FÍSICA (PurchaseReception)               │
│  purchase_reception_details:                                    │
│    - quantity_received: 8 (buen estado)                         │
│    - observed_quantity: 2 (dañadas)                             │
│    - is_credit_note: true (se devolverán)                       │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│         MOVIMIENTO DE INVENTARIO (InventoryMovement)            │
│  - movement_type: PURCHASE_RECEPTION                            │
│  - reference_type: PurchaseReception                            │
│                                                                 │
│  inventory_movement_details:                                    │
│    - quantity: 10 (cantidad FACTURADA)                          │
│    - unit_cost: 60.00                                           │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│           HISTORIAL (getStockMovementHistory)                   │
│                                                                 │
│  Si USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true:              │
│    ✓ Cantidad mostrada: 8                                       │
│    ✓ Cantidad para cálculo: 8                                   │
│    ✓ Stock incrementa: +8                                       │
│                                                                 │
│  Si USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = false:             │
│    ✓ Cantidad mostrada: 10                                      │
│    ✓ Cantidad para cálculo: 10                                  │
│    ✓ Stock incrementa: +10                                      │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            │ (Posteriormente)
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│     NOTA DE CRÉDITO (SupplierCreditNote) - Si es necesario     │
│  - status: APPROVED                                             │
│  - purchase_order_id: (ID de la OC)                             │
│  - Cantidad devuelta: 2                                         │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│    MOVIMIENTO RETURN_OUT (Si INCLUDE_RETURN_OUT_IN_HISTORY=1)   │
│  - movement_type: RETURN_OUT                                    │
│  - Cantidad: -2                                                 │
│  - NO afecta costo promedio                                     │
│  - Solo reduce stock                                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## Preguntas Frecuentes (FAQ)

### ¿Por qué `inventory_movement_details.quantity` guarda 10 y no 8?

Porque ese campo representa la cantidad **facturada** (fiscal), no la física. Al guardar 10, cuando se emita la Nota de Crédito por 2 unidades, el saldo fiscal cuadrará correctamente: 10 - 2 = 8.

---

### ¿Cuándo usar `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true`?

Cuando quieras que el costo promedio refleje solo la mercadería disponible físicamente en el almacén. Es más útil para operaciones de almacén y logística.

---

### ¿Cuándo usar `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = false`?

Cuando necesites que el historial cuadre con documentos fiscales (facturas y notas de crédito). Es más útil para contabilidad y auditoría.

---

### ¿Por qué las devoluciones no afectan el costo promedio?

Porque el costo promedio ponderado solo se recalcula en movimientos de **entrada** (INBOUND). Las salidas (OUTBOUND) solo reducen el stock pero mantienen el costo promedio existente.

---

### ¿Qué pasa si `INCLUDE_RETURN_OUT_IN_HISTORY = 0`?

Las devoluciones no aparecen como líneas separadas en el historial. Esto es útil cuando `USE_RECEIVED_QUANTITY_FOR_AVERAGE_COST = true`, porque la devolución ya está "implícita" (solo se sumaron 8, no 10).

---

### ¿Qué pasa si hay descuadre entre factura y recepción?

El sistema lo maneja así:
- Si `is_credit_note = true`: Se emitirá NC y se devolverá la diferencia
- Si `is_credit_note = false`: El proveedor enviará la diferencia después
- El campo `observed_quantity` siempre guarda la diferencia
- El campo `reason_observation` documenta el motivo

---

## Mantenimiento y Extensiones Futuras

### Si necesitas agregar un nuevo tipo de movimiento:

1. **Definir constante en InventoryMovement:**
   ```php
   const TYPE_NEW_MOVEMENT = 'NEW_MOVEMENT';
   ```

2. **Determinar si es INBOUND u OUTBOUND:**
   ```php
   $isInbound = in_array($movement->movement_type, [
     InventoryMovement::TYPE_PURCHASE_RECEPTION,
     InventoryMovement::TYPE_NEW_MOVEMENT, // Agregar aquí si es entrada
   ]);
   ```

3. **Agregar label en el historial:**
   ```php
   $movementTypeLabel = match ($movement->movement_type) {
     InventoryMovement::TYPE_PURCHASE_RECEPTION => 'Recepción de Compra',
     InventoryMovement::TYPE_NEW_MOVEMENT => 'Tu Nuevo Movimiento',
     ...
   };
   ```

---

### Si necesitas un nuevo switch de configuración:

1. **Agregar constante:**
   ```php
   const NEW_CONFIGURATION = true;
   ```

2. **Aplicar lógica condicional:**
   ```php
   if (self::NEW_CONFIGURATION === true) {
     // Tu lógica especial
   }
   ```

---

## Conclusión

Este sistema está diseñado para:
✓ Flexibilidad: Dos configuraciones independientes para diferentes necesidades
✓ Trazabilidad: Historial completo cronológico de movimientos
✓ Precisión: Cálculo correcto de costo promedio ponderado
✓ Integridad: Cuadre entre documentos fiscales e inventario físico
✓ Auditoría: Todos los movimientos quedan registrados con su referencia original

**Ubicación del código:** `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`

**Método principal:** `getStockMovementHistory($productId, $warehouseId)`

---

**Última actualización:** 2026-06-23
**Autor:** Sistema de Gestión de Inventario Milla Backend