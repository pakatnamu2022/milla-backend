# Documentación: Cálculo del PVP (Precio de Venta al Público)

## Índice
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Flujo de Cálculo](#flujo-de-cálculo)
3. [Fórmulas Utilizadas](#fórmulas-utilizadas)
4. [Parámetros del Sistema](#parámetros-del-sistema)
5. [Ejemplos Detallados](#ejemplos-detallados)
6. [Conversión de Monedas](#conversión-de-monedas)
7. [Casos Especiales](#casos-especiales)

---

## Resumen Ejecutivo

El sistema calcula automáticamente el **Precio de Venta al Público (PVP)** cada vez que se añade stock a un almacén mediante el método `addStock()`. El cálculo se basa en:

- **Costo Promedio Ponderado** de las compras
- **Margen de Ganancia** configurado en el sistema
- **Comisión de Flete** configurada en el sistema
- **Conversión automática** a moneda base (PEN)

---

## Flujo de Cálculo

### Diagrama de Flujo

```
┌─────────────────────────────────────────┐
│  Llamada a addStock()                   │
│  - Cantidad                             │
│  - Costo unitario                       │
│  - Moneda (opcional)                    │
│  - Tipo de cambio (opcional)            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  1. Convertir costo a PEN               │
│     (si la moneda es diferente)         │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  2. Calcular Costo Promedio Ponderado  │
│     average_cost = (stock_actual × costo_actual +  │
│                     cantidad_nueva × costo_nuevo)  │
│                    ÷ (stock_actual + cantidad_nueva)│
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  3. Actualizar cost_price               │
│     (último costo de compra)            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  4. Calcular PVP (sale_price)           │
│     Usando MÉTODO 2 (configurado):      │
│     PVP = average_cost ÷                │
│           (1 - (margen + flete))        │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  5. Guardar en base de datos            │
│     - average_cost                      │
│     - cost_price                        │
│     - sale_price (PVP)                  │
└─────────────────────────────────────────┘
```

---

## Fórmulas Utilizadas

### 1. Conversión a Moneda Base (PEN)

```php
// Si la moneda es USD u otra
costo_en_PEN = costo_original × tipo_cambio

// Ejemplo:
// Costo: $100 USD
// Tipo de cambio: 3.75
// Costo en PEN = 100 × 3.75 = S/ 375.00
```

**Ubicación en código:** `ProductWarehouseStockService.php:175-194`

### 2. Costo Promedio Ponderado

```php
nuevo_costo_promedio = (stock_actual × costo_promedio_actual + cantidad_nueva × costo_unitario_nuevo)
                       ÷ (stock_actual + cantidad_nueva)
```

**Ubicación en código:** `ProductWarehouseStockService.php:121-128`

### 3. Precio de Venta Público (PVP)

El sistema soporta **dos métodos de cálculo**. Actualmente está configurado el **MÉTODO 2**.

#### MÉTODO 1 (No activo)
```php
PVP = (Costo_Promedio ÷ (1 - margen_ganancia)) × (1 + comision_flete)
```

#### MÉTODO 2 (Activo - Por defecto)
```php
PVP = Costo_Promedio ÷ (1 - (margen_ganancia + comision_flete))
```

**Ubicación en código:** `ProductWarehouseStockService.php:137-149`
**Configuración:** `ProductWarehouseStockService.php:20` (`PRICE_CALCULATION_METHOD = 2`)

---

## Parámetros del Sistema

Los parámetros se obtienen de la tabla `general_masters`:

| Parámetro | ID Constante | Valor por Defecto | Descripción |
|-----------|-------------|-------------------|-------------|
| **Comisión de Flete** | `FREIGHT_COMMISSION_ID` | 5% (0.05) | Porcentaje adicional por costos de transporte/logística |
| **Margen de Ganancia** | `PROFIT_MARGIN_ID` | 30% (0.30) | Margen de utilidad esperado |
| **Descuento Mínimo** | `ADVISOR_DISCOUNT_PERCENTAGE_PV_ID` | 5% (0.05) | Descuento máximo que puede aplicar un asesor |

**Ubicación en código:**
- Comisión de Flete: `ProductWarehouseStockService.php:31-37`
- Margen de Ganancia: `ProductWarehouseStockService.php:44-50`
- Descuento Mínimo: `ProductWarehouseStockService.php:52-58`

---

## Ejemplos Detallados

### Ejemplo 1: Primera Compra (Sin Stock Previo)

**Datos iniciales:**
- Stock actual: 0 unidades
- Costo promedio actual: S/ 0.00

**Nueva compra:**
- Cantidad: 100 unidades
- Costo unitario: S/ 50.00
- Moneda: PEN (soles)

**Parámetros del sistema:**
- Margen de ganancia: 30% (0.30)
- Comisión de flete: 5% (0.05)

**Cálculos paso a paso:**

```
1. Conversión a PEN:
   No aplica (ya está en PEN)
   costo_en_PEN = S/ 50.00

2. Costo Promedio Ponderado:
   Como no hay stock previo:
   average_cost = S/ 50.00

3. Último Costo de Compra:
   cost_price = S/ 50.00

4. Precio de Venta Público (MÉTODO 2):
   PVP = 50.00 ÷ (1 - (0.30 + 0.05))
   PVP = 50.00 ÷ (1 - 0.35)
   PVP = 50.00 ÷ 0.65
   PVP = S/ 76.92
```

**Resultado en base de datos:**
```json
{
  "quantity": 100,
  "average_cost": 50.00,
  "cost_price": 50.00,
  "sale_price": 76.92,
  "currency_id": 3
}
```

---

### Ejemplo 2: Segunda Compra (Con Stock Previo)

**Datos iniciales:**
- Stock actual: 100 unidades
- Costo promedio actual: S/ 50.00

**Nueva compra:**
- Cantidad: 50 unidades
- Costo unitario: S/ 60.00
- Moneda: PEN

**Cálculos:**

```
1. Costo Promedio Ponderado:
   average_cost = (100 × 50.00 + 50 × 60.00) ÷ (100 + 50)
   average_cost = (5,000 + 3,000) ÷ 150
   average_cost = 8,000 ÷ 150
   average_cost = S/ 53.33

2. Último Costo de Compra:
   cost_price = S/ 60.00

3. PVP (MÉTODO 2):
   PVP = 53.33 ÷ (1 - 0.35)
   PVP = 53.33 ÷ 0.65
   PVP = S/ 82.05
```

**Resultado:**
```json
{
  "quantity": 150,
  "average_cost": 53.33,
  "cost_price": 60.00,
  "sale_price": 82.05
}
```

**Observaciones:**
- El costo promedio aumentó de S/ 50.00 a S/ 53.33
- El PVP se ajustó automáticamente de S/ 76.92 a S/ 82.05
- El `cost_price` refleja el último precio de compra (S/ 60.00)

---

### Ejemplo 3: Compra en Dólares (USD)

**Datos iniciales:**
- Stock actual: 150 unidades
- Costo promedio actual: S/ 53.33

**Nueva compra:**
- Cantidad: 80 unidades
- Costo unitario: $20.00 USD
- Moneda: USD (ID: 2)
- Tipo de cambio: 3.75

**Cálculos:**

```
1. Conversión a PEN:
   costo_en_PEN = 20.00 × 3.75
   costo_en_PEN = S/ 75.00

2. Costo Promedio Ponderado:
   average_cost = (150 × 53.33 + 80 × 75.00) ÷ (150 + 80)
   average_cost = (7,999.50 + 6,000) ÷ 230
   average_cost = 13,999.50 ÷ 230
   average_cost = S/ 60.87

3. Último Costo de Compra:
   cost_price = S/ 75.00  (convertido a PEN)

4. PVP (MÉTODO 2):
   PVP = 60.87 ÷ 0.65
   PVP = S/ 93.65
```

**Resultado:**
```json
{
  "quantity": 230,
  "average_cost": 60.87,
  "cost_price": 75.00,
  "sale_price": 93.65,
  "currency_id": 3
}
```

**Nota importante:** Todos los valores se almacenan en PEN (moneda base).

---

### Ejemplo 4: Comparación entre MÉTODO 1 y MÉTODO 2

**Datos:**
- Costo promedio: S/ 100.00
- Margen: 30% (0.30)
- Flete: 5% (0.05)

**MÉTODO 1:**
```
PVP = (100 ÷ (1 - 0.30)) × (1 + 0.05)
PVP = (100 ÷ 0.70) × 1.05
PVP = 142.86 × 1.05
PVP = S/ 150.00
```

**MÉTODO 2 (Activo):**
```
PVP = 100 ÷ (1 - (0.30 + 0.05))
PVP = 100 ÷ 0.65
PVP = S/ 153.85
```

**Diferencia:** S/ 3.85 (2.57% más alto con Método 2)

**Sistema configurado:** MÉTODO 2

---

## Conversión de Monedas

### Monedas Soportadas

| Moneda | ID Constante | Notas |
|--------|-------------|-------|
| PEN (Soles) | `TypeCurrency::PEN_ID` (3) | Moneda base del sistema |
| USD (Dólares) | `TypeCurrency::USD_ID` (2) | Requiere tipo de cambio |
| Otras | Variable | Requiere tipo de cambio |

### Lógica de Conversión

**Ubicación:** `ProductWarehouseStockService.php:175-194`

```php
// Caso 1: Moneda es PEN o no especificada
if ($currencyId === null || $currencyId === 3) {
    return $amount;  // Sin conversión
}

// Caso 2: Moneda es USD con tipo de cambio
if ($currencyId === 2 && $exchangeRate > 0) {
    return round($amount × $exchangeRate, 2);
}

// Caso 3: Otras monedas con tipo de cambio
if ($exchangeRate > 0) {
    return round($amount × $exchangeRate, 2);
}

// Caso 4: Sin tipo de cambio válido
return $amount;  // Asume que ya está en PEN
```

**Ejemplo completo:**
```php
// Compra de 100 unidades a $50 USD con tipo de cambio 3.80

addStock(
    productId: 123,
    warehouseId: 1,
    quantity: 100,
    unitCost: 50.00,
    currencyId: 2,      // USD
    exchangeRate: 3.80   // S/ 3.80 por dólar
);

// Conversión interna:
// S/ 50.00 × 3.80 = S/ 190.00 PEN
```

---

## Casos Especiales

### Caso 1: Ajuste de Stock sin Costo

**Escenario:** Ajuste de inventario donde se añade cantidad sin costo.

```php
addStock(
    productId: 123,
    warehouseId: 1,
    quantity: 10,
    unitCost: 0  // Sin costo
);
```

**Comportamiento:**
- NO se actualiza el `average_cost`
- NO se actualiza el `cost_price`
- NO se recalcula el `sale_price`
- Solo se incrementa la `quantity`

**Ubicación en código:** `ProductWarehouseStockService.php:114` (condición `if ($unitCost > 0)`)

---

### Caso 2: Recepción de Orden de Compra

**Contexto:** Cuando se recibe mercadería de una orden de compra.

```php
// En InventoryMovement tipo PURCHASE_RECEPTION
$detail->unit_cost = precio_total_facturado ÷ cantidad_recibida;

addStock(
    productId: $detail->product_id,
    warehouseId: $movement->warehouse_id,
    quantity: $detail->quantity,
    unitCost: $detail->unit_cost,
    currencyId: $movement->currency_id,
    exchangeRate: $movement->exchange_rate
);
```

**Ubicación en código:** `ProductWarehouseStockService.php:440-455`

**Nota:** El `unit_cost` incluye todos los costos de la factura prorrateados.

---

### Caso 3: Ajuste Manual con Costo

**Escenario:** Ajuste de inventario inicial con costo conocido.

```php
// Tipo ADJUSTMENT_IN
addStock(
    productId: 123,
    warehouseId: 1,
    quantity: 500,
    unitCost: 45.50,  // Costo manual
    currencyId: 3      // PEN
);
```

**Comportamiento:** Igual que una compra normal, actualiza todos los valores.

---

### Caso 4: Stock Negativo (No Permitido en addStock)

El método `addStock()` **siempre suma** cantidades positivas. Para reducir stock se usa:
- `removeStock()` - Salida de almacén
- `removeInTransitStock()` - Cancelación de pedidos en tránsito

---

## Precio Mínimo de Venta

Además del PVP, el sistema calcula un **precio mínimo de venta** que considera el descuento máximo permitido.

**Fórmula:**
```php
precio_minimo = sale_price × (1 - descuento_minimo)
```

**Ubicación:** `ProductWarehwareStockService.php:838-845`

**Ejemplo:**
```
PVP: S/ 76.92
Descuento permitido: 5%
Precio mínimo = 76.92 × (1 - 0.05)
Precio mínimo = 76.92 × 0.95
Precio mínimo = S/ 73.07
```

**Uso:** El asesor de ventas puede dar hasta 5% de descuento sin aprobación especial.

---

## Resumen de Campos en Base de Datos

| Campo | Descripción | Cálculo |
|-------|-------------|---------|
| `quantity` | Cantidad física en almacén | Suma acumulativa |
| `average_cost` | Costo promedio ponderado | Fórmula de promedio ponderado |
| `cost_price` | Último costo de compra | Último `unitCost` recibido |
| `sale_price` | Precio de venta público (PVP) | `average_cost ÷ (1 - 0.35)` |
| `currency_id` | Moneda | Siempre PEN (3) |

---

## Ejemplo Completo: 3 Compras Consecutivas

### Estado Inicial
```json
{
  "quantity": 0,
  "average_cost": 0.00,
  "cost_price": 0.00,
  "sale_price": 0.00
}
```

### Compra 1: 100 unidades a S/ 50.00
```
average_cost = 50.00
cost_price = 50.00
sale_price = 50.00 ÷ 0.65 = 76.92
quantity = 100
```

### Compra 2: 50 unidades a S/ 60.00
```
average_cost = (100 × 50 + 50 × 60) ÷ 150 = 53.33
cost_price = 60.00
sale_price = 53.33 ÷ 0.65 = 82.05
quantity = 150
```

### Compra 3: 80 unidades a $20 USD (TC: 3.75)
```
Conversión: 20 × 3.75 = 75.00 PEN

average_cost = (150 × 53.33 + 80 × 75.00) ÷ 230 = 60.87
cost_price = 75.00
sale_price = 60.87 ÷ 0.65 = 93.65
quantity = 230
```

### Estado Final
```json
{
  "quantity": 230,
  "average_cost": 60.87,
  "cost_price": 75.00,
  "sale_price": 93.65,
  "currency_id": 3
}
```

---

## Verificación de Valores

Para verificar manualmente los cálculos:

1. **Verificar conversión de moneda:**
   ```
   Costo en PEN = Costo original × Tipo de cambio
   ```

2. **Verificar costo promedio:**
   ```
   Suma total = Σ(cantidad_i × costo_i)
   Cantidad total = Σ(cantidad_i)
   Promedio = Suma total ÷ Cantidad total
   ```

3. **Verificar PVP (Método 2):**
   ```
   PVP = Costo promedio ÷ (1 - 0.35)
   ```

4. **Verificar precio mínimo:**
   ```
   Precio mínimo = PVP × 0.95
   ```

---

## Ubicación de Código Clave

| Funcionalidad | Archivo | Líneas |
|---------------|---------|--------|
| Método de cálculo (constante) | `ProductWarehouseStockService.php` | 20 |
| Parámetros del sistema | `ProductWarehouseStockService.php` | 31-58 |
| Método addStock completo | `ProductWarehouseStockService.php` | 83-165 |
| Conversión de moneda | `ProductWarehouseStockService.php` | 175-194 |
| Cálculo de promedio ponderado | `ProductWarehouseStockService.php` | 121-128 |
| Cálculo de PVP | `ProductWarehouseStockService.php` | 137-149 |
| Precio mínimo de venta | `ProductWarehouseStockService.php` | 838-845 |

---

## Notas Finales

1. **Moneda base:** Todo se almacena en PEN (Soles Peruanos)
2. **Redondeo:** Todos los importes se redondean a 2 decimales
3. **Método activo:** MÉTODO 2 (línea 20 del código)
4. **Actualización automática:** El PVP se recalcula en cada compra
5. **Transacciones:** Todo el proceso está envuelto en una transacción de base de datos

---

**Fecha de documentación:** 2026-04-14
**Versión del código:** Actual (branch: wilmer)
**Archivo fuente:** `app/Http/Services/ap/postventa/gestionProductos/ProductWarehouseStockService.php`