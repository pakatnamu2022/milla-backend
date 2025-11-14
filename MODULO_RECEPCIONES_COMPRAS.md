# 📦 Módulo de Recepciones de Compras

## 🎯 Descripción General

Este módulo maneja el **proceso completo de recepción de mercadería** de órdenes de compra, incluyendo:

- ✅ Recepciones parciales (entregas en múltiples envíos)
- ✅ Productos BONUS (cortesía del proveedor)
- ✅ Productos GIFT (regalos)
- ✅ Productos SAMPLE (muestras)
- ✅ Manejo de productos dañados/rechazados
- ✅ Control de lotes y fechas de vencimiento
- ✅ Trazabilidad completa

---

## 📋 Tablas Creadas

### 1. **Modificación a `ap_purchase_order_item`**

**Archivo**: `2025_11_14_144303_add_product_id_to_ap_purchase_order_item_table.php`

**Campos agregados**:

```sql
- product_id (foreignId, nullable)
→ Relación con products cuando is_vehicle = false

- quantity_received (decimal 10,2, default 0)
→ Cantidad total recibida hasta el momento

- quantity_pending (decimal 10,2, default 0)
→ Cantidad pendiente por recibir (quantity - quantity_received)
```

**Propósito**: Vincular items de OC con el catálogo de productos y controlar recepciones parciales.

---

### 2. **`purchase_receptions`** (Cabecera de Recepción)

**Archivo**: `2025_11_14_144402_create_purchase_receptions_table.php`

**Campos principales**:

```sql
- id
- reception_number (string 50, unique)
→ REC-2025-0001
- purchase_order_id (foreignId)
→ Relación con OC
- reception_date (date)
→ Fecha de recepción física
- warehouse_id (foreignId)
→ Almacén destino
- supplier_invoice_number (string 100)
→ Factura del proveedor
- supplier_invoice_date (date)
→ Fecha factura proveedor
- shipping_guide_number (string 100)
→ Guía de remisión

- status (enum):
  * PENDING_REVIEW
→ Pendiente de revisión
  * APPROVED
→ Aprobado y en inventario
  * REJECTED
→ Rechazado totalmente
  * PARTIAL
→ Parcialmente aceptado

- reception_type (enum):
  * COMPLETE
→ Recepción completa de la OC
  * PARTIAL
→ Recepción parcial, faltan productos

- notes (text)
→ Observaciones generales
- received_by (foreignId
→ users)
→ Usuario que recibió
- reviewed_by (foreignId
→ users)
→ Usuario que aprobó
- reviewed_at (timestamp)
→ Fecha de aprobación
- total_items (integer)
→ Total de líneas recibidas
- total_quantity (decimal 10,2)
→ Suma de cantidades
```

**Propósito**: Registrar cada recepción de mercadería con su documentación.

---

### 3. **`purchase_reception_details`** (Detalle de Recepción)

**Archivo**: `2025_11_14_144402_create_purchase_reception_details_table.php`

⭐ **LA TABLA MÁS IMPORTANTE** - Aquí se maneja todo el control fino.

**Campos principales**:

```sql
- id
- purchase_reception_id (foreignId)
→ Relación con cabecera
- purchase_order_item_id (foreignId, nullable)
→ NULL si es BONUS/GIFT
- product_id (foreignId)
→ Producto recibido

CANTIDADES:
- quantity_received (decimal 10,2)
→ Cantidad que llegó
- quantity_accepted (decimal 10,2)
→ Cantidad aceptada
- quantity_rejected (decimal 10,2)
→ Cantidad rechazada

TIPO DE RECEPCIÓN:
- reception_type (enum):
  * ORDERED
→ Producto que estaba en la OC
  * BONUS
→ Cortesía del proveedor (ej: compras 10, llegan 11)
  * GIFT
→ Regalo puro del proveedor
  * SAMPLE
→ Muestra gratis para prueba

COSTOS:
- unit_cost (decimal 10,2)
→
$0
.
00
para
BONUS/
GIFT/
SAMPLE
-
is_charged
(
boolean
)
→ false para BONUS/GIFT/SAMPLE
- total_cost (decimal 10,2)
→ quantity_accepted * unit_cost

RECHAZOS:
- rejection_reason (enum):
  * DAMAGED
→ Dañado
  * DEFECTIVE
→ Defectuoso
  * EXPIRED
→ Vencido
  * WRONG_PRODUCT
→ Producto equivocado
  * WRONG_QUANTITY
→ Cantidad incorrecta
  * POOR_QUALITY
→ Mala calidad
  * OTHER
→ Otro

- rejection_notes (text)
→ Detalle del rechazo

BONUS/GIFT:
- bonus_reason (string 255)
→ "Promoción Black Friday"

CONTROL DE LOTES:
- batch_number (string 100)
→ Número de lote
- expiration_date (date)
→ Fecha de vencimiento

- notes (text)
→ Notas específicas
```

**Propósito**: Control detallado de cada producto recibido, incluyendo bonus y rechazos.

---

## 🔄 Flujo de Proceso

### **Escenario 1: Recepción Normal**

```
1. Se crea Orden de Compra
   → OC-2025-001: 100 filtros de aceite

2. Llega mercadería (completa)
   → Se crea recepción REC-2025-001
   → status: PENDING_REVIEW
   → reception_type: COMPLETE

3. Se registran detalles:
   → 100 filtros recibidos
   → 100 filtros aceptados
   → 0 filtros rechazados
   → reception_type: ORDERED
   → unit_cost: $5.00
   → is_charged: true

4. Se aprueba la recepción:
   → status: APPROVED
   → Se actualiza quantity_received en ap_purchase_order_item
   → Se actualiza quantity_pending
```

### **Escenario 2: Recepción con BONUS**

```
1. OC-2025-002: 50 aceites

2. Llega mercadería:
   → 50 aceites + 5 aceites de BONUS

3. Se registran detalles:
   Línea 1:
   → product_id: 123 (aceite)
   → quantity_received: 50
   → reception_type: ORDERED
   → unit_cost: $10.00
   → is_charged: true

   Línea 2:
   → product_id: 123 (aceite)
   → quantity_received: 5
   → reception_type: BONUS
   → unit_cost: $0.00
   → is_charged: false
   → bonus_reason: "Promoción por compra mayor a 50 unidades"
   → purchase_order_item_id: NULL (no estaba en la OC)
```

### **Escenario 3: Recepción Parcial con Dañados**

```
1. OC-2025-003: 200 bujías

2. Primera entrega (parcial):
   → Llegan 100 bujías, 5 dañadas
   → reception_type: PARTIAL

   Detalle:
   → quantity_received: 100
   → quantity_accepted: 95
   → quantity_rejected: 5
   → rejection_reason: DAMAGED
   → rejection_notes: "Caja rota, bujías con embalaje dañado"

3. quantity_received en OC item: 95
   quantity_pending en OC item: 105

4. Segunda entrega:
   → Llegan 105 bujías (100 pendientes + 5 de reposición)
   → reception_type: COMPLETE
```

---

## 📊 Modelos Creados

### **PurchaseOrderItem** (Actualizado)

**Archivo**: `app/Models/ap/compras/PurchaseOrderItem.php`

**Relaciones nuevas**:

- `product()` → BelongsTo Products
- `receptionDetails()` → HasMany PurchaseReceptionDetail

**Accessors**:

- `is_fully_received` → bool
- `has_pending_quantity` → bool

**Scopes**:

- `products()` → Solo items de productos (is_vehicle = false)
- `vehicles()` → Solo items de vehículos
- `pendingReception()` → Items con cantidad pendiente
- `fullyReceived()` → Items completamente recibidos

---

### **PurchaseReception**

**Archivo**: `app/Models/ap/compras/PurchaseReception.php`

**Relaciones**:

- `purchaseOrder()` → BelongsTo PurchaseOrder
- `warehouse()` → BelongsTo Warehouse
- `receivedByUser()` → BelongsTo User
- `reviewedByUser()` → BelongsTo User
- `details()` → HasMany PurchaseReceptionDetail

**Accessors**:

- `is_pending_review` → bool
- `is_approved` → bool
- `is_rejected` → bool
- `is_partial` → bool
- `has_bonus_items` → bool
- `has_gift_items` → bool
- `has_rejected_items` → bool

**Scopes**:

- `pendingReview()` → Pendientes de revisión
- `approved()` → Aprobadas
- `rejected()` → Rechazadas
- `byPurchaseOrder($id)` → Por orden de compra
- `byWarehouse($id)` → Por almacén
- `complete()` → Recepciones completas
- `partialReception()` → Recepciones parciales

---

### **PurchaseReceptionDetail**

**Archivo**: `app/Models/ap/compras/PurchaseReceptionDetail.php`

**Relaciones**:

- `reception()` → BelongsTo PurchaseReception
- `purchaseOrderItem()` → BelongsTo PurchaseOrderItem (nullable)
- `product()` → BelongsTo Products

**Accessors**:

- `is_ordered` → bool (tipo ORDERED)
- `is_bonus` → bool (tipo BONUS)
- `is_gift` → bool (tipo GIFT)
- `is_sample` → bool (tipo SAMPLE)
- `has_rejected_quantity` → bool
- `is_fully_accepted` → bool
- `acceptance_rate` → float (% de aceptación)

**Scopes**:

- `ordered()` → Solo productos ordenados
- `bonus()` → Solo bonus
- `gift()` → Solo regalos
- `sample()` → Solo muestras
- `withRejections()` → Con productos rechazados
- `fullyAccepted()` → Totalmente aceptados
- `charged()` → Con costo
- `free()` → Sin costo
- `byProduct($id)` → Por producto

---

## 🚀 Endpoints API (Por Implementar)

### **Recepciones**

```
GET    /api/ap/purchase-receptions              → Listar recepciones
POST   /api/ap/purchase-receptions              → Crear recepción
GET    /api/ap/purchase-receptions/{id}         → Ver recepción
PUT    /api/ap/purchase-receptions/{id}         → Actualizar recepción
DELETE /api/ap/purchase-receptions/{id}         → Eliminar recepción

POST   /api/ap/purchase-receptions/{id}/approve → Aprobar recepción
POST   /api/ap/purchase-receptions/{id}/reject  → Rechazar recepción

GET    /api/ap/purchase-receptions/pending-review      → Pendientes
GET    /api/ap/purchase-receptions/by-order/{order_id} → Por OC
```

---

## 📝 Archivos PENDIENTES de Crear

Para completar el módulo, aún faltan:

### **Resources** (para API responses):

- [ ] `PurchaseReceptionResource.php`
- [ ] `PurchaseReceptionDetailResource.php`

### **Requests** (validaciones):

- [ ] `IndexPurchaseReceptionRequest.php`
- [ ] `StorePurchaseReceptionRequest.php`
- [ ] `UpdatePurchaseReceptionRequest.php`
- [ ] `ApprovePurchaseReceptionRequest.php`

### **Service**:

- [ ] `PurchaseReceptionService.php` (lógica de negocio completa)

### **Controller**:

- [ ] `PurchaseReceptionController.php` (ya existe el archivo vacío)

### **Routes**:

- [ ] Agregar rutas en `routes/api.php`

---

## 🔥 Lógica de Negocio Clave (Para el Service)

### **Al CREAR una recepción**:

1. Validar que la OC exista y esté activa
2. Validar que los productos pertenezcan a la OC (excepto BONUS/GIFT)
3. Validar que no se reciba más de lo ordenado (excepto BONUS/GIFT)
4. Calcular totales automáticamente
5. Generar número de recepción automático (REC-{year}-{correlativo})
6. Estado inicial: PENDING_REVIEW

### **Al APROBAR una recepción**:

1. Actualizar `quantity_received` y `quantity_pending` en `ap_purchase_order_item`
2. Crear movimientos de inventario (`inventory_movements`) - PENDIENTE
3. Actualizar stock en `product_warehouse_stock` - PENDIENTE
4. Cambiar status a APPROVED
5. Registrar `reviewed_by` y `reviewed_at`
6. Si todos los items de la OC están recibidos completamente:
  - Marcar OC como `fully_received`

### **Validaciones importantes**:

- BONUS/GIFT deben tener `unit_cost = 0` y `is_charged = false`
- ORDERED debe tener `purchase_order_item_id` válido
- `quantity_accepted + quantity_rejected` debe = `quantity_received`
- Si hay `quantity_rejected > 0`, debe haber `rejection_reason`

---

## 🎨 Ejemplos de Uso

### **Crear recepción normal**:

```json
POST /api/ap/purchase-receptions
{
  "purchase_order_id": 1,
  "reception_date": "2025-11-14",
  "warehouse_id": 5,
  "supplier_invoice_number": "FACT-001",
  "shipping_guide_number": "GR-001",
  "received_by": 10,
  "notes": "Recepción sin novedad",
  "details": [
    {
      "purchase_order_item_id": 25,
      "product_id": 100,
      "quantity_received": 50,
      "quantity_accepted": 50,
      "quantity_rejected": 0,
      "reception_type": "ORDERED",
      "unit_cost": 10.50,
      "is_charged": true
    }
  ]
}
```

### **Crear recepción con BONUS**:

```json
POST /api/ap/purchase-receptions
{
  "purchase_order_id": 2,
  "reception_date": "2025-11-14",
  "warehouse_id": 5,
  "received_by": 10,
  "details": [
    {
      "purchase_order_item_id": 30,
      "product_id": 150,
      "quantity_received": 100,
      "quantity_accepted": 100,
      "reception_type": "ORDERED",
      "unit_cost": 15.00,
      "is_charged": true
    },
    {
      "purchase_order_item_id": null,
      "product_id": 150,
      "quantity_received": 10,
      "quantity_accepted": 10,
      "reception_type": "BONUS",
      "unit_cost": 0.00,
      "is_charged": false,
      "bonus_reason": "Promoción por compra mayor a 100 unidades"
    }
  ]
}
```

### **Recepción con productos dañados**:

```json
{
  "details": [
    {
      "purchase_order_item_id": 35,
      "product_id": 200,
      "quantity_received": 50,
      "quantity_accepted": 45,
      "quantity_rejected": 5,
      "rejection_reason": "DAMAGED",
      "rejection_notes": "Embalaje dañado durante transporte",
      "reception_type": "ORDERED",
      "unit_cost": 25.00
    }
  ]
}
```

---

## 🔍 Consultas Útiles

### **Ver recepciones pendientes de aprobación**:

```php
PurchaseReception::pendingReview()
    ->with(['purchaseOrder', 'details.product'])
    ->get();
```

### **Ver productos con bonus recibidos**:

```php
PurchaseReceptionDetail::bonus()
    ->with(['product', 'reception'])
    ->get();
```

### **Ver items de OC pendientes de recibir**:

```php
PurchaseOrderItem::products()
    ->pendingReception()
    ->with(['product', 'purchaseOrder'])
    ->get();
```

### **Calcular total de bonus recibidos de un proveedor**:

```php
PurchaseReceptionDetail::bonus()
    ->whereHas('reception.purchaseOrder', function($q) use ($supplierId) {
        $q->where('supplier_id', $supplierId);
    })
    ->sum('quantity_accepted');
```

---

## ⚠️ IMPORTANTE - Antes de Ejecutar Migraciones

1. **Revisar** todas las migraciones creadas
2. **Verificar** que las foreign keys apunten a las tablas correctas
3. **Hacer backup** de la base de datos
4. **Ejecutar** en ambiente de desarrollo primero:
   ```bash
   php artisan migrate
   ```

5. Si todo está bien, ejecutar en producción con:
   ```bash
   php artisan migrate --force
   ```

---

## 📚 Tablas Relacionadas (Para Crear Después)

1. **`inventory_movements`** → Registrar movimientos de entrada al inventario
2. **`product_warehouse_stock`** → Actualizar stock por almacén
3. **`inventory_adjustments`** → Para regularizaciones (robos, pérdidas, etc.)

---

## 🎯 Siguiente Fase

Una vez que completes los archivos pendientes (Resources, Requests, Service, Controller), el módulo estará 100%
funcional y podrás:

1. ✅ Crear órdenes de compra con productos
2. ✅ Registrar recepciones completas o parciales
3. ✅ Manejar bonus y regalos del proveedor
4. ✅ Controlar productos dañados y rechazos
5. ✅ Llevar trazabilidad completa de recepciones
6. ✅ Generar reportes de recepciones

---

## 👨‍💻 Autor

Generado por Claude Code (Anthropic)
Fecha: 14 de Noviembre, 2025

---

## 📞 Soporte

Para preguntas sobre este módulo, revisar:

- Migraciones en: `database/migrations/2025_11_14_*.php`
- Modelos en: `app/Models/ap/compras/`
- Este README: `MODULO_RECEPCIONES_COMPRAS.md`
