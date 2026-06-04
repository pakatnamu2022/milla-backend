# 📘 Documentación Técnica: Proceso de Anticipos y Facturación en Órdenes de Trabajo

**Versión:** 1.0
**Fecha:** 2026-06-03
**Sistema:** Milla Backend - Postventa/Taller

---

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Conceptos Clave](#conceptos-clave)
3. [Flujo Completo del Proceso](#flujo-completo-del-proceso)
4. [Modelos y Métodos Principales](#modelos-y-métodos-principales)
5. [Casos de Uso Detallados](#casos-de-uso-detallados)
6. [Validaciones y Reglas de Negocio](#validaciones-y-reglas-de-negocio)
7. [Procesamiento Automático (Jobs)](#procesamiento-automático-jobs)
8. [API Response Structure](#api-response-structure)
9. [Escenarios Edge Cases](#escenarios-edge-cases)
10. [Troubleshooting](#troubleshooting)

---

## 📌 Resumen Ejecutivo

### **¿Qué hace este sistema?**

Gestiona el ciclo completo de facturación de órdenes de trabajo (OT) soportando:
- ✅ Anticipos múltiples antes de finalizar el trabajo
- ✅ Factura final que puede deducir anticipos
- ✅ Notas de crédito/débito sobre anticipos y factura final
- ✅ Validaciones para que el monto de la OT no baje por debajo de lo ya pagado
- ✅ Reversión automática de inventario cuando se anulan facturas
- ✅ Actualización de cantidades en OT cuando hay NC por devolución parcial de ítems

### **Principio fundamental:**

> El `final_amount` de la OT se calcula DINÁMICAMENTE desde repuestos (`ApWorkOrderParts`) y mano de obra (`WorkOrderLabour`).
> Los anticipos NO afectan el `final_amount`, solo el saldo pendiente de pago.

---

## 🔑 Conceptos Clave

### **1. Tipos de Documentos Electrónicos**

| Tipo | `is_advance_payment` | Genera Inventario | Cierra OT |
|------|---------------------|-------------------|-----------|
| **Anticipo** (Factura/Boleta) | `1` | ❌ NO | ❌ NO |
| **Factura Final** | `0` | ✅ SÍ (al contabilizarse) | ✅ SÍ |
| **Nota de Crédito** | `0` | Depende del tipo | Depende del contexto |
| **Nota de Débito** | `0` | ❌ NO | ❌ NO |

### **2. Estados de la OT**

```
OPEN → IN_PROGRESS → FINISHED → CLOSED
                         ↑
                    (Se puede facturar)

CLOSED: Solo cuando factura final es contabilizada en Dynamics
```

### **3. Campos Clave en ApWorkOrder**

| Campo | Descripción | Se Modifica |
|-------|-------------|-------------|
| `final_amount` | Monto total calculado (repuestos + mano de obra + IGV) | ✅ Sí (por ediciones en repuestos/mano de obra) |
| `is_invoiced` | Si tiene factura final | ✅ Sí (al contabilizarse factura final) |
| `status_id` | Estado de la OT | ✅ Sí |
| `output_generation_warehouse` | Si ya generó salida de inventario | ✅ Sí (al contabilizarse) |

### **4. Tipos de Nota de Crédito (SUNAT)**

| Código | Tipo | Afecta Inventario | Afecta ApWorkOrderParts |
|--------|------|-------------------|-------------------------|
| `01` | Anulación | ✅ Total | ❌ NO (solo revierte estados) |
| `02` | Devolución de ítem | ✅ Parcial | ✅ SÍ (actualiza cantidades) |
| `06` | Devolución total | ✅ Total | ❌ NO |
| `07` | Descuento global | ❌ NO | ❌ NO (solo contable) |

---

## 🔄 Flujo Completo del Proceso

### **FASE 1: Creación y Construcción de la OT**

```
1. Se crea OT (final_amount = 0)
2. Se agregan repuestos → ApWorkOrderParts
3. Se agregan servicios → WorkOrderLabour
4. calculateTotals() → final_amount = 1500
5. Estado: FINISHED (lista para facturar)
```

**Métodos clave:**
- `ApWorkOrderPartsService::store()` → Agrega repuesto y recalcula
- `WorkOrderLabourService::store()` → Agrega mano de obra y recalcula
- `ApWorkOrder::calculateTotals()` → Suma todo y actualiza `final_amount`

---

### **FASE 2: Anticipos (Opcional)**

```
Cliente puede pagar 1 o más anticipos ANTES de la factura final

Ejemplo:
- Día 1: Anticipo 1 = 600 (F001-123)
- Día 3: Anticipo 2 = 400 (F001-124)
Total anticipos: 1000
```

**Creación de anticipo:**
```php
ElectronicDocument::create([
  'work_order_id' => 123,
  'is_advance_payment' => 1,          // ← CLAVE
  'total' => 600,
  'sunat_concept_document_type_id' => ElectronicDocument::TYPE_FACTURA,
  'aceptada_por_sunat' => false,      // Inicialmente
]);
```

**¿Se puede editar la OT después de emitir anticipos?**

✅ **SÍ**, pero con validación:

```php
// ANTES (bloqueaba TODO):
if ($workOrder->advancesWorkOrder()->exists()) {
  throw new Exception('No se puede editar porque ya tiene avances');
}

// AHORA (valida monto mínimo):
$projectedFinalAmount = $currentFinalAmount + $delta;
$workOrder->validateMinimumAmount($projectedFinalAmount);
// Lanza excepción si $projectedFinalAmount < monto pagado en anticipos
```

**Reglas:**
1. ✅ Puedes AGREGAR repuestos/servicios (sube el monto)
2. ✅ Puedes EDITAR si el nuevo `final_amount >= monto pagado`
3. ❌ NO puedes reducir el `final_amount` por debajo del monto pagado

---

### **FASE 3: Factura Final**

```
Cuando la OT está terminada, se emite la FACTURA FINAL
```

**Creación:**
```php
// Calcular anticipo neto
$netAdvances = $workOrder->getNetAmountFromAdvances(); // 1000

// Total a facturar
$totalToInvoice = $workOrder->final_amount - $netAdvances; // 1500 - 1000 = 500

ElectronicDocument::create([
  'work_order_id' => 123,
  'is_advance_payment' => 0,          // ← Factura FINAL
  'total' => 500,                     // Solo el saldo pendiente
  'total_anticipo' => 1000,           // Campo que guarda anticipos deducidos
  'sunat_concept_document_type_id' => ElectronicDocument::TYPE_FACTURA,
]);
```

**En el PDF aparece:**
```
Subtotal:           1270.00
IGV (18%):           230.00
Total:              1500.00
(-) Anticipos:     -1000.00
──────────────────────────────
Total a pagar:       500.00
```

**Caso especial: Factura por 0 soles**
```php
// Si anticipos cubren el 100%:
$netAdvances = 1500;
$totalToInvoice = 1500 - 1500 = 0;

// Se emite igual (necesario para SUNAT y cerrar proceso)
ElectronicDocument::create([
  'total' => 0,
  'total_anticipo' => 1500,
  'is_advance_payment' => 0,
]);
```

---

### **FASE 4: Contabilización en Dynamics (Automático)**

**Job:** `SyncAccountingStatusJob`
**Frecuencia:** Ejecuta cada X minutos (configurado en queue worker)
**Trigger:** Busca documentos con `migration_status = COMPLETED` y `is_accounted = false`

```php
// Cuando detecta que la factura final está en Dynamics:
if ($sopRecord && !$isAnnulled) {
  $document->update(['is_accounted' => true]);

  // Si es factura final (is_advance_payment = 0):
  $this->createInventoryMovementForWorkOrder($workOrderId);
}
```

**¿Qué hace `createInventoryMovementForWorkOrder()`?**

1. Verifica que `is_invoiced = false` (no procesada aún)
2. Verifica que `output_generation_warehouse = false`
3. Llama a `InventoryMovementService::createSaleFromWorkOrder()`
4. Genera salida de inventario con TODOS los repuestos de `ApWorkOrderParts`
5. Actualiza OT:
   ```php
   $workOrder->update([
     'is_invoiced' => true,
     'status_id' => ApMasters::CLOSED_WORK_ORDER_ID,
     'output_generation_warehouse' => true,
   ]);
   ```

**IMPORTANTE:** El inventario se genera CON EL TOTAL de repuestos, NO proporcional a la factura final.

---

## 🛠️ Modelos y Métodos Principales

### **ApWorkOrder.php**

#### **`getActiveAdvances(): Collection`**
```php
// Retorna anticipos activos (sin NC de anulación)
// Filtra:
// - is_advance_payment = 1
// - aceptada_por_sunat = true
// - Excluye si tiene NC de anulación (credit_note_id con tipo 01 o 06)
```

**Ejemplo retorno:**
```php
Collection [
  ElectronicDocument { id: 1, total: 600, ... },
  ElectronicDocument { id: 2, total: 400, ... },
]
```

---

#### **`getFinalInvoice(): ElectronicDocument|null`**
```php
// Retorna la factura final (factura/boleta) de la orden de trabajo
// Filtra:
// - is_advance_payment = 0 (NO es anticipo)
// - aceptada_por_sunat = true
// - Tipo FACTURA o BOLETA
// - NO cancelada (status != cancelled && anulado != 1)
// - NO anulada por NC
```

**Ejemplo retorno:**
```php
ElectronicDocument {
  id: 5,
  full_number: 'F001-00150',
  total: 500,
  is_advance_payment: 0,
  aceptada_por_sunat: true
}
```

**Uso:**
```php
$workOrder = ApWorkOrder::with('advancesWorkOrder')->find(123);
$finalInvoice = $workOrder->getFinalInvoice();

if ($finalInvoice) {
  echo "Factura final: " . $finalInvoice->full_number;
} else {
  echo "No hay factura final aún";
}
```

---

#### **`getValidDocuments(): Collection`**
```php
// Retorna TODOS los documentos válidos de la OT
// Incluye:
// - Anticipos activos (de getActiveAdvances)
// - Factura final (de getFinalInvoice)
```

**Ejemplo retorno:**
```php
Collection [
  ElectronicDocument { id: 1, total: 600, is_advance_payment: 1 },  // Anticipo 1
  ElectronicDocument { id: 2, total: 400, is_advance_payment: 1 },  // Anticipo 2
  ElectronicDocument { id: 5, total: 500, is_advance_payment: 0 },  // Factura final
]
```

**Uso:**
```php
$workOrder = ApWorkOrder::with('advancesWorkOrder')->find(123);
$allDocuments = $workOrder->getValidDocuments();

echo "Total documentos: " . $allDocuments->count();

foreach ($allDocuments as $doc) {
  if ($doc->is_advance_payment) {
    echo "Anticipo: " . $doc->full_number;
  } else {
    echo "Factura final: " . $doc->full_number;
  }
}
```

**Ventajas sobre usar directamente `advancesWorkOrder`:**
- ✅ Filtra automáticamente documentos anulados
- ✅ Incluye tanto anticipos como factura final
- ✅ Retorna solo documentos válidos para mostrar al cliente
- ✅ No incluye NC, ND, ni documentos rechazados

---

#### **`getNetAmountFromAdvances(): float`**
```php
// Calcula el monto NETO pagado considerando NC/ND sobre anticipos
// Lógica:
// 1. Obtiene anticipos activos
// 2. Por cada anticipo:
//    - Resta NC parciales (NO de anulación)
//    - Suma ND
// 3. Retorna total neto
```

**Ejemplo cálculo:**
```php
Anticipo 1: 600
  - NC parcial: -50 (descuento comercial)
  Neto: 550

Anticipo 2: 400
  + ND: +30 (cargo adicional)
  Neto: 430

Total neto: 980
```

**Código:**
```php
public function getNetAmountFromAdvances(): float
{
  $activeAdvances = $this->getActiveAdvances();
  $totalNet = 0;

  foreach ($activeAdvances as $advance) {
    $netAmount = $advance->total;

    // Restar NC (excepto anulación/devolución total)
    $creditNotes = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->whereNotIn('sunat_concept_credit_note_type_id', [
        SunatConcepts::ID_CREDIT_NOTE_ANULACION,
        SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
      ])
      ->get();

    foreach ($creditNotes as $nc) {
      $netAmount -= $nc->total;
    }

    // Sumar ND
    $debitNotes = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($debitNotes as $nd) {
      $netAmount += $nd->total;
    }

    $totalNet += $netAmount;
  }

  return (float)$totalNet;
}
```

---

#### **`validateMinimumAmount(float $newFinalAmount): void`**
```php
// Valida que el nuevo monto de la OT no sea menor al ya pagado
// Se usa ANTES de permitir ediciones que reduzcan el monto

public function validateMinimumAmount(float $newFinalAmount): void
{
  $paidAmount = $this->getNetAmountFromAdvances();

  if ($paidAmount > 0 && $newFinalAmount < $paidAmount) {
    throw new \Exception(
      "El nuevo monto total (S/. " . number_format($newFinalAmount, 2) . ") " .
      "no puede ser menor al monto ya pagado en anticipos (S/. " . number_format($paidAmount, 2) . "). " .
      "Debe anular los anticipos correspondientes antes de reducir el monto de la orden de trabajo."
    );
  }
}
```

**Uso en servicios:**
```php
// ApWorkOrderPartsService::update()
$projectedFinalAmount = $currentTotal + ($newNetAmount - $oldNetAmount);
$workOrder->validateMinimumAmount($projectedFinalAmount);

// ApWorkOrderPartsService::destroy()
$projectedFinalAmount = $currentTotal - $partNetAmount;
$workOrder->validateMinimumAmount($projectedFinalAmount);
```

---

#### **`calculateTotals(): void`**
```php
// Recalcula TODOS los totales de la OT
// 1. Suma net_amount de ApWorkOrderParts → total_parts_cost
// 2. Suma net_amount de WorkOrderLabour → total_labor_cost
// 3. Calcula subtotal, descuentos, IGV
// 4. Actualiza final_amount

// Se llama SIEMPRE que se agrega/edita/elimina repuesto o mano de obra
```

---

### **ApWorkOrderPartsService.php**

#### **`store(array $data): ApWorkOrderPartsResource`**
```php
// Agrega un repuesto a la OT
// Validaciones:
// - Producto no duplicado
// - Stock disponible
// - Precio >= precio de venta público
// - NO valida anticipos (solo permite agregar)

// Flujo:
1. Calcular precios y totales (calculatePricesAndTotals)
2. Validar stock en Dynamics
3. Crear ApWorkOrderParts
4. Reservar stock
5. Recalcular totales de OT (calculateTotals)
```

---

#### **`update(array $data): ApWorkOrderPartsResource`**
```php
// Actualiza un repuesto existente
// NUEVA validación de anticipos:

// Calcular nuevo monto proyectado
$oldNetAmount = $workOrderPart->net_amount;
$newNetAmount = $recalcData['net_amount'];
$deltaNetAmount = $newNetAmount - $oldNetAmount;
$projectedFinalAmount = $currentTotal + $deltaNetAmount;

// Validar
$workOrder->validateMinimumAmount($projectedFinalAmount);
// Si pasa, permite la edición
```

---

#### **`destroy(int $id): JsonResponse`**
```php
// Elimina un repuesto
// NUEVA validación:

$projectedFinalAmount = $currentTotal - $workOrderPart->net_amount;
$workOrder->validateMinimumAmount($projectedFinalAmount);
// Si pasa, permite eliminar
```

---

### **SyncAccountingStatusJob.php**

#### **`reverseForDevolucionParcial(ElectronicDocument $creditNote, ElectronicDocument $originalDocument): void`**
```php
// Procesa NC por devolución de ítem (código 02)
// Solo aplica a facturas finales sobre órdenes de trabajo

// Flujo:
1. Leer ítems de la NC (creditNote->items)
2. Por cada ítem:
   a. Buscar ApWorkOrderParts correspondiente
   b. Restar cantidad devuelta
   c. Si cantidad llega a 0 → eliminar ApWorkOrderParts
   d. Si cantidad > 0 → actualizar y recalcular montos
   e. Devolver stock al almacén
3. Recalcular totales de OT (calculateTotals)
```

**Ejemplo:**
```php
// NC por devolución de 2 repuestos (producto_id = 10)
$item->quantity = 2;

// ApWorkOrderParts original:
$workOrderPart->quantity_used = 5;
$workOrderPart->unit_price = 200;
$workOrderPart->net_amount = 1000;

// Después de procesar:
$workOrderPart->quantity_used = 3;
$workOrderPart->total_cost = 600;
$workOrderPart->net_amount = 600; // Recalculado con descuento si había

// Stock devuelto:
ProductWarehouseStock::increment('stock_quantity', 2);

// OT recalculada:
$workOrder->final_amount = 1028 (antes 1500)
```

---

## 📊 Casos de Uso Detallados

### **CASO 1: OT sin anticipos → Factura final directa**

```
1. OT creada: final_amount = 1500
2. Cliente NO paga anticipos
3. Se finaliza trabajo
4. Se emite factura final:
   - total = 1500
   - total_anticipo = 0
   - is_advance_payment = 0
5. Se contabiliza en Dynamics
6. SyncAccountingStatusJob:
   - Genera salida de inventario (5 repuestos)
   - OT → CLOSED, is_invoiced = true
```

---

### **CASO 2: OT con múltiples anticipos → Factura final con deducción**

```
1. OT: final_amount = 1500
2. Cliente paga:
   - Anticipo 1: 600 (día 1)
   - Anticipo 2: 400 (día 3)
3. getNetAmountFromAdvances() = 1000
4. Se emite factura final:
   - total = 500 (1500 - 1000)
   - total_anticipo = 1000
5. Contabilización → Genera salida inventario COMPLETA (5 repuestos)
6. OT → CLOSED
```

---

### **CASO 3: Factura final por 0 soles (anticipos cubren 100%)**

```
1. OT: final_amount = 1500
2. Cliente paga:
   - Anticipo 1: 500
   - Anticipo 2: 1000
3. getNetAmountFromAdvances() = 1500
4. Se emite factura final:
   - total = 0
   - total_anticipo = 1500
   - is_advance_payment = 0
5. Contabilización → Genera salida inventario COMPLETA
6. OT → CLOSED

NOTA: Factura por 0 soles es OBLIGATORIA para cerrar el proceso
```

---

### **CASO 4: Cliente desiste → NC de anulación sobre factura final por 0 soles**

```
Continuando CASO 3:

7. Cliente desiste
8. Se emite NC de anulación sobre factura final:
   - original_document_id = factura_final_id
   - total = 0
   - sunat_concept_credit_note_type_id = 01 (anulación)
9. SyncAccountingStatusJob al contabilizarse:
   - Detecta NC sobre factura final (is_advance_payment = 0)
   - Llama reverseForAnulacion()
   - Revierte inventario (devuelve 5 repuestos)
   - OT → FINISHED, is_invoiced = false
10. Se emiten NC sobre anticipos:
    - NC sobre anticipo 1: -500
    - NC sobre anticipo 2: -1000
    (Solo ajuste contable, NO tocan inventario)
```

---

### **CASO 5: Editar OT después de recibir anticipos**

#### **Escenario A: Aumentar monto (agregar repuesto)**
```
1. OT: final_amount = 1500
2. Anticipo pagado: 600
3. Usuario agrega repuesto de 200
4. Sistema:
   - NO valida (agregar siempre está permitido)
   - Crea ApWorkOrderParts
   - Recalcula: final_amount = 1700
   - Cliente ahora debe: 1700 - 600 = 1100
```

#### **Escenario B: Reducir monto (aplicar descuento) - PERMITIDO**
```
1. OT: final_amount = 1500
2. Anticipo pagado: 600
3. Usuario aplica 5% descuento → nuevo total = 1425
4. Sistema valida:
   - ¿1425 >= 600? SÍ ✅
   - Permite la edición
   - final_amount = 1425
   - Cliente debe: 1425 - 600 = 825
```

#### **Escenario C: Reducir monto excesivo - BLOQUEADO**
```
1. OT: final_amount = 1500
2. Anticipo pagado: 600
3. Usuario intenta reducir a 500 (elimina repuestos)
4. Sistema valida:
   - ¿500 >= 600? NO ❌
   - Lanza excepción:
     "El nuevo monto total (S/. 500.00) no puede ser menor
      al monto ya pagado en anticipos (S/. 600.00).
      Debe anular los anticipos antes..."
5. Edición RECHAZADA
```

**Solución:** Anular anticipo primero (NC), después editar OT.

---

### **CASO 6: NC parcial sobre anticipo**

```
1. OT: final_amount = 1500
2. Anticipo: 600
3. Gerencia aprueba descuento comercial de 50
4. Se emite NC parcial sobre anticipo:
   - original_document_id = anticipo_id
   - total = 50
   - sunat_concept_credit_note_type_id = 07 (descuento)
5. getNetAmountFromAdvances():
   - Anticipo: 600
   - NC: -50
   - Neto: 550
6. Cliente debe: 1500 - 550 = 950
7. final_amount de OT NO cambia (sigue en 1500)
```

---

### **CASO 7: NC por devolución parcial de ítems en factura final**

```
1. OT: final_amount = 1500 (5 repuestos × 200 + IGV)
2. Factura final emitida y contabilizada
3. Inventario: Ya salieron 5 repuestos
4. Cliente devuelve 2 repuestos (producto_id = 10)
5. Se emite NC por ítem:
   - sunat_concept_credit_note_type_id = 02
   - items = [{ product_id: 10, quantity: 2, unit_price: 200 }]
   - total = 472 (2 × 200 × 1.18)
6. SyncAccountingStatusJob al contabilizarse:
   - Llama reverseForDevolucionParcial()
   - Busca ApWorkOrderParts con product_id = 10
   - Actualiza: quantity_used = 5 - 2 = 3
   - Recalcula: net_amount = 3 × 200 = 600
   - Devuelve stock: +2 repuestos
   - Recalcula OT: final_amount = 1028
7. Resultado:
   - OT sigue CLOSED
   - final_amount actualizado: 1028
   - Stock devuelto: +2
   - Saldo contable: 1500 - 472 = 1028 ✅
```

---

### **CASO 8: NC por descuento global sobre factura final**

```
1. OT: final_amount = 1500
2. Factura final emitida: 1500
3. Gerencia aprueba 10% descuento por demora
4. Se emite NC por descuento:
   - sunat_concept_credit_note_type_id = 07
   - total = 150
5. SyncAccountingStatusJob:
   - Detecta tipo 07 → NO hace reversión
   - Solo log informativo
6. Resultado:
   - OT NO cambia: final_amount = 1500
   - Inventario NO se toca
   - ApWorkOrderParts NO se toca
   - Solo ajuste contable: 1500 - 150 = 1350
```

---

## ⚖️ Validaciones y Reglas de Negocio

### **1. Creación/Edición de Repuestos**

| Acción | Validación | ¿Bloquea? |
|--------|------------|-----------|
| Agregar repuesto | Stock disponible | ✅ Sí |
| Agregar repuesto | Precio >= PVP | ✅ Sí |
| Agregar repuesto | OT tiene anticipos | ❌ NO (permitido) |
| Editar repuesto | Nuevo monto >= anticipos pagados | ✅ Sí |
| Eliminar repuesto | Nuevo monto >= anticipos pagados | ✅ Sí |

### **2. Emisión de Factura Final**

```php
// Validaciones:
1. OT debe estar en FINISHED
2. OT NO debe estar CLOSED
3. Total a facturar >= 0 (puede ser 0 si anticipos cubren todo)
4. Si total < 0 → Error (anticipos superan el monto)
```

### **3. Contabilización de Documentos**

| Documento | Genera Inventario | Cierra OT | Condición |
|-----------|-------------------|-----------|-----------|
| Anticipo | ❌ NO | ❌ NO | - |
| Factura Final | ✅ SÍ | ✅ SÍ | `is_accounted = true` en Dynamics |
| NC Anulación (factura final) | ✅ Revierte | ❌ Reabre | Contabilizada |
| NC por ítem (factura final) | ✅ Devuelve parcial | ❌ NO | Contabilizada |
| NC Descuento | ❌ NO | ❌ NO | - |

### **4. Reversión de Inventario**

```php
// Solo se revierte inventario si:
1. Es NC sobre FACTURA FINAL (is_advance_payment = 0)
2. Tipo de NC es anulación (01) o devolución total/parcial (06, 02)
3. NC está contabilizada en Dynamics (is_accounted = true)

// NC sobre ANTICIPOS nunca revierte inventario
// (porque los anticipos nunca lo generaron)
```

---

## 🤖 Procesamiento Automático (Jobs)

### **SyncAccountingStatusJob**

**Ubicación:** `app/Jobs/SyncAccountingStatusJob.php`
**Queue:** `electronic_documents`
**Frecuencia:** Configurable (ej: cada 5 minutos)

**Comando para ejecutar:**
```bash
php artisan queue:work --queue=electronic_documents --tries=3
```

---

### **Flujo del Job**

```php
1. Buscar documentos:
   - migration_status = COMPLETED
   - is_accounted = false

2. Por cada documento:
   a. Consultar Dynamics (SOP30200 / RM20101)
   b. Si existe en Dynamics:
      - Actualizar: is_accounted = true
      - Si es factura final → Generar inventario
      - Si es NC → Procesar reversión
   c. Si NO existe:
      - Actualizar: is_accounted = false
```

---

### **Métodos de Reversión**

#### **`reversePostventaStatusIfApplicable()`**
```php
// Decide qué hacer según tipo de NC

// Valida:
1. Área debe ser TALLER o MESON
2. Tipo debe ser NOTA_CREDITO
3. Documento original debe existir
4. Documento original NO debe ser anticipo (is_advance_payment = 0)

// Delega según tipo:
- Tipo 01 (Anulación) → reverseForAnulacion()
- Tipo 06 (Devolución total) → reverseForDevolucionTotal()
- Tipo 02 (Devolución ítem) → reverseForDevolucionParcial()
- Otros → Solo log (no hace nada)
```

#### **`reverseForAnulacion()`**
```php
// Reversión TOTAL
1. Si es OT → reverseWorkOrderStatus()
2. Si es Cotización → reverseQuotationStatus()

// reverseWorkOrderStatus():
a. Revierte inventario (reverseInventoryForWorkOrder)
b. Actualiza OT:
   - status_id = FINISHED
   - is_invoiced = false
   - output_generation_warehouse = false
```

#### **`reverseForDevolucionParcial()`**
```php
// Reversión por ÍTEM
1. Lee items de NC
2. Por cada ítem:
   a. Busca ApWorkOrderParts
   b. Resta cantidad
   c. Recalcula montos
   d. Devuelve stock
3. Recalcula totales OT
```

---

## 📡 API Response Structure

### **WorkOrderResource (Completo)**

```json
{
  "id": 123,
  "correlative": "OT-2026-001",
  "final_amount": 1500.00,
  "is_invoiced": false,
  "status_id": 4,
  "status_name": "Finalizada",

  "advances": [
    {
      "id": 1,
      "full_number": "F001-00123",
      "serie": "F001",
      "number": "00123",
      "emission_date": "2026-06-01",
      "total": 600.00,
      "is_advance_payment": 1,
      "aceptada_por_sunat": true
    },
    {
      "id": 2,
      "full_number": "F001-00124",
      "serie": "F001",
      "number": "00124",
      "emission_date": "2026-06-02",
      "total": 400.00,
      "is_advance_payment": 1,
      "aceptada_por_sunat": true
    }
  ],

  "final_invoice": {
    "id": 5,
    "full_number": "F001-00150",
    "serie": "F001",
    "number": "00150",
    "emission_date": "2026-06-05",
    "total": 500.00,
    "total_anticipo": 1000.00,
    "is_advance_payment": 0,
    "aceptada_por_sunat": true,
    "is_accounted": true
  },

  "valid_documents": [
    {
      "id": 1,
      "full_number": "F001-00123",
      "total": 600.00,
      "is_advance_payment": 1
    },
    {
      "id": 2,
      "full_number": "F001-00124",
      "total": 400.00,
      "is_advance_payment": 1
    },
    {
      "id": 5,
      "full_number": "F001-00150",
      "total": 500.00,
      "is_advance_payment": 0
    }
  ],

  "payment_summary": {
    "total_amount": 1500.00,
    "paid_amount": 1000.00,
    "pending_amount": 500.00,
    "payment_percentage": 66.67,
    "is_fully_paid": false,
    "advances_count": 2
  }
}
```

**Descripción de campos:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `advances` | Array | Solo anticipos activos (is_advance_payment = 1) |
| `final_invoice` | Object\|null | Factura final si existe (is_advance_payment = 0) |
| `valid_documents` | Array | Todos los documentos válidos (anticipos + factura final) |
| `payment_summary` | Object | Resumen de pagos calculado |

**¿Cuándo usar cada campo?**

- **`advances`**: Cuando solo necesitas mostrar los anticipos
- **`final_invoice`**: Cuando necesitas verificar si ya tiene factura final
- **`valid_documents`**: Cuando necesitas mostrar TODOS los documentos al cliente
- **`payment_summary`**: Cuando necesitas mostrar el estado de pago

---

### **Cómo usar en Frontend**

```javascript
// Vue.js ejemplo
const { payment_summary, advances } = workOrder.value;

// Mostrar totales
<div class="totals">
  <span>Total: S/. {{ payment_summary.total_amount }}</span>
  <span>Pagado: S/. {{ payment_summary.paid_amount }}</span>
  <span>Pendiente: S/. {{ payment_summary.pending_amount }}</span>
</div>

// Progress bar
<div class="progress">
  <div :style="{ width: payment_summary.payment_percentage + '%' }"></div>
</div>

// Badge
<span v-if="payment_summary.is_fully_paid" class="badge-success">
  Totalmente Pagado
</span>

// Validaciones
const canAddMoreAdvances = computed(() =>
  !payment_summary.is_fully_paid && payment_summary.pending_amount > 0
);

const canGenerateFinalInvoice = computed(() =>
  !workOrder.value.is_invoiced && payment_summary.pending_amount >= 0
);
```

---

## 🚨 Escenarios Edge Cases

### **Edge Case 1: Anticipos superan el monto de la OT**

```
OT: final_amount = 1500
Anticipo 1: 1000
Anticipo 2: 700
Total anticipos: 1700

¿Qué pasa al emitir factura final?

→ Error en createFinalInvoice():
  "Los anticipos (1700) superan el monto total de la OT (1500).
   Verifique los anticipos emitidos."
```

**Solución:** Anular uno de los anticipos o emitir NC para ajustar.

---

### **Edge Case 2: Editar OT entre emisión y contabilización de factura final**

```
1. Se emite factura final (total = 500)
2. Factura aún NO se contabiliza en Dynamics
3. Usuario intenta editar OT

→ Permitido SOLO si:
  - is_invoiced = false (aún no contabilizada)
  - Nuevo monto >= anticipos pagados
```

**Si ya está contabilizada (`is_invoiced = true`):**
```php
// En ApWorkOrder::ensureCanBeModified()
if ($this->is_invoiced) {
  throw new Exception('No se puede modificar una OT cerrada/facturada');
}
```

---

### **Edge Case 3: NC por ítem con cantidad mayor a la registrada**

```
ApWorkOrderParts: quantity_used = 5
NC por ítem: quantity = 7

→ El método reverseForDevolucionParcial():
  $newQuantity = 5 - 7 = -2

  if ($newQuantity <= 0) {
    $workOrderPart->delete(); // Elimina el repuesto completamente
  }
```

**Resultado:** Se elimina el repuesto de la OT (asume que hubo error en la emisión).

---

### **Edge Case 4: Múltiples NC sobre el mismo anticipo**

```
Anticipo: 600
NC 1: -50 (descuento comercial)
NC 2: -30 (ajuste por error)
ND 1: +20 (cargo adicional)

getNetAmountFromAdvances():
600 - 50 - 30 + 20 = 540
```

**Sistema soporta múltiples NC/ND sobre un mismo anticipo.**

---

## 🔧 Troubleshooting

### **Problema 1: "No se puede editar porque ya tiene avances"**

**Causa:** Código antiguo bloqueaba TODO cuando había anticipos.
**Solución:** Código actualizado permite edición con validación de monto mínimo.

**Verificar:**
```php
// ApWorkOrderPartsService::update()
// Debe tener:
$workOrder->validateMinimumAmount($projectedFinalAmount);

// NO debe tener:
if ($workOrder->advancesWorkOrder()->exists()) {
  throw new Exception('...');
}
```

---

### **Problema 2: Inventario no se revierte al anular factura final**

**Verificar:**
1. NC está contabilizada en Dynamics (`is_accounted = true`)
2. `original_document_id` apunta correctamente a la factura final
3. Documento original tiene `is_advance_payment = 0`
4. Queue worker está corriendo:
   ```bash
   php artisan queue:work --queue=electronic_documents
   ```

**Revisar logs:**
```php
// storage/logs/laravel.log
[2026-06-03] Orden de trabajo revertida por NC contabilizada
```

---

### **Problema 3: final_amount no se actualiza después de NC por ítem**

**Verificar:**
1. NC tipo es `ID_CREDIT_NOTE_DEVOLUCION_ITEM (02)`
2. Job procesó la NC (revisar logs)
3. `ApWorkOrder::calculateTotals()` se llamó al final

**Debug:**
```php
// En SyncAccountingStatusJob::reverseForDevolucionParcial()
Log::info('NC por ítem procesada', [
  'work_order_id' => $workOrder->id,
  'final_amount_antes' => $finalAmountAntes,
  'final_amount_despues' => $workOrder->final_amount,
]);
```

---

### **Problema 4: payment_summary no aparece en API**

**Verificar:**
```php
// En WorkOrderService::show()
$workOrder->load('advancesWorkOrder'); // ← Debe estar cargado

// WorkOrderResource debe tener:
'payment_summary' => $this->when(
  $this->relationLoaded('advancesWorkOrder'),
  fn() => [...]
),
```

---

## 📚 Referencias Rápidas

### **Constantes Importantes**

```php
// ElectronicDocument
TYPE_FACTURA = 1
TYPE_BOLETA = 2
TYPE_NOTA_CREDITO = 3
TYPE_NOTA_DEBITO = 4

// SunatConcepts (NC)
ID_CREDIT_NOTE_ANULACION = 1
ID_CREDIT_NOTE_DEVOLUCION_ITEM = 2
ID_CREDIT_NOTE_DEVOLUCION_TOTAL = 6
ID_CREDIT_NOTE_DESCUENTO_GLOBAL = 7

// ApMasters (Estados OT)
OPEN_WORK_ORDER_ID
IN_PROGRESS_WORK_ORDER_ID
FINISHED_WORK_ORDER_ID
CLOSED_WORK_ORDER_ID
CANCELED_WORK_ORDER_ID
```

---

### **Archivos Clave**

```
Models:
- app/Models/ap/postventa/taller/ApWorkOrder.php
- app/Models/ap/postventa/taller/ApWorkOrderParts.php
- app/Models/ap/facturacion/ElectronicDocument.php

Services:
- app/Http/Services/ap/postventa/taller/ApWorkOrderPartsService.php
- app/Http/Services/ap/postventa/taller/WorkOrderLabourService.php
- app/Http/Services/ap/postventa/gestionProductos/InventoryMovementService.php

Jobs:
- app/Jobs/SyncAccountingStatusJob.php

Resources:
- app/Http/Resources/ap/postventa/taller/WorkOrderResource.php
```

---

### **Comandos Útiles**

```bash
# Ejecutar queue worker
php artisan queue:work --queue=electronic_documents --tries=3

# Ver trabajos pendientes
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Limpiar cola
php artisan queue:flush
```

---

## ✅ Checklist de Implementación

Cuando implementes una nueva funcionalidad relacionada:

- [ ] ¿Afecta el cálculo de `final_amount`? → Llamar `calculateTotals()`
- [ ] ¿Permite editar OT con anticipos? → Agregar `validateMinimumAmount()`
- [ ] ¿Es un nuevo tipo de documento? → Actualizar `SyncAccountingStatusJob`
- [ ] ¿Modifica inventario? → Considerar reversión en NC
- [ ] ¿Necesita mostrar pagos? → Usar `payment_summary` en Resource
- [ ] ¿Nueva validación de negocio? → Documentar en esta guía

---

## 📝 Changelog

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2026-06-03 | 1.1 | Agregados métodos `getFinalInvoice()` y `getValidDocuments()` en ApWorkOrder. Agregados campos `final_invoice` y `valid_documents` en WorkOrderResource y WorkOrderBasicInfoResource. Creado WorkOrderBasicInfoResource para InventoryMovementResource. |
| 2026-06-03 | 1.0 | Documentación inicial completa |

---

## 🆕 Cambios Recientes (v1.1)

### **Nuevos Métodos en ApWorkOrder**

#### `getFinalInvoice(): ElectronicDocument|null`
- Retorna la factura final (no anticipo) de la orden de trabajo
- Filtra documentos con `is_advance_payment = 0`
- Excluye facturas canceladas o anuladas

#### `getValidDocuments(): Collection`
- Retorna TODOS los documentos válidos (anticipos + factura final)
- Combina `getActiveAdvances()` + `getFinalInvoice()`
- Útil para mostrar al cliente todos sus comprobantes

### **Nuevos Campos en Resources**

#### WorkOrderResource & WorkOrderBasicInfoResource
```php
'final_invoice' => $this->when(...),     // Factura final si existe
'valid_documents' => ElectronicDocumentResource::collection(...),  // Todos los docs
```

### **Nueva Resource: WorkOrderBasicInfoResource**
- Resource simplificada para contextos de inventario
- Incluye solo campos esenciales + advances + final_invoice
- Usado en `InventoryMovementResource` para optimizar payload
- Ubicación: `app/Http/Resources/ap/postventa/taller/WorkOrderBasicInfoResource.php`

### **Motivación de los Cambios**

**Problema anterior:**
- `advances` solo mostraba anticipos (`is_advance_payment = 1`)
- La factura final no aparecía en el frontend
- Clientes no veían el comprobante final en movimientos de inventario

**Solución:**
- Método `getFinalInvoice()` para obtener factura final específicamente
- Método `getValidDocuments()` para obtener TODOS los documentos válidos
- Campos separados en Resources para mayor flexibilidad
- Resource optimizada para inventarios (WorkOrderBasicInfoResource)

---

**Fin de la documentación técnica**

Para consultas o actualizaciones, contactar al equipo de desarrollo backend.