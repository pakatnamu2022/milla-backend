# 📘 Documentación API - Cálculos de Nómina por Asistencias

## 🎯 Descripción General

Este sistema guarda los cálculos de nómina basados en las asistencias registradas (`gh_payroll_schedules`), generando registros históricos e inmutables en `gh_payroll_calculations` y `gh_payroll_calculation_details`.

### Flujo del Sistema

1. **Asistencias** → Se registran diariamente en `gh_payroll_schedules`
2. **Generar Cálculos** → Botón que consolida asistencias y calcula montos
3. **Revisar/Recalcular** → Si hay correcciones, se puede recalcular
4. **Aprobar/Pagar** → Estados finales del cálculo

---

## 📊 Estructura de Datos

### Tabla Principal: `gh_payroll_calculations`

Almacena el cálculo consolidado por trabajador y período.

```typescript
interface PayrollCalculation {
  id: number;

  // Información del trabajador (snapshot)
  worker_id: number;
  salary: number;              // Sueldo mensual al momento del cálculo
  shift_hours: number;         // Horas de jornada laboral
  base_hour_value: number;     // Valor hora base (sueldo/30/shift_hours)

  // Totales calculados
  total_earnings: number;      // Total ingresos (asistencias, bonos, etc.)
  total_deductions: number;    // Total descuentos (AFP, ISSS, préstamos, etc.)
  total_contributions: number; // Total aportes patronales
  net_salary: number;          // Salario neto a pagar

  // Estado y auditoría
  status: 'DRAFT' | 'CALCULATED' | 'APPROVED' | 'PAID';
  calculated_at: string;
  calculated_by: number;
  approved_at: string | null;
  approved_by: number | null;
  paid_at: string | null;
  paid_by: number | null;

  // Relaciones
  period: PayrollPeriod;
  worker: Worker;
  details: PayrollCalculationDetail[];
}
```

### Tabla Detalle: `gh_payroll_calculation_details`

Almacena cada línea de cálculo (por código de asistencia).

```typescript
interface PayrollCalculationDetail {
  id: number;
  payroll_calculation_id: number;

  // Identificación del concepto
  code: string;                // Código: HN, HED, HEN, AFP, ISSS, etc.
  type: 'EARNING' | 'DEDUCTION' | 'CONTRIBUTION';
  category: 'ATTENDANCE' | 'BONUS' | 'TAX' | 'INSURANCE' | 'LOAN' | 'OTHER';
  description: string;

  // Campos específicos para ASISTENCIAS
  hour_type: 'DIURNO' | 'NOCTURNO' | null;
  hours: number | null;        // Horas por día
  days_worked: number;         // Cantidad de días trabajados
  multiplier: number | null;   // Multiplicador de la regla (1.25, 1.75, etc.)
  use_shift: boolean;          // Si usa horas de jornada del trabajador

  // Campos para DESCUENTOS/APORTES
  base_amount: number | null;  // Monto base para cálculo
  rate: number | null;         // Porcentaje (ej: 0.13 para AFP 13%)

  // Resultado del cálculo
  hour_value: number;          // Valor por hora calculado (incluye recargo nocturno + multiplicador)
  amount: number;              // Monto total (positivo para ingresos, negativo para descuentos)

  calculation_order: number;   // Orden de cálculo
}
```

---

## 🔌 Endpoints de la API

### Base URL
```
/api/gp/gh/payroll
```

---

## 1️⃣ Generar Cálculos de Nómina

Genera los cálculos de nómina para un período, leyendo todas las asistencias trabajadas.

### **POST** `/schedules/generate-calculations/{periodId}`

#### Request

```http
POST /api/gp/gh/payroll/schedules/generate-calculations/5
Content-Type: application/json
Authorization: Bearer {token}
```

**Sin body** - Solo requiere el ID del período en la URL.

#### Response Success (200)

```json
{
  "success": true,
  "data": {
    "success": true,
    "period_id": 5,
    "calculations_created": 25,
    "calculation_ids": [1, 2, 3, 4, 5, ...],
    "errors": []
  },
  "message": "Successfully generated 25 payroll calculations"
}
```

#### Response Errors

**409 - Cálculos ya existen**
```json
{
  "success": false,
  "message": "Calculations already exist for this period. Use recalculate endpoint to update them."
}
```

**404 - Período no encontrado**
```json
{
  "success": false,
  "message": "Period not found"
}
```

**400 - Sin asistencias**
```json
{
  "success": false,
  "message": "No worked schedules found for this period"
}
```

---

## 2️⃣ Recalcular Cálculos de Nómina

Elimina y regenera los cálculos si hubo cambios en las asistencias.

### **POST** `/schedules/recalculate-calculations/{periodId}`

#### Request

```http
POST /api/gp/gh/payroll/schedules/recalculate-calculations/5
Content-Type: application/json
Authorization: Bearer {token}
```

**Sin body** - Solo requiere el ID del período en la URL.

#### Response Success (200)

```json
{
  "success": true,
  "data": {
    "success": true,
    "period_id": 5,
    "calculations_created": 25,
    "calculation_ids": [26, 27, 28, 29, 30, ...],
    "errors": []
  },
  "message": "Successfully recalculated 25 payroll calculations"
}
```

#### Response Errors

**403 - Cálculos ya aprobados/pagados**
```json
{
  "success": false,
  "message": "Cannot recalculate: 5 calculations are already APPROVED or PAID. Please delete them manually first."
}
```

---

## 3️⃣ Obtener Resumen de Cálculos (Preview)

Obtiene un resumen de cómo quedarían los cálculos **sin guardarlos en BD**.

### **GET** `/schedules/summary/{periodId}`

#### Request

```http
GET /api/gp/gh/payroll/schedules/summary/5
Authorization: Bearer {token}
```

#### Response Success (200)

```json
{
  "success": true,
  "data": {
    "period": {
      "id": 5,
      "name": "Enero 2026",
      "code": "2026-01",
      "start_date": "2026-01-01",
      "end_date": "2026-01-31",
      "status": "OPEN"
    },
    "workers_count": 2,
    "summary": [
      {
        "worker_id": 123,
        "worker_name": "Juan Pérez",
        "salary": 3000.00,
        "shift_hours": 8.00,
        "base_hour_value": 12.50,
        "details": [
          {
            "code": "HN",
            "hour_type": "DIURNO",
            "hours": 8.00,
            "multiplier": 1.0000,
            "pay": true,
            "use_shift": true,
            "hour_value": 12.50,
            "days_worked": 22,
            "total": 2200.00
          },
          {
            "code": "HED",
            "hour_type": "DIURNO",
            "hours": 2.00,
            "multiplier": 1.25,
            "pay": true,
            "use_shift": false,
            "hour_value": 15.63,
            "days_worked": 5,
            "total": 156.30
          }
        ],
        "total_amount": 2356.30
      }
    ]
  }
}
```

---

## 📋 Lógica de Cálculo

### Fórmula Base

```
valor_hora_base = sueldo / 30 / horas_jornada
```

### Para cada código de asistencia:

1. **Obtener horas:**
   - Si `use_shift = true` → usa `horas_jornada` del trabajador
   - Si `use_shift = false` → usa `hours` de la regla

2. **Calcular valor hora:**
   ```
   valor_hora = valor_hora_base

   Si hour_type = 'NOCTURNO':
     valor_hora = valor_hora × 1.35  (recargo 35%)

   valor_hora = valor_hora × multiplier
   ```

3. **Calcular total:**
   ```
   total = horas × valor_hora × días_trabajados

   Si pay = false:
     total = -total  (se convierte en descuento)
   ```

### Ejemplo Práctico

**Trabajador:**
- Sueldo: $3,000
- Horas jornada: 8

**Cálculos:**

```
valor_hora_base = 3000 / 30 / 8 = $12.50

HN (Horas Normales):
  horas = 8 (usa jornada)
  valor_hora = 12.50 × 1.0 = $12.50
  total = 8 × 12.50 × 22 días = $2,200.00

HED (Horas Extra Diurnas):
  horas = 2 (de la regla)
  valor_hora = 12.50 × 1.25 = $15.63
  total = 2 × 15.63 × 5 días = $156.30

HEN (Horas Extra Nocturnas):
  horas = 2 (de la regla)
  valor_hora = 12.50 × 1.35 (nocturno) × 1.75 (multiplier) = $29.53
  total = 2 × 29.53 × 3 días = $177.19

TOTAL NETO = $2,533.49
```

---

## 🔄 Estados del Cálculo

```mermaid
DRAFT → CALCULATED → APPROVED → PAID
```

| Estado | Descripción | Puede Modificar | Puede Recalcular |
|--------|-------------|-----------------|------------------|
| **DRAFT** | Borrador inicial | ✅ Sí | ✅ Sí |
| **CALCULATED** | Cálculo generado | ✅ Sí | ✅ Sí |
| **APPROVED** | Aprobado por RRHH | ❌ No | ❌ No |
| **PAID** | Pagado al trabajador | ❌ No | ❌ No |

---

## 🎨 Ejemplo de Integración en Frontend

### 1. Ver Resumen (Preview)

```typescript
async function previewPayrollCalculations(periodId: number) {
  const response = await api.get(`/api/gp/gh/payroll/schedules/summary/${periodId}`);

  // Mostrar tabla resumen
  return response.data.data.summary;
}
```

### 2. Generar Cálculos (Guardar en BD)

```vue
<template>
  <button @click="generateCalculations" :disabled="loading">
    {{ loading ? 'Generando...' : 'Generar Cálculos de Nómina' }}
  </button>
</template>

<script setup>
const generateCalculations = async () => {
  try {
    loading.value = true;

    const response = await api.post(
      `/api/gp/gh/payroll/schedules/generate-calculations/${periodId.value}`
    );

    toast.success(response.data.message);

    // Mostrar errores si los hay
    if (response.data.data.errors.length > 0) {
      console.warn('Errores al generar algunos cálculos:', response.data.data.errors);
    }

    // Recargar datos
    await fetchCalculations();

  } catch (error) {
    if (error.response?.status === 409) {
      toast.error('Ya existen cálculos para este período. Usa el botón Recalcular.');
    } else {
      toast.error(error.response?.data?.message || 'Error al generar cálculos');
    }
  } finally {
    loading.value = false;
  }
};
</script>
```

### 3. Recalcular (Si hubo cambios)

```typescript
async function recalculateCalculations(periodId: number) {
  // Confirmar acción
  const confirmed = await confirm({
    title: 'Recalcular Nómina',
    message: 'Esto eliminará los cálculos actuales y los regenerará con las asistencias actualizadas. ¿Continuar?'
  });

  if (!confirmed) return;

  try {
    const response = await api.post(
      `/api/gp/gh/payroll/schedules/recalculate-calculations/${periodId}`
    );

    toast.success(response.data.message);

  } catch (error) {
    if (error.response?.status === 403) {
      toast.error('No se puede recalcular: hay cálculos APROBADOS o PAGADOS');
    } else {
      toast.error(error.response?.data?.message);
    }
  }
}
```

---

## 🚨 Validaciones Importantes

### Antes de Generar
- ✅ El período debe existir
- ✅ Debe haber asistencias con status `WORKED`
- ✅ No deben existir cálculos previos para ese período

### Antes de Recalcular
- ✅ El período debe existir
- ✅ No debe haber cálculos con status `APPROVED` o `PAID`
- ✅ Si los hay, deben eliminarse manualmente primero

### Durante el Cálculo
- ⚠️ Trabajadores sin sueldo o sin horas_jornada se omiten (se registra en `errors`)
- ⚠️ Códigos de asistencia sin reglas definidas se omiten

---

## 📦 Respuesta Completa de Cálculo Guardado

Cuando consultes los cálculos guardados, obtendrás:

```json
{
  "id": 1,
  "worker_id": 123,
  "period_id": 5,

  "salary": 3000.00,
  "shift_hours": 8.00,
  "base_hour_value": 12.50,

  "total_earnings": 2533.49,
  "total_deductions": 0.00,
  "total_contributions": 0.00,
  "net_salary": 2533.49,

  "status": "CALCULATED",
  "can_modify": true,
  "can_approve": true,

  "calculated_at": "2026-02-27T10:30:00.000000Z",
  "approved_at": null,
  "paid_at": null,

  "worker": {
    "id": 123,
    "full_name": "Juan Pérez",
    "vat": "12345678-9",
    "sueldo": 3000.00
  },

  "period": {
    "id": 5,
    "code": "2026-01",
    "name": "Enero 2026"
  },

  "details": [
    {
      "id": 1,
      "concept_code": "HN",
      "concept_name": "Horas Normales",
      "type": "EARNING",
      "category": "ATTENDANCE",

      "hour_type": "DIURNO",
      "hours": 8.00,
      "days_worked": 22,
      "multiplier": 1.0000,
      "use_shift": true,

      "hour_value": 12.50,
      "amount": 2200.00,

      "calculation_order": 1
    },
    {
      "id": 2,
      "concept_code": "HED",
      "concept_name": "Horas Extra Diurnas",
      "type": "EARNING",
      "category": "ATTENDANCE",

      "hour_type": "DIURNO",
      "hours": 2.00,
      "days_worked": 5,
      "multiplier": 1.2500,
      "use_shift": false,

      "hour_value": 15.63,
      "amount": 156.30,

      "calculation_order": 2
    }
  ]
}
```

---

## ✅ Checklist para el Frontend

- [ ] Botón "Ver Resumen" (Preview sin guardar)
- [ ] Botón "Generar Cálculos" (Guardar en BD)
- [ ] Botón "Recalcular" (Solo si status = DRAFT o CALCULATED)
- [ ] Mostrar tabla con detalles por trabajador
- [ ] Mostrar totales: earnings, deductions, net_salary
- [ ] Mostrar errores si los hay
- [ ] Confirmación antes de recalcular
- [ ] Deshabilitar botones según estado del período
- [ ] Notificaciones de éxito/error

---

## 📞 Soporte

Para dudas o mejoras, contactar al equipo de backend.

**Fecha:** 2026-02-27
**Versión:** 1.0