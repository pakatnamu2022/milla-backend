# Bug: devolución de stock “duplicada” en Notas de Crédito de Taller/Mesón

**Fecha de análisis:** 2026‑09‑01
**Comprobante testigo:** NC `#4986` – `FN52-00000004` (área 881, Taller)
**Estado:** causa raíz **confirmada** por reproducción aislada. Falta decidir e implementar la corrección + regularizar datos.

---

## 1. Resumen ejecutivo

Al contabilizar una Nota de Crédito de anulación sobre una OT (o cotización de mesón) que luego se **re‑factura** (`re_invoice = 1`), el stock físico de los repuestos queda **1 unidad por encima** de lo que dictan sus propios movimientos de inventario. El usuario lo percibe como *“me devolvió el stock dos veces”*.

**No hay doble devolución.** Existe **una sola** devolución (`RETURN_IN`) y es correcta. El `+1` lo inyecta el **cálculo incremental de costo promedio** (`addIncrementalSnapshot`), que al procesar la entrada de la devolución **sobreescribe** `product_warehouse_stock.quantity` con un valor recalculado a partir de `weighted_average_cost_history`, y esa tabla estaba **incompleta**: nunca registró la venta original que había dejado el stock en 0.

Impacto medido: **53 de 4 244** registros de stock del almacén 165 están descuadrados (rango −61 a +73). No es exclusivo de esta NC.

---

## 2. Síntoma reportado

> “Tuve una NC (comprobante id = 4986), mi stock debió retornar pero parece que retornó 2 veces.”

---

## 3. Evidencia en base de datos (caso NC 4986 / OT 247)

### 3.1 Documentos

| Documento | Número | Tipo | Detalle |
|---|---|---|---|
| `#4175` | `FV52-00000130` | Factura (doctype 29) | Factura original, `is_advance_payment = 0`, `work_order_id = 247` |
| `#4986` | `FN52-00000004` | Nota de crédito (doctype 31) | `cn_type = 68` (**ID_CREDIT_NOTE_ANULACION**), `re_invoice = 1`, `original_document_id = 4175` |
| `#4991` | `FV52-00000154` | Factura (doctype 29) | Re‑factura de la OT 247 |

### 3.2 OT 247

```
sede = 15   almacén físico = 165 (ALMACEN POSTVENTA JAE)
status_id = 893   is_invoiced = 1   output_generation_warehouse = 1
had_credit_note = 1   stock_re_reserved = 1
Repuestos: product 4106 (qty 1), product 7673 (qty 1)  — ninguno es travesía
```

### 3.3 Kardex reconstruido (producto 4106, almacén 165)

| id mov | Número | Tipo | Fecha | Δ | Saldo teórico |
|---|---|---|---|---|---|
| 83 | MOV‑2026‑0082 | ADJUSTMENT_IN | 2026‑06‑30 | +1 | 1 |
| 15837 | MOV‑2026‑3962 | SALE | 2026‑08‑24 | −1 | 0 |
| 23604 | MOV‑2026‑4679 | RETURN_IN | 2026‑09‑01 | +1 | 1 |
| 23897 | MOV‑2026‑4705 | SALE | 2026‑09‑01 | −1 | 0 |

```
SUMA DE MOVIMIENTOS (esperado)                 = 0
product_warehouse_stock.quantity (almacenado) = 1     <-- DESFASE +1
reserved_quantity = 0   available_quantity = 1
```

Producto 7673: idéntico, **desfase +1**.

La pieza salió al cliente **una sola vez** (misma OT, re‑facturada). El stock correcto debería ser **0**.

### 3.4 `weighted_average_cost_history` (WACH) del producto 4106 / almacén 165

Solo **2 filas** (¡faltan las dos ventas!):

| id | Fecha | Tipo | mov_id | qty_in | `stock_after_movement` | `average_cost_after_movement` | `recalculated_at` |
|---|---|---|---|---|---|---|---|
| 9270 | 2026‑06‑30 | ADJUSTMENT_IN | 83 | 1 | **1** | 137.26 | 2026‑07‑06 |
| 780847443 | 2026‑09‑01 | RETURN_IN | 23604 | 1 | **2** | **174.22** | **NULL** |

- `recalculated_at = NULL` ⇒ es un **snapshot incremental en tiempo real**, no un rebuild.
- `stock_after_movement = 2` solo puede salir de `previousStock = 1` (el snapshot viejo del ajuste), **no** del stock real que en ese momento era `0`.
- `174.22 = (1 × 137.26 + 1 × 211.17) / 2` → fórmula de promedio ponderado con `previousStock = 1`. Con el stock real (`0`) habría dado `stock_after = 1` y `avg = 211.17`.

### 3.5 Contaminación de `average_cost`

`product_warehouse_stock.average_cost` quedó también sobreescrito:

| Producto | `stock.quantity` | `stock.average_cost` | `WACH.average_cost_after_movement` |
|---|---|---|---|
| 4106 | 1.00 | **174.22** | 174.22 |
| 7673 | 1.00 | **149.76** | 149.76 |

---

## 4. Qué se descartó (con prueba)

| Hipótesis | Resultado |
|---|---|
| La reversión de la NC corrió 2 veces | **Falso.** Una sola fila `RETURN_IN` (MOV‑4679), un solo detalle por producto. Sin filas soft/hard‑deleted (`withTrashed`), sin `inventory_movement_details` huérfanos. |
| Movimiento de más / duplicado | **Falso.** Exactamente 4 movimientos por producto, todos `APPROVED`, todos con detalle válido. |
| La secuencia de operaciones de stock está mal | **Falso.** Replay de la secuencia ejecutada **una vez** → delta −1 (correcto). Ver `replay_nc_reversal.php`, Escenario A. |

---

## 5. Causa raíz (confirmada)

### 5.1 El punto exacto

`app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`, método **`addIncrementalSnapshot()`**:

```php
// ~línea 644-646
$lastSnapshot  = WeightedAverageCostHistory::getLatestSnapshot($productId, $warehouseId);
$previousStock = $lastSnapshot ? (float)$lastSnapshot->stock_after_movement : 0;

// ~línea 668-670  (movimiento de ENTRADA)
$isInbound = $movement->is_inbound;
if ($isInbound) {
    $newStock = $previousStock + $quantity;
    // ...
}

// ~línea 708-709  <<<<<<  AQUÍ SE INYECTA EL BUG
$stock->quantity     = $newStock;      // sobreescribe el stock físico REAL
$stock->average_cost = $newAvgCost;    // sobreescribe el costo promedio REAL
// ...
$stock->save();                        // ~línea 738
```

`addIncrementalSnapshot()` se invoca así:

```
createReturnMovementForWorkOrder()                    (InventoryMovementService.php:2168)
  └─ updateStockFromMovement($movement)               (ProductWarehouseStockService.php:442)   ← InventoryMovementService.php:2308
       ├─ addStock(...)  → quantity += 1   (CORRECTO, deja el stock en 1)
       └─ recalculatePricesAfterMovement(...)         (ProductWarehouseStockService.php:492 → :512)
            └─ addIncrementalSnapshot(...)            (ProductWarehouseStockService.php:544-548)
                 └─ $stock->quantity = previousStock(1, STALE) + 1 = 2   ← PISA el valor correcto
```

Config activa: `cost_calculation_mode = incremental` (rama `addIncrementalSnapshot`, no rebuild).

### 5.2 Por qué `weighted_average_cost_history` estaba incompleto

WACH **solo avanza con movimientos de ENTRADA**. Las ventas de OT **nunca** generan snapshot:

1. **`createSaleFromWorkOrder()`** (a partir del commit `631f5567`, 2026‑08‑31) descuenta stock con `removeStockFromSale()` y ya **no** llama a `updateStockFromMovement()`:

   ```php
   // InventoryMovementService.php ~1928-1944
   foreach ($productParts as $part) {
     $this->stockService->removeStockFromSale($part->product_id, $warehouse->id, $part->quantity_used, true);
   }
   // "Ya no es necesario llamar a updateStockFromMovement porque removeStockFromSale
   //  ya actualizó el stock correctamente"
   ```

   `removeStockFromSale()` → `releaseReservedStockAndRemove()` (`ProductWarehouseStockService.php:836`) hace `$stock->quantity -= $qty; $stock->save();` **sin** tocar WACH ni `recalculatePricesAfterMovement()`.

2. Aunque una venta pase por `updateStockFromMovement()`, ese método **solo encola productos para recálculo en la rama `is_inbound`** (`ProductWarehouseStockService.php` ~449‑486). Las salidas nunca entran a `$productsToRecalculate`.

**Consecuencia:** `stock_after_movement` en WACH **solo sube** (entradas) y **nunca baja** (ventas). Se despega de la realidad y cada entrada posterior re‑inyecta ese despegue en `product_warehouse_stock.quantity` vía la línea 708.

> Nota: globalmente sí existen 4 679 filas WACH de tipo `SALE`. Provienen del **rebuild completo** (`rebuildWeightedAverageCostHistory()` / `RecalculateProductCostJob`), no del flujo incremental. Para 4106/7673 el último rebuild fue **anterior** a la venta MOV‑3962, por eso su WACH solo tiene la entrada inicial.

---

## 6. Secuencia paso a paso — NC 4986 / producto 4106

| # | Evento | `quantity` real | Último `WACH.stock_after` | Comentario |
|---|---|---|---|---|
| 0 | Baseline (ADJUSTMENT_IN 2026‑06‑30) | 1 | 1 | OK |
| 1 | Se agrega repuesto a OT 247 → `reserveStock(1)` | 1 (reserved 1) | 1 | OK |
| 2 | FV52‑00000130 contabilizada → `createSaleFromWorkOrder` → `removeStockFromSale` | **1 → 0** | **1** ⚠️ | La venta **no** escribe snapshot WACH → WACH queda *stale* |
| 3a | NC 4986 contabilizada → `reverseWorkOrderStatus(247)` → `createReturnMovementForWorkOrder` → `addStock(1)` | 0 → **1** ✅ | 1 | El `addStock` deja el stock **correcto** en 1 |
| 3b | …mismo `updateStockFromMovement` → `addIncrementalSnapshot`: `newStock = 1 (stale) + 1 = 2` → `$stock->quantity = 2` | **1 → 2** ❌ | escribe **2**, `avg = 174.22` | **Aquí se inyecta el `+1`** |
| 4 | `re_invoice = 1` → `reReserveStockForWorkOrder(247)` → `reserved += 1` | 2 (reserved 1) | 2 | Solo toca `reserved_quantity` |
| 5 | FV52‑00000154 contabilizada → `createSaleFromWorkOrder` → `removeStockFromSale` | 2 → **1** | 2 | Sin snapshot WACH |
| — | **Final** | **1** (debería ser **0**) | 2 | **Desfase +1** |

---

## 7. Confirmación por reproducción aislada

Script `confirm_root_cause.php` (todo dentro de una transacción revertida):

1. Monta el estado previo real: `product_warehouse_stock.quantity = 0`, último `WACH.stock_after_movement = 1` (stale).
2. Crea un `RETURN_IN` de +1 y llama `updateStockFromMovement()`.
3. Resultado:

```
product_warehouse_stock.quantity = 2.00      (debería ser 1)
nuevo WACH.stock_after_movement   = 2.0000   <- idéntico a la fila real de producción
nuevo WACH.average_cost           = 174.22   <- idéntico a la fila real de producción
>>> CONFIRMADO
```

La fila generada por la reproducción es **byte a byte igual** a la fila real `id = 780847443` de tu BD. Eso cierra el diagnóstico.

---

## 8. Alcance

Reconciliación `product_warehouse_stock.quantity` vs. suma de movimientos, almacén 165:

```
registros revisados : 4244
descuadrados        : 53      (diferencias de -61 a +73)
```

Patrón común: producto con **rebuild WACH antiguo** + **ventas por `removeStockFromSale`** + **cualquier entrada posterior** (devolución, compra, ajuste, transferencia) → `quantity` sobreescrito con valor inflado.

Otras NC de Taller/Mesón con `re_invoice = 1` son candidatas directas al mismo desfase.

---

## 9. Bug secundario detectado (no es la causa de este caso, pero conviene corregir)

### 9.1 Reversión de NC no idempotente

- `SyncAccountingStatusJob.php:99` — la rama de reversión de NC **no** tiene el guard `!$wasAccounted` que sí tiene la rama de ventas (línea 89). El comentario lo admite: *“primera vez o re‑procesamiento”*.
- `ApWorkOrderReversalService::reverseWorkOrderStatus()` (línea 43) — su **único** freno es `if ($workOrder->output_generation_warehouse)`.
- `InventoryMovementService::createSaleFromWorkOrder()` (línea 1929) — al re‑facturar vuelve a poner `output_generation_warehouse = true`, **reabriendo** ese freno.
- `reverseInventoryForWorkOrder()` / `createReturnMovementForWorkOrder()` — **no** verifican si ya existe un `RETURN_IN` para esa NC.
- Además `cancelInNubefact()` (`ElectronicDocumentService.php:1544`) llama `reverseWorkOrderStatus()` **directamente**, disparador independiente del Job.

Hoy no produjo doble fila porque el flujo terminó cuadrando a nivel de movimientos, pero es una bomba de tiempo: si el Job se reintenta (`$tries = 3`) o se re‑dispara tras la re‑factura, sí generaría un segundo `RETURN_IN`.

### 9.2 `catch` con variable inexistente

`InventoryMovementService::createReturnMovementForWorkOrder()` (~línea 2315): el `catch` referencia `$creditNote->id`, pero el parámetro del método se llama `$relatedDocument`. Si el `try` falla, el `catch` lanza un segundo error (`Attempt to read property "id" on null`) y oculta el original.

---

## 10. Soluciones propuestas

### Opción A (recomendada) — `addIncrementalSnapshot` NO debe sobreescribir `quantity`

**Idea:** el stock físico ya lo actualizan `addStock()` / `removeStock*()`. El snapshot incremental es para **costo promedio**, no para cantidad. Que use el `quantity` real como base y que no lo pise.

Cambios en `ProductWarehouseStockService::addIncrementalSnapshot()`:

1. **No** hacer `$stock->quantity = $newStock;` (línea 708). Dejar que `quantity` lo gobiernen solo los métodos de stock.
2. Calcular `previousStock` desde el **stock real** antes del movimiento, no desde `lastSnapshot->stock_after_movement`:
   - `previousStockReal = $stock->quantity - $delta` (donde `$delta = +qty` si entrada, `-qty` si salida), **o**
   - registrar `stock_after_movement = $stock->quantity` (valor real ya actualizado) y `previousStock` = `$stock->quantity - $delta`.
3. El costo promedio ponderado debe usar `previousStockReal`, no el stale:
   `newAvgCost = ((previousStockReal * previousAvgCost) + (qty * unitCost)) / (previousStockReal + qty)`.

**Pros:** ataca la causa raíz; mínimo cambio; `quantity` deja de tener dos “dueños”.
**Contras:** hay que revisar que ningún otro consumidor dependa de que `addIncrementalSnapshot` fije `quantity` (buscar usos). Revisar `SyncInventoryAdjustmentsDynamicsJob.php:285` que también llama `recalculatePricesAfterMovement`.

### Opción B — Que TODA salida genere snapshot WACH (coherencia total del historial)

1. En `updateStockFromMovement()` encolar también los productos de la rama `else` (salidas) en `$productsToRecalculate`.
2. En `createSaleFromWorkOrder()` / `createSaleFromQuotation()` volver a llamar `recalculatePricesAfterMovement()` (o `updateStockFromMovement()`) tras `removeStockFromSale()`, **o** que `removeStockFromSale()` dispare el snapshot internamente.
3. Así `lastSnapshot->stock_after_movement` siempre refleja la realidad y la Opción A deja de ser necesaria para cuadrar (pero sigue siendo sana).

**Pros:** WACH queda completo y auditable; sirve para kardex y costeo.
**Contras:** más invasivo; más escrituras por venta; hay que validar performance y que el `previousStock` en cadenas largas no acumule error; riesgo de doble descuento si se hace mal.

### Opción C (defensa en profundidad) — Invariante + job de verificación

Independiente de A/B:

1. Al final de `updateStockFromMovement()` / `addIncrementalSnapshot()`, **assert** `quantity == available_quantity + reserved_quantity` y `quantity == Σ(entradas) − Σ(salidas)` para ese producto/almacén; si no cuadra, log de error + alerta (no romper la operación).
2. Comando programado `stock:reconciliar {--warehouse=} {--fix}` que compare `product_warehouse_stock.quantity` contra la suma de movimientos y liste / corrija descuadres (reutilizar la lógica de `diagnose_nc_stock_return.php`).

### Corrección del bug secundario (§9)

1. `SyncAccountingStatusJob.php:99` → añadir guard de idempotencia: no re‑revertir si ya existe `InventoryMovement` tipo `RETURN_IN` con `electronic_document_id = $document->id` (o `reference_id` = NC).
2. `createReturnMovementForWorkOrder()` / `reverseInventoryForWorkOrder()` → antes de crear, verificar que no exista ya un `RETURN_IN` para (NC, OT).
3. `createReturnMovementForWorkOrder()` catch → cambiar `$creditNote->id` por `$relatedDocument->id ?? null`.

---

## 11. Plan de regularización de datos (después de aplicar el fix)

1. **Congelar** temporalmente movimientos de inventario en el almacén afectado durante la corrección (o hacerlo en ventana de baja actividad).
2. Para cada `(product_id, warehouse_id)` descuadrado:
   a. Recalcular `quantity_correcto = Σ(entradas APPROVED) − Σ(salidas APPROVED)` (con transferencias por `warehouse_destination_id`, y considerando `INITIAL`/carga inicial si aplica).
   b. Ejecutar `rebuildWeightedAverageCostHistory(product_id, warehouse_id, fromDate)` para reconstruir WACH completo y coherente.
   c. Verificar `quantity == available + reserved`; ajustar `available_quantity` si es necesario (`updateAvailableQuantity()`).
   d. Registrar un `ADJUSTMENT_OUT` (o `ADJUSTMENT_IN`) de conciliación con nota explícita **o** corrección directa documentada (según política contable) para dejar traza del ajuste `-1` por producto.
3. Revisar `average_cost` / `sale_price` / `sale_price_min` de los productos tocados (quedaron con promedio ponderado calculado sobre base inflada).
4. Caso NC 4986 concreto: `4106` y `7673` en almacén 165 deben quedar en `quantity = 0` (o el valor real que corresponda si hubo otros movimientos legítimos posteriores).
5. Correr `diagnose_nc_stock_return.php` sobre **todas** las NC de área 881/882 con `re_invoice = 1` para dimensionar y priorizar.

---

## 12. Scripts de diagnóstico (guardar en `scripts/` o `database/diagnostics/`)

> Se usaron con `php artisan tinker --execute="\$DOCUMENT_ID=4986; require 'ruta/archivo.php';"`.
> Todos los de reproducción corren dentro de `DB::beginTransaction()` + `DB::rollBack()` → **no modifican datos reales**.

### 12.1 `diagnose_nc_stock_return.php` — auditoría de una NC

```php
<?php
/**
 * Diagnóstico de devolución de stock por Nota de Crédito.
 * Uso: php artisan tinker --execute="$DOCUMENT_ID=4986; require 'ruta/diagnose_nc_stock_return.php';"
 * Reconstruye el kardex de cada repuesto de la OT/cotización asociada y compara
 * el stock ALMACENADO contra la SUMA de sus movimientos. Desfase = retorno de más/menos.
 */

use Illuminate\Support\Facades\DB;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;

$DOCUMENT_ID = $DOCUMENT_ID ?? 4986;

$INBOUND  = ['PURCHASE_RECEPTION', 'RETURN_IN', 'TRANSFER_IN', 'ADJUSTMENT_IN'];
$OUTBOUND = ['SALE', 'RETURN_OUT', 'TRANSFER_OUT', 'ADJUSTMENT_OUT'];

$doc = ElectronicDocument::find($DOCUMENT_ID);
if (!$doc) { echo "Documento $DOCUMENT_ID no existe\n"; return; }

$isNC = $doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO;
$orig = $isNC ? $doc->originalDocument : $doc;

echo str_repeat('=', 90) . "\n";
echo "COMPROBANTE #{$doc->id}  {$doc->full_number}   area={$doc->area_id}   "
   . ($isNC ? "NOTA DE CREDITO (cn_type={$doc->sunat_concept_credit_note_type_id}, re_invoice=" . (int)$doc->re_invoice . ")"
            : "COMPROBANTE DE VENTA") . "\n";
if ($orig) echo "FACTURA ORIGINAL #{$orig->id}  {$orig->full_number}   work_order_id={$orig->work_order_id}   order_quotation_id={$orig->order_quotation_id}   is_advance_payment={$orig->is_advance_payment}\n";
echo str_repeat('=', 90) . "\n\n";

$targets = [];

$collectWO = function ($workOrderId) use (&$targets) {
    $wo = ApWorkOrder::find($workOrderId);
    if (!$wo) return;
    $wh = Warehouse::where('sede_id', $wo->sede_id)->where('is_physical_warehouse', true)->where('status', true)->first();
    foreach (ApWorkOrderParts::where('work_order_id', $wo->id)->whereNotNull('product_id')->get() as $p) {
        $targets[] = ['product_id' => $p->product_id, 'qty' => (float)$p->quantity_used,
                      'warehouse_id' => $wh?->id, 'is_traverse' => (bool)$p->is_traverse,
                      'label' => "OT {$wo->correlative} (part#{$p->id})"];
    }
    echo "OT #{$wo->id} {$wo->correlative}: sede={$wo->sede_id} almacen={$wh?->id} ({$wh?->description})  "
       . "status_id={$wo->status_id} is_invoiced=" . (int)$wo->is_invoiced
       . " output_generation_warehouse=" . (int)$wo->output_generation_warehouse
       . " had_credit_note=" . (int)($wo->had_credit_note ?? 0)
       . " stock_re_reserved=" . (int)($wo->stock_re_reserved ?? 0) . "\n";
};

$collectQuo = function ($quotationId) use (&$targets) {
    $q = ApOrderQuotations::with('details')->find($quotationId);
    if (!$q) return;
    $wh = Warehouse::where('sede_id', $q->sede_id)->where('is_physical_warehouse', true)->where('status', true)->first();
    foreach ($q->details as $d) {
        if (!$d->product_id) continue;
        $targets[] = ['product_id' => $d->product_id, 'qty' => (float)$d->quantity,
                      'warehouse_id' => $wh?->id, 'is_traverse' => (bool)$d->is_traverse,
                      'label' => "COT {$q->quotation_number}"];
    }
    echo "COTIZACION #{$q->id} {$q->quotation_number}: sede={$q->sede_id} almacen={$wh?->id}  "
       . "status_id={$q->status_id} is_fully_paid=" . (int)$q->is_fully_paid
       . " output_generation_warehouse=" . (int)$q->output_generation_warehouse
       . " had_credit_note=" . (int)($q->had_credit_note ?? 0)
       . " stock_re_reserved=" . (int)($q->stock_re_reserved ?? 0) . "\n";
};

if ($orig?->work_order_id)      $collectWO($orig->work_order_id);
if ($orig?->order_quotation_id) $collectQuo($orig->order_quotation_id);
if ($orig && $orig->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
    foreach ($orig->internalNotes()->get() as $note) {
        if ($note->work_order_id) $collectWO($note->work_order_id);
    }
}
echo "\n";

$grandDiff = 0.0; $seen = [];
foreach ($targets as $t) {
    $key = $t['product_id'] . '@' . $t['warehouse_id'];
    if (isset($seen[$key])) continue;
    $seen[$key] = true;

    $pid = $t['product_id']; $wid = $t['warehouse_id'];
    $stock = ProductWarehouseStock::where('product_id', $pid)->where('warehouse_id', $wid)->first();

    $movs = DB::table('inventory_movement_details as d')
        ->join('inventory_movements as m', 'm.id', '=', 'd.inventory_movement_id')
        ->where('d.product_id', $pid)->whereNull('m.deleted_at')->where('m.status', 'APPROVED')
        ->where(function ($q) use ($wid) { $q->where('m.warehouse_id', $wid)->orWhere('m.warehouse_destination_id', $wid); })
        ->orderBy('m.movement_date')->orderBy('m.id')
        ->get(['m.id as mid', 'm.movement_number', 'm.movement_type', 'm.movement_date',
               'm.reference_type', 'm.reference_id', 'm.electronic_document_id', 'd.quantity']);

    echo str_repeat('-', 90) . "\n";
    echo "PRODUCTO {$pid}   almacen {$wid}   [{$t['label']}]" . ($t['is_traverse'] ? "  *** TRAVESIA ***" : "") . "\n";
    echo "  stock ALMACENADO : quantity=" . ($stock->quantity ?? 'N/A')
       . "  reserved=" . ($stock->reserved_quantity ?? 'N/A')
       . "  available=" . ($stock->available_quantity ?? 'N/A') . "\n  kardex:\n";

    $run = 0.0;
    foreach ($movs as $r) {
        $isIn = in_array($r->movement_type, $INBOUND, true) || $r->movement_type === 'TRANSFER_IN';
        $run += ($isIn ? 1 : -1) * (float)$r->quantity;
        printf("   %-6s %-15s %-11s %s  ref=%s:%s edoc=%s  %s%s   saldo=%s\n",
            $r->mid, $r->movement_number, $r->movement_type, substr($r->movement_date, 0, 10),
            class_basename($r->reference_type ?? '-'), $r->reference_id ?? '-', $r->electronic_document_id ?? '-',
            $isIn ? '+' : '-', $r->quantity, $run);
    }

    $stored = (float)($stock->quantity ?? 0);
    $diff   = round($stored - $run, 4);
    $grandDiff += $diff;
    echo "  --------\n  SUMA MOVIMIENTOS (esperado) = {$run}\n  STOCK ALMACENADO           = {$stored}\n";
    echo abs($diff) < 0.001
        ? "  >>> OK\n"
        : ($diff > 0
            ? "  >>> DESFASE +{$diff}: hay {$diff} unidad(es) DE MÁS -> retorno duplicado.\n"
            : "  >>> DESFASE {$diff}: faltan " . abs($diff) . " unidad(es).\n");
}

echo str_repeat('=', 90) . "\nDESFASE TOTAL del comprobante: " . round($grandDiff, 4) . "\n" . str_repeat('=', 90) . "\n";
```

### 12.2 `replay_nc_reversal.php` — replay del ciclo (1 vez vs. 2 veces)

```php
<?php
/**
 * Replay del ciclo NC de anulación + re-factura sobre un producto de prueba.
 * Corre dentro de una transacción que se REVIERTE: no toca datos reales.
 * ESCENARIO A: ciclo ejecutado 1 vez  -> delta -1 (correcto).
 * ESCENARIO B: reversión ejecutada 2 veces -> queda +1 de más.
 */

use Illuminate\Support\Facades\DB;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;

$svc  = app(ProductWarehouseStockService::class);
$PID  = 4106;   // solo "cascarón"; se restaura por rollback
$WH   = 165;
$BASE = 10.0;

$show = function (string $step) use ($PID, $WH) {
    $s = ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)->first();
    printf("   %-62s qty=%-6s reserved=%-5s available=%-6s\n", $step, $s->quantity, $s->reserved_quantity, $s->available_quantity);
};

$run = function (bool $doubleReverse) use ($svc, $PID, $WH, $BASE, $show) {
    ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)
        ->update(['quantity' => $BASE, 'reserved_quantity' => 0, 'available_quantity' => $BASE]);
    echo "  BASELINE\n"; $show('inicio');

    $svc->reserveStock($PID, $WH, 1);
    $show('[1] repuesto agregado a la OT  -> reserveStock(1)');

    $svc->releaseReservedStockAndRemove($PID, $WH, 1);
    $show('[2] factura original contabilizada -> SALE (venta real)  -1');

    $svc->addStock($PID, $WH, 1);
    $show('[3] NC anulacion contabilizada -> RETURN_IN  +1');

    $s = ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)->first();
    $s->reserved_quantity += 1; $s->updateAvailableQuantity(); $s->save();
    $show('[4] re_invoice=1 -> reReserveStockForWorkOrder  reserved+1');

    if ($doubleReverse) {
        $svc->addStock($PID, $WH, 1);
        $show('[3-bis] NC RE-PROCESADA -> RETURN_IN otra vez  +1   <-- DOBLE RETORNO');
    }

    $svc->releaseReservedStockAndRemove($PID, $WH, 1);
    $show('[5] re-factura contabilizada -> SALE (venta real)  -1');

    $final = (float) ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)->first()->quantity;
    $delta = $final - $BASE;
    echo "  RESULTADO: qty final = {$final}   delta vs baseline = {$delta}   (correcto = -1)\n";
    echo "  " . ($delta == -1 ? ">>> OK, cuadra." : ">>> MAL, sobran " . ($delta + 1) . " unidad(es).") . "\n";
};

DB::beginTransaction();
try {
    echo str_repeat('=', 90) . "\n ESCENARIO A — ciclo ejecutado UNA vez\n" . str_repeat('=', 90) . "\n";
    $run(false);
    echo "\n" . str_repeat('=', 90) . "\n ESCENARIO B — la reversión de la NC se ejecuta DOS veces\n" . str_repeat('=', 90) . "\n";
    $run(true);
} finally {
    DB::rollBack();
    echo "\n(transacción revertida — no se modificó ningún dato real)\n";
}
```

### 12.3 `confirm_root_cause.php` — prueba decisiva de la causa raíz

```php
<?php
/**
 * PRUEBA DECISIVA: addIncrementalSnapshot() sobreescribe product_warehouse_stock.quantity
 * con (ultimo_WACH.stock_after_movement + qty_in) y el ultimo WACH estaba DESACTUALIZADO.
 * Corre en transacción revertida. No toca datos reales.
 */

use Illuminate\Support\Facades\DB;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\gestionProductos\WeightedAverageCostHistory;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;

$PID = 4106; $WH = 165;
$svc = app(ProductWarehouseStockService::class);

DB::beginTransaction();
try {
    // Estado previo real (tal como quedó tras la venta MOV-3962):
    //   stock físico REAL = 0 ; ultimo WACH.stock_after_movement = 1 (stale, la venta no generó snapshot)
    ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)
        ->update(['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0, 'average_cost' => 137.26]);

    DB::table('weighted_average_cost_history')->where('product_id', $PID)->where('warehouse_id', $WH)->delete();
    WeightedAverageCostHistory::create([
        'product_id' => $PID, 'warehouse_id' => $WH, 'movement_id' => 83,   // id de movimiento real (FK)
        'movement_date' => '2026-06-30', 'movement_type' => 'ADJUSTMENT_IN', 'movement_number' => 'SEED',
        'quantity_in' => 1, 'quantity_out' => 0, 'unit_cost_pen' => 137.26,
        'stock_after_movement' => 1, 'average_cost_after_movement' => 137.26, 'recalculated_at' => null,
    ]);

    echo "PREVIO A LA NC:\n";
    $s = ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)->first();
    echo "  quantity REAL = {$s->quantity}   |   ultimo WACH.stock_after = "
       . WeightedAverageCostHistory::where('product_id', $PID)->where('warehouse_id', $WH)->orderByDesc('id')->value('stock_after_movement')
       . "  (deberia ser 0)\n\n";

    // Procesar el RETURN_IN de la NC (+1) tal como createReturnMovementForWorkOrder()
    $mov = InventoryMovement::create([
        'movement_number' => 'TEST-RETURN', 'movement_type' => InventoryMovement::TYPE_RETURN_IN,
        'movement_date' => '2026-09-01', 'warehouse_id' => $WH, 'status' => InventoryMovement::STATUS_APPROVED,
        'total_items' => 1, 'total_quantity' => 1, 'notes' => 'TEST devolucion NC',
    ]);
    InventoryMovementDetail::create([
        'inventory_movement_id' => $mov->id, 'product_id' => $PID, 'quantity' => 1,
        'unit_cost' => 211.17, 'total_cost' => 211.17, 'notes' => 'TEST',
    ]);
    $svc->updateStockFromMovement($mov->fresh('details'));

    $s    = ProductWarehouseStock::where('product_id', $PID)->where('warehouse_id', $WH)->first();
    $wach = WeightedAverageCostHistory::where('product_id', $PID)->where('warehouse_id', $WH)->orderByDesc('id')->first();
    echo "DESPUES DEL RETURN_IN (+1):\n";
    echo "  product_warehouse_stock.quantity = {$s->quantity}   (deberia ser 1)\n";
    echo "  nuevo WACH.stock_after_movement   = {$wach->stock_after_movement}\n";
    echo "  nuevo WACH.average_cost           = {$wach->average_cost_after_movement}\n\n";
    echo ((float)$s->quantity == 2.0)
        ? ">>> CONFIRMADO: rebasó desde el WACH stale (1) => 1+1 = 2. El '+1' viene de\n"
        . ">>> ProductWarehouseStockService.php:708 (sobreescritura de quantity con WACH incompleto).\n"
        : ">>> NO reproducido (quantity = {$s->quantity}).\n";
} finally {
    DB::rollBack();
    echo "\n(transaccion revertida - nada real fue modificado)\n";
}
```

---

## 13. Checklist para mañana

- [ ] Revisar todos los usos de `addIncrementalSnapshot` / `recalculatePricesAfterMovement` y confirmar que nadie depende de que fijen `quantity` (`SyncInventoryAdjustmentsDynamicsJob.php:285`, etc.).
- [ ] Decidir entre **Opción A** (no sobreescribir `quantity`) y **Opción B** (WACH completo con salidas). Recomendado: **A** ahora + **C** (invariante + job de reconciliación), evaluar **B** después.
- [ ] Aplicar corrección del bug secundario §9 (idempotencia de la reversión + `catch` con `$relatedDocument`).
- [ ] Correr `diagnose_nc_stock_return.php` sobre todas las NC de área 881/882 con `re_invoice = 1`.
- [ ] Definir y ejecutar el plan de regularización §11 sobre los 53 descuadres del almacén 165 (y revisar otros almacenes).
- [ ] Revisar `average_cost` / `sale_price` / `sale_price_min` de los productos contaminados.
- [ ] Añadir test de regresión: venta → NC anulación → re‑factura debe dejar `quantity == Σ(entradas) − Σ(salidas)`.

---

## 14. Archivos y líneas clave

| Archivo | Línea(s) | Rol |
|---|---|---|
| `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php` | **708‑709** | **Sobreescritura de `quantity` / `average_cost`** (causa raíz) |
| id. | 614‑743 | `addIncrementalSnapshot()` |
| id. | 442‑499 | `updateStockFromMovement()` (solo encola entradas para recálculo) |
| id. | 512‑568 | `recalculatePricesAfterMovement()` |
| id. | 836‑879 | `releaseReservedStockAndRemove()` (no genera snapshot WACH) |
| id. | 953‑962 | `removeStockFromSale()` |
| `app/Http/Services/ap/postventa/gestionProductos/InventoryMovementService.php` | 1827‑1952 | `createSaleFromWorkOrder()` (usa `removeStockFromSale`, no `updateStockFromMovement`) |
| id. | 2168‑2321 | `createReturnMovementForWorkOrder()` (llama `updateStockFromMovement` en :2308) |
| `app/Http/Services/ap/postventa/taller/ApWorkOrderReversalService.php` | 33‑105 | `reverseWorkOrderStatus()` / `reverseInventoryForWorkOrder()` |
| `app/Jobs/SyncAccountingStatusJob.php` | 89 / 98‑107 | rama ventas (con guard) vs. rama reversión NC (sin guard `!$wasAccounted`) |
| `app/Http/Services/ap/facturacion/ElectronicDocumentService.php` | 1494‑1562 | `cancelInNubefact()` CASO 2 (segundo disparador de la reversión) |
| Commit | `631f5567` (2026‑08‑31) | Cambió `createSaleFromWorkOrder` a `removeStockFromSale` (origen del WACH incompleto para ventas de OT) |
