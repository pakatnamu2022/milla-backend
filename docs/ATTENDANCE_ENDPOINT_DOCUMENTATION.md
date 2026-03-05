# Endpoint de Asistencias por Periodo

## Endpoint
```
GET /api/gp/gh/payroll/schedules/attendances/{periodId}
```

## Descripción
Este endpoint retorna todas las asistencias día tras día de todos los trabajadores para un periodo específico. La información está estructurada para facilitar el mapeo en el frontend y calcular resúmenes por código de asistencia.

## Parámetros
- `periodId` (int, requerido): ID del periodo de nómina

## Headers Requeridos
```
Authorization: Bearer {token}
```

## Respuesta Exitosa (200)

### Estructura de la Respuesta
```json
{
  "success": true,
  "data": {
    "period_id": 1,
    "period_name": "Enero 2026",
    "start_date": "2026-01-01",
    "end_date": "2026-01-31",
    "total_workers": 25,
    "attendances": [
      {
        "worker_id": 1,
        "worker_name": "Juan Pérez García",
        "worker_code": "EMP001",
        "document_number": "12345678",
        "daily_attendances": [
          {
            "date": "2026-01-01",
            "code": "D",
            "status": "WORKED"
          },
          {
            "date": "2026-01-02",
            "code": "N",
            "status": "WORKED"
          },
          {
            "date": "2026-01-03",
            "code": "D",
            "status": "WORKED"
          }
        ],
        "summary": {
          "codes": {
            "D": 11,
            "N": 13,
            "V": 2,
            "EXT": 5
          },
          "total_days": 31
        }
      }
    ]
  }
}
```

## Estructura de Datos

### Objeto Principal
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `period_id` | Integer | ID del periodo |
| `period_name` | String | Nombre del periodo |
| `start_date` | Date | Fecha de inicio del periodo |
| `end_date` | Date | Fecha de fin del periodo |
| `total_workers` | Integer | Total de trabajadores con asistencias |
| `attendances` | Array | Lista de asistencias por trabajador |

### Objeto de Trabajador (dentro de `attendances`)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `worker_id` | Integer | ID del trabajador |
| `worker_name` | String | Nombre completo del trabajador |
| `worker_code` | String | Código del trabajador |
| `document_number` | String | Número de documento |
| `daily_attendances` | Array | Asistencias día por día |
| `summary` | Object | Resumen de códigos |

### Objeto de Asistencia Diaria (dentro de `daily_attendances`)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `date` | Date | Fecha de la asistencia (formato: YYYY-MM-DD) |
| `code` | String | Código de asistencia (D, N, V, EXT, etc.) |
| `status` | String | Estado (WORKED, ABSENT, VACATION, etc.) |

### Objeto Summary (dentro de cada trabajador)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codes` | Object | Objeto con conteo de cada código. Las claves son los códigos y los valores son las cantidades |
| `total_days` | Integer | Total de días registrados |

## Códigos de Asistencia Comunes

Los códigos pueden variar según la configuración de cada empresa. Ejemplos típicos:

- **D**: Turno Diurno
- **N**: Turno Nocturno
- **V**: Vacaciones
- **EXT**: Horas Extras
- **P**: Permiso
- **F**: Falta
- **L**: Licencia
- **DF**: Descanso Feriado

## Ejemplo de Mapeo en Frontend

### Vue.js / Nuxt.js
```javascript
// Llamada al endpoint
const response = await $fetch(`/api/gp/gh/payroll/schedules/attendances/${periodId}`)
const { period_id, period_name, start_date, end_date, total_workers, attendances } = response.data

// Renderizar tabla de asistencias
attendances.forEach(worker => {
  console.log(`Trabajador: ${worker.worker_name} (${worker.worker_code})`)
  console.log(`Total de días trabajados: ${worker.summary.total_days}`)

  // Mostrar resumen de códigos
  Object.entries(worker.summary.codes).forEach(([code, count]) => {
    console.log(`  - ${code}: ${count} días`)
  })

  // Acceder a asistencias diarias
  worker.daily_attendances.forEach(attendance => {
    console.log(`    ${attendance.date}: ${attendance.code}`)
  })
})
```

### React / Next.js
```javascript
// Componente para mostrar resumen de códigos
function AttendanceCodeSummary({ summary }) {
  return (
    <div>
      <h4>Resumen de Asistencias</h4>
      <ul>
        {Object.entries(summary.codes).map(([code, count]) => (
          <li key={code}>
            <strong>{code}:</strong> {count} días
          </li>
        ))}
      </ul>
      <p>Total de días: {summary.total_days}</p>
    </div>
  )
}

// Componente para calendario diario
function DailyAttendanceCalendar({ dailyAttendances }) {
  return (
    <div className="calendar">
      {dailyAttendances.map(attendance => (
        <div key={attendance.date} className="calendar-day">
          <span className="date">{attendance.date}</span>
          <span className={`code code-${attendance.code}`}>
            {attendance.code}
          </span>
        </div>
      ))}
    </div>
  )
}
```

### Angular
```typescript
interface AttendanceData {
  period_id: number;
  period_name: string;
  start_date: string;
  end_date: string;
  total_workers: number;
  attendances: WorkerAttendance[];
}

interface WorkerAttendance {
  worker_id: number;
  worker_name: string;
  worker_code: string;
  document_number: string;
  daily_attendances: DailyAttendance[];
  summary: AttendanceSummary;
}

interface DailyAttendance {
  date: string;
  code: string;
  status: string;
}

interface AttendanceSummary {
  codes: { [key: string]: number };
  total_days: number;
}

// Servicio
getAttendancesByPeriod(periodId: number): Observable<AttendanceData> {
  return this.http.get<{success: boolean, data: AttendanceData}>(
    `/api/gp/gh/payroll/schedules/attendances/${periodId}`
  ).pipe(
    map(response => response.data)
  );
}
```

## Casos de Uso del Frontend

### 1. Tabla de Resumen por Trabajador
Mostrar una tabla con todos los trabajadores y el conteo de cada código:

| Trabajador | Código | D | N | V | EXT | Total |
|-----------|--------|---|---|---|-----|-------|
| Juan Pérez | EMP001 | 11 | 13 | 2 | 5 | 31 |
| María López | EMP002 | 20 | 0 | 5 | 6 | 31 |

```javascript
// Generar datos para la tabla
const tableData = attendances.map(worker => ({
  name: worker.worker_name,
  code: worker.worker_code,
  ...worker.summary.codes,
  total: worker.summary.total_days
}))
```

### 2. Calendario Mensual Individual
Mostrar un calendario del mes con los códigos por cada día:

```javascript
// Crear estructura de calendario
function buildCalendar(dailyAttendances, startDate, endDate) {
  const calendar = []
  const attendanceMap = new Map(
    dailyAttendances.map(a => [a.date, a.code])
  )

  let current = new Date(startDate)
  const end = new Date(endDate)

  while (current <= end) {
    const dateStr = current.toISOString().split('T')[0]
    calendar.push({
      date: dateStr,
      code: attendanceMap.get(dateStr) || '-',
      dayOfWeek: current.getDay()
    })
    current.setDate(current.getDate() + 1)
  }

  return calendar
}
```

### 3. Gráfico de Distribución de Códigos
Generar datos para gráficos (pie chart, bar chart):

```javascript
// Datos para Chart.js
function getChartData(summary) {
  return {
    labels: Object.keys(summary.codes),
    datasets: [{
      data: Object.values(summary.codes),
      backgroundColor: [
        '#FF6384',
        '#36A2EB',
        '#FFCE56',
        '#4BC0C0',
        '#9966FF'
      ]
    }]
  }
}
```

### 4. Filtros y Búsqueda
```javascript
// Filtrar por código específico
const workersWithNightShift = attendances.filter(
  worker => worker.summary.codes.N > 0
)

// Buscar trabajador
const searchWorker = (query) => {
  return attendances.filter(worker =>
    worker.worker_name.toLowerCase().includes(query.toLowerCase()) ||
    worker.worker_code.toLowerCase().includes(query.toLowerCase())
  )
}

// Ordenar por total de días
const sortedByDays = [...attendances].sort(
  (a, b) => b.summary.total_days - a.summary.total_days
)
```

## Respuestas de Error

### Periodo no encontrado (404)
```json
{
  "success": false,
  "message": "Period not found"
}
```

### Sin autorización (401)
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### Sin permisos (403)
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

## Notas Importantes

1. **Periodo sin asistencias**: Si el periodo no tiene asistencias registradas, el array `attendances` estará vacío pero la respuesta será exitosa.

2. **Fechas**: Todas las fechas están en formato ISO 8601 (YYYY-MM-DD).

3. **Códigos dinámicos**: Los códigos en `summary.codes` son dinámicos y dependen de los códigos registrados en la tabla `gh_payroll_schedules`.

4. **Performance**: El endpoint está optimizado para cargar todos los datos de una vez. Para periodos con muchos trabajadores, considera implementar paginación en el frontend.

5. **Caché**: Considera implementar caché del lado del cliente para evitar llamadas repetidas con el mismo `periodId`.

## Recomendaciones de UI/UX

1. **Vista de Tabla**: Tabla con resumen de códigos por trabajador
2. **Vista de Calendario**: Calendario mensual mostrando códigos día por día
3. **Vista de Detalle**: Modal o página de detalle para ver el historial completo de un trabajador
4. **Exportación**: Botón para exportar datos a Excel/CSV
5. **Filtros**: Filtros por código, trabajador, rango de fechas
6. **Leyenda**: Mostrar leyenda de códigos con su descripción completa

## Ejemplo de Implementación Completa (Vue 3 + Composition API)

```vue
<template>
  <div class="attendance-view">
    <h2>{{ periodName }}</h2>
    <p>{{ startDate }} - {{ endDate }} | Total Trabajadores: {{ totalWorkers }}</p>

    <div class="filters">
      <input v-model="searchQuery" placeholder="Buscar trabajador..." />
      <select v-model="selectedCode">
        <option value="">Todos los códigos</option>
        <option v-for="code in allCodes" :key="code">{{ code }}</option>
      </select>
    </div>

    <table>
      <thead>
        <tr>
          <th>Trabajador</th>
          <th>Código</th>
          <th v-for="code in allCodes" :key="code">{{ code }}</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="worker in filteredAttendances" :key="worker.worker_id">
          <td>{{ worker.worker_name }}</td>
          <td>{{ worker.worker_code }}</td>
          <td v-for="code in allCodes" :key="code">
            {{ worker.summary.codes[code] || 0 }}
          </td>
          <td>{{ worker.summary.total_days }}</td>
          <td>
            <button @click="showDetail(worker)">Ver Detalle</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  periodId: { type: Number, required: true }
})

const attendances = ref([])
const periodName = ref('')
const startDate = ref('')
const endDate = ref('')
const totalWorkers = ref(0)
const searchQuery = ref('')
const selectedCode = ref('')

const allCodes = computed(() => {
  const codes = new Set()
  attendances.value.forEach(worker => {
    Object.keys(worker.summary.codes).forEach(code => codes.add(code))
  })
  return Array.from(codes).sort()
})

const filteredAttendances = computed(() => {
  return attendances.value.filter(worker => {
    const matchesSearch = !searchQuery.value ||
      worker.worker_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      worker.worker_code.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesCode = !selectedCode.value ||
      worker.summary.codes[selectedCode.value] > 0

    return matchesSearch && matchesCode
  })
})

async function loadAttendances() {
  try {
    const response = await $fetch(`/api/gp/gh/payroll/schedules/attendances/${props.periodId}`)
    const data = response.data

    attendances.value = data.attendances
    periodName.value = data.period_name
    startDate.value = data.start_date
    endDate.value = data.end_date
    totalWorkers.value = data.total_workers
  } catch (error) {
    console.error('Error loading attendances:', error)
  }
}

function showDetail(worker) {
  // Implementar modal o navegación a página de detalle
  console.log('Show detail for:', worker)
}

onMounted(() => {
  loadAttendances()
})
</script>
```

## Contacto y Soporte

Para cualquier duda sobre la implementación o el uso de este endpoint, contactar al equipo de backend.
