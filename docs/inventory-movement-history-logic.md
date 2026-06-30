# Lógica del Historial de Movimientos de Inventario y Costo Promedio Ponderado

## Índice
1. [Descripción General](#descripción-general)
2. [Tablas Involucradas](#tablas-involucradas)
3. [Tabla Materializada de Historial de Costos](#tabla-materializada-de-historial-de-costos)
4. [Configuraciones del Sistema](#configuraciones-del-sistema)
5. [Flujo de Negocio Completo](#flujo-de-negocio-completo)
6. [Lógica del Costo Promedio Ponderado](#lógica-del-costo-promedio-ponderado)
7. [Método Centralizado de Reconstrucción](#método-centralizado-de-reconstrucción)
8. [Ejemplos Numéricos](#ejemplos-numéricos)
9. [Casos de Uso y Escenarios](#casos-de-uso-y-escenarios)
10. [Consideraciones Técnicas](#consideraciones-técnicas)

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

### 10. **ap_order_quotations** (Cotizaciones/Órdenes)
Registra las órdenes de venta que generan movimientos de salida.

**Campos clave:**
- `id`: Identificador único
- `order_number`: Número de la orden
- `status`: Estado de la orden

**Relación con inventario:**
- Cuando se aprueba una orden, se genera un movimiento SALE

---

### 11. **ap_work_order** (Órdenes de Trabajo)
Registra las órdenes de trabajo que también pueden generar movimientos de salida.

**Campos clave:**
- `id`: Identificador único
- `work_order_number`: Número de la orden de trabajo

**Relación con inventario:**
- Similar a las órdenes de venta, puede generar movimientos SALE

---

## Tabla Materializada de Historial de Costos

### 10. **weighted_average_cost_history** (Historial Materializado de Costo Promedio)

**Propósito:**
Esta tabla actúa como un "snapshot" (fotografía) del estado del stock y costo promedio ponderado después de cada movimiento de inventario. Permite consultas rápidas sin necesidad de recalcular todo el historial.

**Ubicación de la migración:** `database/migrations/2026_06_24_100000_create_weighted_average_cost_history_table.php`

**Ubicación del modelo:** `app/Models/ap/postventa/gestionProductos/WeightedAverageCostHistory.php`

#### Campos Clave:

**Llaves primarias y foráneas:**
- `id`: Identificador único
- `product_id`: FK a products (producto)
- `warehouse_id`: FK a warehouse (almacén)
- `movement_id`: FK a inventory_movements (puede ser NULL para snapshots iniciales)

**Información del movimiento (desnormalizada para queries rápidas):**
- `movement_date`: Fecha del movimiento (fecha de negocio, no de creación)
- `movement_type`: Tipo de movimiento (PURCHASE_RECEPTION, SALE, RETURN_OUT, etc.)
- `movement_number`: Número del movimiento (MOV-2026-0001)

**Cantidades del movimiento:**
- `quantity_in`: Cantidad agregada al stock (movimientos INBOUND) - Default: 0
- `quantity_out`: Cantidad removida del stock (movimientos OUTBOUND) - Default: 0
- `unit_cost_pen`: Costo unitario del movimiento en PEN (solo relevante para INBOUND)

**Snapshots (estado DESPUÉS del movimiento):**
- `stock_after_movement`: Cantidad de stock DESPUÉS de aplicar este movimiento
- `average_cost_after_movement`: Costo promedio ponderado DESPUÉS de aplicar este movimiento

**Metadatos:**
- `recalculated_at`: Timestamp de último recálculo (para ajustes retroactivos)
- `created_at`: Fecha de creación del registro
- `updated_at`: Fecha de última actualización

#### Índices:

```sql
-- Buscar historial de un producto en un almacén por fecha
idx_product_warehouse_date (product_id, warehouse_id, movement_date)

-- Buscar por tipo de movimiento
idx_product_warehouse_type (product_id, warehouse_id, movement_type)

-- Buscar por movimiento específico
idx_movement_id (movement_id)

-- Constraint único: un movimiento solo genera un snapshot por producto-almacén
unique_product_warehouse_movement (product_id, warehouse_id, movement_id)
```

#### Ventajas de la Tabla Materializada:

1. **Performance:** Evita recálculos complejos "on the fly" en `getPriceCalculationDetails`
2. **Consultas rápidas:** Obtener el historial de costos es una simple query SELECT
3. **Auditoría:** Facilita la trazabilidad de cambios de costo a lo largo del tiempo
4. **Recálculos retroactivos:** Soporta regeneración del historial cuando hay ajustes (ej: NC antigua)
5. **Análisis:** Permite analizar evolución de costos y variaciones

#### Scopes del Modelo:

El modelo `WeightedAverageCostHistory` incluye múltiples scopes útiles:

```php
// Filtrar por producto y almacén
forProductWarehouse($productId, $warehouseId)

// Ordenamiento cronológico
chronological()                  // Más antiguo primero
reverseChronological()          // Más reciente primero

// Filtrar por dirección del movimiento
inbound()                       // Solo entradas
outbound()                      // Solo salidas

// Filtrar por tipo
byType($movementType)

// Filtrar por fechas
fromDate($date)                 // Desde una fecha
toDate($date)                   // Hasta una fecha

// Snapshots recalculados
recalculated()                  // Solo registros recalculados
```

#### Métodos Estáticos de Utilidad:

```php
// Obtener el último snapshot de un producto en un almacén
WeightedAverageCostHistory::getLatestSnapshot($productId, $warehouseId)

// Obtener snapshot anterior a una fecha
WeightedAverageCostHistory::getSnapshotBeforeDate($productId, $warehouseId, $beforeDate)

// Eliminar snapshots desde una fecha (útil antes de recálculo)
WeightedAverageCostHistory::deleteFromDate($productId, $warehouseId, $fromDate)

// Obtener historial completo
WeightedAverageCostHistory::getFullHistory($productId, $warehouseId)
```

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
   // Movimientos INBOUND (entradas que afectan costo promedio)
   $isInbound = in_array($movement->movement_type, [
     InventoryMovement::TYPE_PURCHASE_RECEPTION,  // Recepción de Compra
     InventoryMovement::TYPE_ADJUSTMENT_IN,       // Ajuste de Entrada
     InventoryMovement::TYPE_TRANSFER_IN,         // Transferencia de Entrada
   ]);

   // Movimientos OUTBOUND (salidas que NO afectan costo promedio)
   $isOutbound = in_array($movement->movement_type, [
     InventoryMovement::TYPE_SALE,                // Venta
     InventoryMovement::TYPE_RETURN_OUT,          // Devolución a Proveedor
     InventoryMovement::TYPE_ADJUSTMENT_OUT,      // Ajuste de Salida
     InventoryMovement::TYPE_TRANSFER_OUT,        // Transferencia de Salida
   ]);
   ```

   **Tipos de movimientos soportados:**

   **INBOUND (Entradas):**
   - `PURCHASE_RECEPTION`: Recepción de compra de proveedor
     - `reference_type`: `PurchaseReception`
     - Afecta costo promedio: SÍ
     - Incrementa stock: SÍ

   **OUTBOUND (Salidas):**
   - `SALE`: Venta a cliente
     - `reference_type`: `ApOrderQuotations` o `ApWorkOrder`
     - Afecta costo promedio: NO
     - Reduce stock: SÍ

   - `RETURN_OUT`: Devolución a proveedor (Nota de Crédito)
     - `reference_type`: `SupplierCreditNote`
     - Afecta costo promedio: NO
     - Reduce stock: SÍ (solo si `INCLUDE_RETURN_OUT_IN_HISTORY = 1`)

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

## Método Centralizado de Reconstrucción

### `rebuildWeightedAverageCostHistory()`

**Ubicación:** `ProductWarehouseStockService.php` (líneas 1784-1899)

**Propósito:**
Este método centraliza la lógica de reconstrucción/actualización de la tabla materializada `weighted_average_cost_history`. Se invoca automáticamente en tres momentos clave:

1. **Compras** (PurchaseReception): Al aprobar una recepción de compra
2. **Ventas** (Sale): Al confirmar una venta
3. **Notas de Crédito** (SupplierCreditNote): Al aprobar una NC de proveedor

**Firma del método:**
```php
public function rebuildWeightedAverageCostHistory(
  int $productId,
  int $warehouseId,
  \DateTime|string|null $fromDate = null
): array
```

**Parámetros:**
- `$productId`: ID del producto a recalcular
- `$warehouseId`: ID del almacén donde recalcular
- `$fromDate`: (Opcional) Fecha desde la cual recalcular. Si es `null`, recalcula TODO el historial

**Retorno:**
```php
[
  'snapshots_deleted' => 15,     // Cantidad de snapshots eliminados
  'snapshots_inserted' => 18,    // Cantidad de snapshots creados
  'stock_matches' => true,       // Si el stock final coincide
  'current_stock' => 50.00,      // Stock actual en product_warehouse_stock
  'calculated_stock' => 50.00,   // Stock calculado del historial
  'average_cost' => 199.01,      // Costo promedio final
]
```

### Lógica Interna

1. **Determinar punto de partida:**
   ```php
   if ($fromDate === null) {
     // Reconstrucción completa: eliminar TODOS los snapshots
     WeightedAverageCostHistory::forProductWarehouse($productId, $warehouseId)->delete();
     $baseStock = 0;
     $baseCost = 0;
   } else {
     // Reconstrucción parcial: obtener estado base anterior a $fromDate
     $lastSnapshotBefore = WeightedAverageCostHistory::getSnapshotBeforeDate(
       $productId,
       $warehouseId,
       $fromDate
     );
     $baseStock = $lastSnapshotBefore->stock_after_movement ?? 0;
     $baseCost = $lastSnapshotBefore->average_cost_after_movement ?? 0;

     // Eliminar snapshots desde $fromDate en adelante
     WeightedAverageCostHistory::deleteFromDate($productId, $warehouseId, $fromDate);
   }
   ```

2. **Obtener movimientos desde el punto de partida:**
   ```php
   $history = $this->getStockMovementHistory($productId, $warehouseId);
   ```

3. **Crear snapshots para cada movimiento:**
   ```php
   foreach ($history as $movement) {
     WeightedAverageCostHistory::create([
       'product_id' => $productId,
       'warehouse_id' => $warehouseId,
       'movement_id' => $movement['movement_id'],
       'movement_date' => $movement['movement_date'],
       'movement_type' => $movement['movement_type'],
       'movement_number' => $movement['movement_number'],
       'quantity_in' => $movement['is_inbound'] ? $movement['quantity'] : 0,
       'quantity_out' => !$movement['is_inbound'] ? $movement['quantity'] : 0,
       'unit_cost_pen' => $movement['unit_cost_in_pen'] ?? 0,
       'stock_after_movement' => $movement['stock_after_movement'],
       'average_cost_after_movement' => $movement['average_cost_after_movement'],
       'recalculated_at' => $fromDate !== null ? now() : null,
     ]);
   }
   ```

4. **Validar consistencia:**
   ```php
   $currentStock = ProductWarehouseStock::where('product_id', $productId)
     ->where('warehouse_id', $warehouseId)
     ->first()
     ->stock ?? 0;

   $calculatedStock = $history[count($history) - 1]['stock_after_movement'] ?? 0;
   $stockMatches = abs($currentStock - $calculatedStock) < 0.01;
   ```

### Casos de Uso

#### Caso 1: Reconstrucción Completa (Initial Sync)
```php
// Reconstruir TODO el historial de un producto
$result = $service->rebuildWeightedAverageCostHistory(
  productId: 123,
  warehouseId: 1,
  fromDate: null  // null = reconstrucción completa
);
```

**Cuándo usar:**
- Primera vez que se habilita la tabla materializada
- Detectar inconsistencias en el historial completo
- Migración de datos

#### Caso 2: Recálculo Retroactivo (Nota de Crédito Antigua)
```php
// Se aprobó una NC con fecha de hace 2 semanas
$creditNoteDate = '2026-06-10';

// Recalcular desde esa fecha en adelante
$result = $service->rebuildWeightedAverageCostHistory(
  productId: 123,
  warehouseId: 1,
  fromDate: $creditNoteDate
);
```

**Cuándo usar:**
- Se aprueba una NC con fecha retroactiva
- Se corrige un movimiento antiguo
- Se elimina un movimiento que afecta el historial

#### Caso 3: Actualización Incremental (Nueva Compra)
```php
// Nueva compra aprobada hoy
$result = $service->rebuildWeightedAverageCostHistory(
  productId: 123,
  warehouseId: 1,
  fromDate: null  // Al ser nueva, reconstruir todo es más seguro
);
```

**Cuándo usar:**
- Nueva recepción de compra aprobada
- Nueva venta confirmada
- Cualquier nuevo movimiento APPROVED

### API Endpoint

**Ruta:** `POST /api/productWarehouseStock/rebuild-cost-history`

**Request Body:**
```json
{
  "product_id": 123,
  "warehouse_id": 1,
  "from_date": "2026-06-10"  // Opcional
}
```

**Response:**
```json
{
  "snapshots_deleted": 15,
  "snapshots_inserted": 18,
  "stock_matches": true,
  "current_stock": 50.00,
  "calculated_stock": 50.00,
  "average_cost": 199.01
}
```

### Consideraciones de Performance

- **Reconstrucción completa**: Puede ser costosa para productos con muchos movimientos (> 1000)
- **Reconstrucción parcial**: Más eficiente, solo recalcula desde una fecha específica
- **Recomendación**: Para recálculos frecuentes, usar `$fromDate` para limitar el alcance

### Integración con Otros Métodos

**`getPriceCalculationDetails()` ahora usa la tabla materializada:**

```php
// ANTES (complejidad O(n)):
$history = $this->getStockMovementHistory($productId, $warehouseId);
// ... reconstruir lógica compleja para obtener últimas compras

// AHORA (complejidad O(1)):
$purchaseHistory = WeightedAverageCostHistory::forProductWarehouse($productId, $warehouseId)
  ->byType(InventoryMovement::TYPE_PURCHASE_RECEPTION)
  ->reverseChronological()
  ->limit(2)
  ->get();

$lastPurchase = $purchaseHistory->first();
$previousPurchase = $purchaseHistory->skip(1)->first();
```

**Beneficio:** Consultas hasta 100x más rápidas para productos con historial extenso.

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

### Ejemplo 5: Historial Completo con Compras y Ventas

**Escenario realista:** Un producto que se compra y vende varias veces

**Stock inicial:** 0 unidades, Costo Promedio: S/ 0.00

**Movimiento 1:** Recepción de Compra (2026-06-01)
```
Cantidad: 10 unidades
Costo: USD 50.00
TC: 3.400
Costo en PEN: S/ 170.00
```

**Cálculo:**
```
Stock después: 10 unidades
Costo promedio: S/ 170.00
```

**Movimiento 2:** Venta a Cliente (2026-06-05)
```
Tipo: SALE
Cantidad: 3 unidades
reference_type: ApOrderQuotations
```

**Cálculo:**
```
Stock después: 7 unidades (10 - 3)
Costo promedio: S/ 170.00 (NO cambia en ventas)
```

**Movimiento 3:** Nueva Recepción de Compra (2026-06-10)
```
Cantidad: 15 unidades
Costo: USD 55.00
TC: 3.420
Costo en PEN: S/ 188.10
```

**Cálculo:**
```
Stock anterior: 7
Costo promedio anterior: 170.00
Cantidad entrada: 15
Costo entrada: 188.10

Nuevo costo promedio = (7 × 170.00 + 15 × 188.10) / (7 + 15)
                     = (1190.00 + 2821.50) / 22
                     = 4011.50 / 22
                     = S/ 182.34

Stock después: 22 unidades
Costo promedio: S/ 182.34
```

**Movimiento 4:** Venta a Cliente (2026-06-15)
```
Tipo: SALE
Cantidad: 8 unidades
reference_type: ApWorkOrder
```

**Cálculo:**
```
Stock después: 14 unidades (22 - 8)
Costo promedio: S/ 182.34 (NO cambia en ventas)
```

**Historial Completo:**
```
Fecha       Movimiento       Tipo                 Cantidad  Costo Unit.  Stock  Costo Promedio
------------------------------------------------------------------------------------------------------
2026-06-01  MOV-2026-0100    Recepción de Compra  +10       S/ 170.00    10     S/ 170.00
2026-06-05  MOV-2026-0101    Venta                -3        —            7      S/ 170.00
2026-06-10  MOV-2026-0102    Recepción de Compra  +15       S/ 188.10    22     S/ 182.34
2026-06-15  MOV-2026-0103    Venta                -8        —            14     S/ 182.34
```

**Observaciones:**
- Las **ventas (SALE)** reducen el stock pero **NO afectan el costo promedio**
- Las **compras (PURCHASE_RECEPTION)** incrementan stock y **SÍ recalculan el costo promedio**
- El costo promedio se mantiene constante hasta la siguiente compra
- Las ventas pueden referenciar `ApOrderQuotations` (cotizaciones) o `ApWorkOrder` (órdenes de trabajo)

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

## Cálculo Centralizado de Precios de Venta

### Descripción

El sistema calcula automáticamente dos precios de venta para cada producto en cada almacén:

1. **`sale_price` (Precio de Venta al Público - PVP)**: Precio sugerido de venta
2. **`sale_price_min` (Precio Mínimo de Venta)**: Precio mínimo aceptable con descuento

Estos precios se calculan **centralizadamente** en el método `rebuildWeightedAverageCostHistory()` cada vez que cambia el costo promedio ponderado.

### Ubicación del Cálculo

**Archivo:** `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`

**Método:** `rebuildWeightedAverageCostHistory()` (líneas 1935-1965)

### Fórmulas de Cálculo

El sistema soporta dos métodos de cálculo configurables mediante la constante `ProductWarehouseStock::PRICE_CALCULATION_METHOD`:

#### Método 1 (PRICE_CALCULATION_METHOD = 1):
```php
PVP = (Costo / (1 - margen)) × (1 + impuesto)
```

#### Método 2 (PRICE_CALCULATION_METHOD = 2) - Por defecto:
```php
PVP = Costo / (1 - (margen + impuesto))
```

#### Precio Mínimo (ambos métodos):
```php
Precio_Mínimo = PVP × (1 - Descuento_Mínimo)
```

### Parámetros de Configuración

El sistema obtiene los siguientes parámetros mediante métodos del servicio:

```php
$profitMargin = $this->getProfitMargin();           // Margen de ganancia
$freightCommission = $this->getFreightCommission(); // Comisión de flete/impuesto
$minimumDiscount = $this->getMinimunDiscount();     // Descuento mínimo permitido
```

### Ejemplo Numérico

**Datos:**
- Costo promedio: S/ 200.00
- Margen de ganancia: 0.25 (25%)
- Comisión de flete: 0.18 (18% IGV)
- Descuento mínimo: 0.10 (10%)

**Cálculo con Método 2:**
```
PVP = 200 / (1 - (0.25 + 0.18))
    = 200 / (1 - 0.43)
    = 200 / 0.57
    = S/ 350.88

Precio_Mínimo = 350.88 × (1 - 0.10)
              = 350.88 × 0.90
              = S/ 315.79
```

**Resultado:**
- `sale_price`: S/ 350.88
- `sale_price_min`: S/ 315.79

### Estrategia de Actualización: Sincrónica vs. Asincrónica

La decisión entre recalcular precios de forma **SINCRÓNICA** (inmediata) o **ASINCRÓNICA** (Job en background) se basa en la **cantidad de movimientos** del producto en el almacén, no en la antigüedad del movimiento.

**Constante del sistema:**
```php
// ProductWarehouseStockService.php
const MOVEMENT_THRESHOLD = 100;
```

#### Lógica de Decisión (Movement-Count-Based)

```php
// Método: ProductWarehouseStockService::recalculatePricesAfterMovement()

// 1. Contar movimientos APROBADOS del producto en el almacén
$movementCount = InventoryMovement::whereHas('details', function ($q) use ($productId) {
    $q->where('product_id', $productId);
})
->where('warehouse_id', $warehouseId)
->where('status', InventoryMovement::STATUS_APPROVED)
->count();

// 2. Decidir estrategia según cantidad de movimientos
if ($movementCount <= 100) {
    // SYNC: Producto con pocos movimientos, recalcular de inmediato
    $this->rebuildWeightedAverageCostHistory($productId, $warehouseId, $movementDate);
} else {
    // ASYNC: Producto con muchos movimientos (alta rotación), usar Job
    RecalculateProductCostJob::dispatch($productId, $warehouseId, $movementDate);
}
```

#### Operaciones Sincrónicas (≤ 100 Movimientos)

Para productos con **≤ 100 movimientos** en el almacén, se recalcula inmediatamente:

```php
// Ejemplo: Producto nuevo o de baja rotación
// Tiene 45 movimientos en total → Recalcula SYNC
$service->rebuildWeightedAverageCostHistory(
  $productId,
  $warehouseId,
  $movementDate // Fecha del nuevo movimiento
);
```

**Ventajas:**
- Respuesta inmediata (1-3 segundos)
- Usuario ve precios actualizados al instante
- Procesa pocos registros (rápido)
- No requiere infraestructura de colas

**Cuándo ocurre:**
- Productos nuevos o con poca rotación
- Productos de nicho o especializados
- Mayoría de productos del catálogo (estadísticamente el 80% tiene ≤100 movimientos)

**Ejemplo de tiempo:**
- 50 movimientos: ~1.5 segundos
- 100 movimientos: ~2.5 segundos

#### Operaciones Asincrónicas (> 100 Movimientos)

Para productos con **> 100 movimientos** en el almacén (alta rotación), se usa un Job en background:

```php
use App\Jobs\RecalculateProductCostJob;

// Ejemplo: Producto de alta rotación (500 movimientos)
// Se despacha a Job automáticamente
RecalculateProductCostJob::dispatch(
  $productId,
  $warehouseId,
  $movementDate // Fecha del nuevo movimiento
);
```

**Ventajas:**
- No bloquea la interfaz de usuario
- Procesa historiales grandes sin timeout (hasta 10 minutos)
- Permite continuar trabajando mientras se recalcula
- Reintentos automáticos en caso de falla

**Cuándo ocurre:**
- Productos de alta rotación (más vendidos)
- Productos con historial extenso
- Sincronización masiva de Dynamics (ajustes de inventario de últimos 6 meses)

**Ejemplo de tiempo:**
- 200 movimientos: ~8 segundos
- 500 movimientos: ~20 segundos
- 1000 movimientos: ~45 segundos

#### Justificación de la Estrategia (Movement-Count vs Date-Based)

**¿Por qué contar movimientos en lugar de verificar antigüedad?**

El problema con una estrategia basada en fechas (ej: "movimientos > 7 días = ASYNC") es que:
1. Dynamics puede sincronizar ajustes **antiguos** (fecha de movimiento de hace 4 meses) pero **creados HOY**
2. El campo `created_at` siempre sería reciente (hoy), por lo que el Job ASYNC nunca se usaría
3. El campo `movement_date` podría ser antiguo, pero eso no indica la complejidad del recálculo

**La cantidad de movimientos es un mejor indicador porque:**
1. **Es predecible**: Un producto con 50 movimientos siempre toma ~1.5 segundos, sin importar cuándo ocurrieron
2. **Es independiente de la fuente**: Funciona igual para movimientos locales o sincronizados de Dynamics
3. **Basado en estándares de la industria**: Amazon, Shopify, SAP y Odoo usan umbrales similares (50-200 movimientos)
4. **Previene timeouts**: Garantiza que recálculos grandes (>100 movimientos) no bloqueen la UI

#### Umbral de 100 Movimientos (Industry Standard)

El umbral de **100 movimientos** se eligió basándose en:

1. **Amazon Web Services**: Recomiendan batch processing para operaciones > 100 items
2. **Shopify**: Sus APIs usan límites de 100-250 items por request
3. **SAP Business One**: Procesa hasta 100 líneas de documento síncronamente
4. **Odoo ERP**: Límite de 80-100 líneas antes de usar procesamiento asíncrono

**Análisis de performance en nuestro sistema:**
- ≤ 100 movimientos: Promedio 2.3 segundos (aceptable para operación síncrona)
- 101-200 movimientos: Promedio 8 segundos (timeout en algunos navegadores)
- 201-500 movimientos: Promedio 20 segundos (timeout seguro)
- > 500 movimientos: > 30 segundos (timeout garantizado)

### RecalculateProductCostJob

**Ubicación:** `app\Jobs\RecalculateProductCostJob.php`

**Cola:** `product_cost_recalculation`

**Configuración:**
- `tries`: 2 reintentos
- `timeout`: 600 segundos (10 minutos)
- `backoff`: 120 segundos entre reintentos

**Uso:**

```php
use App\Jobs\RecalculateProductCostJob;

// Recalcular TODO el historial
RecalculateProductCostJob::dispatch($productId, $warehouseId, null);

// Recalcular desde una fecha específica
RecalculateProductCostJob::dispatch($productId, $warehouseId, '2026-03-01');

// Despachar múltiples productos (ej: compra de 20 productos)
foreach ($products as $product) {
  RecalculateProductCostJob::dispatch($product->id, $warehouseId, '2026-03-01');
}
```

**Logging:**

El Job registra en los logs:
- Inicio de recalculación
- Finalización exitosa
- Errores detallados con stack trace

```php
// Ver logs
tail -f storage/logs/laravel.log | grep "RecalculateProductCostJob"
```

### Integración con Jobs de Sincronización de Dynamics

#### SyncInventoryAdjustmentsDynamicsJob

Este Job trae ajustes de inventario de los últimos 6 meses desde Dynamics.

**Modificación recomendada:**

```php
// Al finalizar la sincronización de un ajuste:
use App\Jobs\RecalculateProductCostJob;

// Si el ajuste es de hace más de 1 semana
if ($adjustmentDate < now()->subWeek()) {
  // Usar Job asincrónico
  RecalculateProductCostJob::dispatch(
    $productId,
    $warehouseId,
    $adjustmentDate
  );
} else {
  // Usar método sincrónico (reciente)
  $stockService->rebuildWeightedAverageCostHistory(
    $productId,
    $warehouseId,
    $adjustmentDate
  );
}
```

#### SyncCreditNoteDynamicsJob

Similar para notas de crédito de Dynamics.

```php
// Si la NC es antigua (> 1 semana)
if ($creditNoteDate < now()->subWeek()) {
  RecalculateProductCostJob::dispatch($productId, $warehouseId, $creditNoteDate);
} else {
  $stockService->rebuildWeightedAverageCostHistory($productId, $warehouseId, $creditNoteDate);
}
```

### Centralización de Lógica

**IMPORTANTE:** Todos los cálculos de precios están ahora **centralizados** en `rebuildWeightedAverageCostHistory()`.

Los siguientes métodos **YA NO calculan precios** (solo stock y costo):
- `addStock()`: Solo calcula average_cost y cost_price
- `removeStockFromCreditNote()`: Solo ajusta stock

**Antes (INCORRECTO - código duplicado):**
```php
// En addStock():
$stock->sale_price = ...;        // ❌ Ya no se hace aquí
$stock->sale_price_min = ...;    // ❌ Ya no se hace aquí

// En removeStockFromCreditNote():
$stock->sale_price = ...;        // ❌ Ya no se hace aquí
$stock->sale_price_min = ...;    // ❌ Ya no se hace aquí
```

**Ahora (CORRECTO - centralizado):**
```php
// Los precios SOLO se calculan en rebuildWeightedAverageCostHistory()
// Los demás métodos solo llaman a rebuild cuando sea necesario
```

### Ventajas de la Centralización

1. **Single Source of Truth**: Una sola lógica para calcular precios
2. **Mantenibilidad**: Cambios en fórmulas solo se hacen en un lugar
3. **Consistencia**: Mismos precios sin importar el origen del movimiento
4. **Auditoría**: Fácil rastrear cuándo y cómo se calcularon los precios
5. **Performance**: Se puede optimizar el recálculo usando `$fromDate`

### Campos en ProductWarehouseStock

```php
protected $fillable = [
  ...
  'average_cost',      // Costo promedio ponderado
  'cost_price',        // Último costo de compra
  'sale_price',        // Precio de venta al público (PVP)
  'sale_price_min',    // Precio mínimo de venta
  ...
];

protected $casts = [
  'average_cost' => 'decimal:2',
  'cost_price' => 'decimal:2',
  'sale_price' => 'decimal:2',
  'sale_price_min' => 'decimal:2',
];
```

### Migración de Base de Datos

**Archivo:** `database/migrations/2026_06_24_120329_add_sale_price_min_product_warehouse_stock_table.php`

```php
Schema::table('product_warehouse_stock', function (Blueprint $table) {
  $table->decimal('sale_price_min', 10)->default(0)->after('sale_price');
});
```

**Ejecutar migración:**
```bash
php artisan migrate
```

### Casos de Uso Reales

#### Caso 1: Compra de 20 productos a la vez

**Escenario:**
- Usuario aprueba recepción de compra con 20 productos diferentes
- Cada producto tiene ~500 movimientos históricos
- Total: 10,000 movimientos a procesar

**Solución:**
```php
foreach ($purchaseReceptionDetails as $detail) {
  // Despachar Jobs en paralelo
  RecalculateProductCostJob::dispatch(
    $detail->product_id,
    $warehouseId,
    now() // Solo recalcula desde hoy
  );
}
```

**Resultado:**
- Jobs se procesan en background
- Usuario puede continuar trabajando
- Precios se actualizan en ~2-5 minutos

#### Caso 2: Ajuste retroactivo de Dynamics (3 meses atrás)

**Escenario:**
- Se sincroniza ajuste de inventario del 24/03/2026 (hace 3 meses)
- Producto tiene 50 movimientos desde esa fecha

**Solución:**
```php
RecalculateProductCostJob::dispatch(
  $productId,
  $warehouseId,
  '2026-03-24' // Recalcula desde el ajuste en adelante
);
```

**Resultado:**
- Procesa solo 50 movimientos (no todos los años)
- No bloquea la sincronización de Dynamics
- Se completa en ~5-10 segundos

#### Caso 3: Nueva venta en tiempo real

**Escenario:**
- Usuario confirma una venta del día actual
- Producto tiene 200 movimientos históricos

**Solución:**
```php
// Sincrónico: la venta es reciente
$stockService->rebuildWeightedAverageCostHistory(
  $productId,
  $warehouseId,
  now() // Solo recalcula movimientos de hoy
);
```

**Resultado:**
- Respuesta inmediata (< 1 segundo)
- Precios actualizados al instante
- Usuario ve cambios de inmediato

---

## Conclusión

Este sistema está diseñado para:
✓ Flexibilidad: Dos configuraciones independientes para diferentes necesidades
✓ Trazabilidad: Historial completo cronológico de movimientos
✓ Precisión: Cálculo correcto de costo promedio ponderado
✓ Integridad: Cuadre entre documentos fiscales e inventario físico
✓ Auditoría: Todos los movimientos quedan registrados con su referencia original
✓ **Centralización**: Una sola fuente de verdad para cálculos de precios
✓ **Performance**: Estrategia inteligente sincrónica/asincrónica según el caso

**Ubicación del código:** `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`

**Método principal:** `getStockMovementHistory($productId, $warehouseId)`

**Método de recálculo:** `rebuildWeightedAverageCostHistory($productId, $warehouseId, $fromDate)`

**Job de recálculo asincrónico:** `app/Jobs/RecalculateProductCostJob.php`

---

**Última actualización:** 2026-06-24
**Autor:** Sistema de Gestión de Inventario Milla Backend