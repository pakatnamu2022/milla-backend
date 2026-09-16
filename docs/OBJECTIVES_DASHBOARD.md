# Dashboard de Objetivos - Documentación Técnica

## Índice
1. [Descripción General](#descripción-general)
2. [Estructura de Cálculo](#estructura-de-cálculo)
3. [Fórmulas de Avance](#fórmulas-de-avance)
4. [Estados y Clasificación](#estados-y-clasificación)
5. [Estructura de Respuesta](#estructura-de-respuesta)

---

## Descripción General

El Dashboard de Objetivos permite visualizar el avance de ventas/servicios de cada sede comparado con sus objetivos mensuales establecidos. El sistema calcula automáticamente el progreso en tres áreas principales:

- **TALLER**: Servicios de taller (Internas, P&P, Accesorios, etc.)
- **MESÓN**: Venta de repuestos en mesón
- **PASO VEHICULAR**: Cantidad de vehículos recepcionados

---

## Estructura de Cálculo

### 1. Objetivo Total de la Sede

```
objetivo_total_sede = suma(todos los conceptos de objetivos)
                    = objetivo_taller + objetivo_meson + objetivo_paso_vehicular
```

### 2. Avance Total de la Sede

```
avance_total_sede = avance_taller + avance_meson
```

**NOTA**: El paso vehicular es un indicador de cantidad (no monetario), por lo que NO se suma al avance total en monto.

### 3. Porcentaje de Cumplimiento de Sede

```
% cumplimiento_sede = (avance_total_sede / objetivo_total_sede) × 100
```

**Ejemplo:**
```
Objetivo Total: 684,516.40
Avance Total:   596,476.41
% Cumplimiento: (596,476.41 / 684,516.40) × 100 = 87.14%
```

---

## Fórmulas de Avance

### TALLER (Workshop)

**Objetivo**: Suma de `sub_amount` de todos los conceptos con `area_id = AREA_TALLER`

**Avance**: Total de facturación **SIN IGV** de documentos electrónicos que cumplan:
- Fecha de emisión dentro del periodo (mes/año)
- No anulados (`anulado = false`)
- Estado SENT o ACCEPTED
- Relacionados a órdenes de trabajo con `type_planning_id` de los conceptos objetivo
- Considera notas de crédito (resta del total)
- **IMPORTANTE**: Se usa el campo `total_gravada` (subtotal sin IGV) porque los objetivos se establecen sin impuestos

```sql
SELECT SUM(
  CASE
    WHEN sunat_concept_document_type_id = 7 -- Nota de Crédito
    THEN -total_gravada  -- Subtotal sin IGV
    ELSE total_gravada   -- Subtotal sin IGV
  END
) FROM electronic_documents
WHERE fecha_de_emision BETWEEN '2026-08-01' AND '2026-08-31'
  AND anulado = false
  AND status IN ('SENT', 'ACCEPTED')
  AND (
    -- Facturación simple: work_order_id directo
    work_order_id IN (
      SELECT id FROM ap_work_orders
      WHERE sede_id = X
        AND EXISTS (
          SELECT 1 FROM ap_work_orders_item
          WHERE work_order_id = ap_work_orders.id
            AND type_planning_id IN (conceptos_objetivo)
        )
    )
    OR
    -- Facturación masiva: notas internas
    id IN (
      SELECT electronic_document_id FROM electronic_document_internal_note
      WHERE internal_note_id IN (
        SELECT id FROM internal_note
        WHERE work_order_id IN (...)
      )
    )
  )
```

**Desglose por Marca**:
- Agrupa por marca del vehículo
- Si `is_marketed = 1`: usa nombre de marca
- Si `is_marketed = 0`: agrupa como "OTRAS MARCAS"

**Top Asesores**:
- Calcula avance por asesor
- Compara con objetivo individual del asesor
- Ordena por % de cumplimiento descendente

```
% cumplimiento_asesor = (avance_asesor / objetivo_asesor) × 100
```

### MESÓN (Counter)

**Objetivo**: Suma de `sub_amount` de todos los conceptos con `area_id = AREA_MESON`

**Avance**: Total de facturación **SIN IGV** de documentos electrónicos que cumplan:
- Fecha de emisión dentro del periodo
- No anulados
- Estado SENT o ACCEPTED
- Relacionados a `ap_order_quotations` con `area_id = AREA_MESON`
- Considera notas de crédito
- **IMPORTANTE**: Se usa el campo `total_gravada` (subtotal sin IGV) porque los objetivos se establecen sin impuestos

```sql
SELECT SUM(
  CASE
    WHEN sunat_concept_document_type_id = 7
    THEN -total_gravada  -- Subtotal sin IGV
    ELSE total_gravada   -- Subtotal sin IGV
  END
) FROM electronic_documents
WHERE fecha_de_emision BETWEEN '2026-08-01' AND '2026-08-31'
  AND anulado = false
  AND status IN ('SENT', 'ACCEPTED')
  AND ap_order_quotation_id IN (
    SELECT id FROM ap_order_quotations
    WHERE sede_id = X
      AND area_id = AREA_MESON
  )
```

### PASO VEHICULAR (Vehicle Crossing)

**Objetivo**: Valor `sub_amount` del concepto con `is_vehicular_crossing = true` (cantidad de vehículos)

**Avance**: Conteo de órdenes de trabajo que cumplan:
- Sede específica
- Fecha de apertura dentro del periodo
- Tienen inspección vehicular activa (no cancelada)

```sql
SELECT COUNT(*) FROM ap_work_orders
WHERE sede_id = X
  AND opening_date BETWEEN '2026-08-01' AND '2026-08-31'
  AND EXISTS (
    SELECT 1 FROM work_order_vehicle_inspection
    WHERE work_order_id = ap_work_orders.id
      AND is_cancelled = false
  )
```

**IMPORTANTE**: Se usa la relación `activeVehicleInspectionPivot` que filtra automáticamente:
- `is_cancelled = false`
- Último registro (si hay múltiples)

**Desglose por Marca**:
- Cuenta vehículos por marca
- Aplica misma lógica de `is_marketed` para agrupar

```
% del total por marca = (cantidad_marca / total_vehiculos) × 100
```

---

## Estados y Clasificación

### Estados de Cumplimiento

El sistema clasifica el avance en 4 estados según el porcentaje:

| Estado | Rango | Significado |
|--------|-------|-------------|
| **critical** | < 70% | Crítico - Muy por debajo del objetivo |
| **warning** | 70% - 84.99% | Advertencia - Bajo el objetivo |
| **on_track** | 85% - 100% | En camino - Cumpliendo satisfactoriamente |
| **exceeded** | > 100% | Superado - Por encima del objetivo |
| **not_applicable** | N/A | No aplica (sin objetivo configurado) |

```php
private function getStatus(float $percentage): string
{
    if ($percentage < 70) return 'critical';
    if ($percentage < 85) return 'warning';
    if ($percentage <= 100) return 'on_track';
    return 'exceeded';
}
```

### Ranking de Sedes

Las sedes se ordenan por `completion_percentage` de mayor a menor:

```
Ranking:
1. AP_JAEN      - 336.98%  (exceeded)
2. AP_PIURA     - 100.84%  (exceeded)
3. AP_CAJAMARCA - 89.29%   (on_track)
4. AP_LEGUIA    - 87.14%   (on_track)
5. AP_TUMBES    - 17.49%   (critical)
```

---

## Estructura de Respuesta

### Resumen Ejecutivo

```json
{
  "executive_summary": {
    "total_objective": 1909412.46,        // Suma objetivos de todas las sedes
    "total_progress": 2305256.81,          // Suma avances de todas las sedes
    "completion_percentage": 120.73,       // (total_progress / total_objective) × 100
    "status": "exceeded",                  // Estado según porcentaje
    "trend": "up",                         // Tendencia (up/down/stable)
    "days_remaining": 0,                   // Días restantes del mes
    "expected_vs_real": {
      "expected_percentage": 100,          // % esperado según días transcurridos
      "real_percentage": 120.73,           // % real alcanzado
      "difference": 20.73                  // Diferencia (real - esperado)
    }
  }
}
```

**Cálculo del Porcentaje Esperado**:
```
% esperado = (días_transcurridos / días_del_mes) × 100
```

Si el mes ya terminó, `expected_percentage = 100`.

### Detalle por Sede

```json
{
  "id": 13,
  "name": "",
  "abbreviation": "AP_LEGUIA",
  "total_objective": 684516.4,            // Suma de workshop + counter objectives
  "total_progress": 596476.41,            // Suma de workshop + counter progress
  "completion_percentage": 87.14,         // (total_progress / total_objective) × 100
  "status": "on_track",

  "workshop": {
    "objective": 602886.72,
    "progress": 540842.88,
    "completion_percentage": 89.71,
    "status": "on_track",
    "by_brand": [...],                    // Desglose por marca
    "top_advisors": [...]                 // Top 10 asesores
  },

  "counter": {
    "objective": 81977.68,
    "progress": 55633.53,
    "completion_percentage": 67.86,
    "status": "critical"
  },

  "vehicle_crossing": {
    "objective": 348,                     // Cantidad de vehículos objetivo
    "progress": 664,                      // Cantidad de vehículos recepcionados
    "completion_percentage": 190.8,       // (664 / 348) × 100
    "status": "exceeded",
    "by_brand": [...]                     // Desglose por marca
  }
}
```

### Desglose por Marca (Taller)

```json
{
  "brand_name": "JAC",
  "total_billing": 93260.37,              // Total facturado de esta marca
  "vehicle_count": 108,                   // Cantidad de vehículos atendidos
  "percentage_of_total": 17.24            // (93260.37 / total_billing_sede) × 100
}
```

### Top Asesores

```json
{
  "advisor_id": 4360,
  "advisor_name": "LLATAS GORDILLO YAMILY",
  "objective": 150634.68,                 // Objetivo individual del asesor
  "progress": 180770.13,                  // Avance del asesor
  "completion_percentage": 120.01,        // (progress / objective) × 100
  "status": "exceeded",
  "rank": 1                               // Posición en el ranking
}
```

---

## Notas Importantes

### Caché
- Los datos se cachean por 30 minutos
- Key: `objective_dashboard_{year}_{month}_{sede_id|all}`
- Use `use_cache=false` para forzar recálculo

### Consideraciones de Datos
1. **Cálculo SIN IGV**: El sistema usa `total_gravada` (subtotal sin IGV) para todos los cálculos de avance, ya que los objetivos se establecen sin impuestos. Esto garantiza que los valores coincidan con los reportes de facturación.
2. **Notas de Crédito**: Se restan del total (multiplicador -1)
3. **Marcas Comercializadas**: Solo marcas con `is_marketed = 1` se muestran por nombre individual
4. **Facturación Simple vs Masiva**: El sistema maneja ambos tipos de facturación
5. **Inspecciones Canceladas**: NO se cuentan en paso vehicular (`is_cancelled = false`)
6. **Relación Pivot**: Se usa `activeVehicleInspectionPivot` para obtener la inspección vigente más reciente

### Endpoints Disponibles

```
GET  /api/ap/postventa/objectives-dashboard
     - year (required)
     - month (required)
     - sede_id (optional)
     - use_cache (optional, default: true)

POST /api/ap/postventa/objectives-dashboard/refresh
     - year (required)
     - month (required)
     - sede_id (optional)

GET  /api/ap/postventa/objectives-dashboard/export
     - year (required)
     - month (required)
     - sede_id (optional)
```

### Ejemplo de Cálculo Completo

**Sede: AP_LEGUIA - Agosto 2026**

```
1. TALLER:
   Objetivo:  602,886.72
   Avance:    540,842.88
   %:         (540,842.88 / 602,886.72) × 100 = 89.71%
   Estado:    on_track

2. MESÓN:
   Objetivo:  81,977.68
   Avance:    55,633.53
   %:         (55,633.53 / 81,977.68) × 100 = 67.86%
   Estado:    critical

3. PASO VEHICULAR:
   Objetivo:  348 vehículos
   Avance:    664 vehículos
   %:         (664 / 348) × 100 = 190.8%
   Estado:    exceeded

4. TOTAL SEDE:
   Objetivo Total:  684,516.40  (602,886.72 + 81,977.68)
   Avance Total:    596,476.41  (540,842.88 + 55,633.53)
   % Cumplimiento:  (596,476.41 / 684,516.40) × 100 = 87.14%
   Estado:          on_track

   Nota: Paso vehicular es indicador separado, no suma al total monetario.
```

---

## Archivos Relacionados

- **Controller**: `app/Http/Controllers/ap/postventa/Dashboard/ObjectiveDashboardController.php`
- **Service**: `app/Http/Services/ap/postventa/Dashboard/ObjectiveDashboardService.php`
- **Modelos**:
  - `app/Models/ap/postventa/taller/ObjectiveSedePeriodPv.php`
  - `app/Models/ap/postventa/taller/ObjectiveAdvisorsPeriodPv.php`
  - `app/Models/ap/postventa/taller/ApWorkOrder.php`
  - `app/Models/ap/facturacion/ElectronicDocument.php`
- **Routes**: `routes/api.php`
