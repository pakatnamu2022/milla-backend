# API - Enriquecimiento de Archivos de Fugado/Fuga Temprana

## Descripción
Esta API permite procesar archivos Excel de "Formato Fugado" y "Formato Fuga Temprana" para enriquecerlos con datos del cliente y última orden de trabajo del vehículo.

**IMPORTANTE:** El procesamiento se ejecuta en **background usando Laravel Queue**. El archivo se encola y se procesa de forma asíncrona sin bloquear el servidor HTTP. El usuario no necesita mantener la conexión abierta.

---

## Endpoints

### 1. Encolar Archivo para Procesamiento

**Endpoint:** `POST /api/vehicle-leakage/process`

**Descripción:** Sube el archivo Excel y lo encola para procesamiento en background. Retorna inmediatamente un `job_id` para consultar el estado.

**Request:**
- **Content-Type:** `multipart/form-data`
- **Headers:** `Authorization: Bearer TOKEN`
- **Body:**
  - `file`: Archivo Excel (.xlsx o .xls) - Máximo 50MB

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Archivo encolado para procesamiento. Use el job_id para consultar el estado.",
  "data": {
    "job_id": 123,
    "status": "pending",
    "original_filename": "Formato Fugado.xlsx"
  }
}
```

**Ejemplo con cURL:**
```bash
curl -X POST "http://localhost/api/vehicle-leakage/process" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@Formato Fugado.xlsx"
```

**Ejemplo con JavaScript/Fetch:**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('/api/vehicle-leakage/process', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
})
.then(response => response.json())
.then(data => {
  const jobId = data.data.job_id;
  console.log('Job ID:', jobId);

  // Iniciar polling para consultar estado
  checkJobStatus(jobId);
});
```

---

### 2. Listar Archivos Procesados del Usuario

**Endpoint:** `GET /api/vehicle-leakage/jobs`

**Descripción:** Lista todos los archivos procesados (o en procesamiento) del usuario autenticado. Útil para mostrar un historial de todos los archivos subidos.

**Query Parameters (Opcionales):**
- `status`: Filtrar por estado (`pending`, `processing`, `completed`, `failed`)
- `per_page`: Número de resultados por página (default: 20)
- `page`: Número de página

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "job_id": 3,
      "original_filename": "Formato Fugado.xlsx",
      "status": "completed",
      "created_at": "2026-09-12T10:30:00.000000Z",
      "started_at": "2026-09-12T10:30:05.000000Z",
      "completed_at": "2026-09-12T10:32:30.000000Z",
      "results": {
        "processed": 499,
        "enriched": 59,
        "not_found": 440,
        "errors": []
      },
      "download_url": "http://api.../vehicle-leakage/download/3"
    },
    {
      "job_id": 2,
      "original_filename": "Fuga Temprana.xlsx",
      "status": "failed",
      "created_at": "2026-09-12T10:15:00.000000Z",
      "started_at": "2026-09-12T10:15:05.000000Z",
      "completed_at": "2026-09-12T10:15:10.000000Z",
      "error_message": "No se encontró la columna 'Rango' con los VIN en el archivo Excel"
    },
    {
      "job_id": 1,
      "original_filename": "Test.xlsx",
      "status": "processing",
      "created_at": "2026-09-12T10:00:00.000000Z",
      "started_at": "2026-09-12T10:00:05.000000Z",
      "completed_at": null
    }
  ],
  "pagination": {
    "total": 3,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1
  }
}
```

**Ejemplo con cURL:**
```bash
# Listar todos
curl "http://localhost/api/vehicle-leakage/jobs" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filtrar solo completados
curl "http://localhost/api/vehicle-leakage/jobs?status=completed" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Paginación
curl "http://localhost/api/vehicle-leakage/jobs?per_page=10&page=2" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ejemplo con JavaScript:**
```javascript
async function loadJobsList() {
  const response = await fetch('/api/vehicle-leakage/jobs', {
    headers: { 'Authorization': `Bearer ${token}` }
  });

  const data = await response.json();

  data.data.forEach(job => {
    console.log(`${job.original_filename}: ${job.status}`);

    if (job.status === 'completed') {
      console.log('  → Descargar:', job.download_url);
    }

    if (job.status === 'failed') {
      console.log('  → Error:', job.error_message);
    }
  });
}
```

---

### 3. Consultar Estado del Procesamiento

**Endpoint:** `GET /api/vehicle-leakage/status/{job_id}`

**Descripción:** Consulta el estado actual del procesamiento. El frontend debe llamar este endpoint periódicamente (polling) hasta que `status = "completed"` o `status = "failed"`.

**Response cuando está procesando:**
```json
{
  "success": true,
  "data": {
    "job_id": 123,
    "status": "processing",
    "original_filename": "Formato Fugado.xlsx",
    "created_at": "2026-09-12T10:00:00.000000Z",
    "started_at": "2026-09-12T10:00:05.000000Z",
    "completed_at": null
  }
}
```

**Response cuando completó exitosamente:**
```json
{
  "success": true,
  "data": {
    "job_id": 123,
    "status": "completed",
    "original_filename": "Formato Fugado.xlsx",
    "created_at": "2026-09-12T10:00:00.000000Z",
    "started_at": "2026-09-12T10:00:05.000000Z",
    "completed_at": "2026-09-12T10:02:30.000000Z",
    "results": {
      "processed": 499,
      "enriched": 59,
      "not_found": 440,
      "errors": []
    },
    "download_url": "http://api.../vehicle-leakage/download/123"
  }
}
```

**Response cuando falló:**
```json
{
  "success": true,
  "data": {
    "job_id": 123,
    "status": "failed",
    "original_filename": "Formato Fugado.xlsx",
    "error_message": "No se encontró la columna 'Rango' con los VIN en el archivo Excel",
    "created_at": "2026-09-12T10:00:00.000000Z",
    "started_at": "2026-09-12T10:00:05.000000Z",
    "completed_at": "2026-09-12T10:00:10.000000Z"
  }
}
```

**Posibles estados:**
- `pending`: En cola, aún no inició el procesamiento
- `processing`: Procesando actualmente
- `completed`: Completado exitosamente, listo para descargar
- `failed`: Falló con error

**Ejemplo con JavaScript (Polling cada 5 segundos):**
```javascript
function checkJobStatus(jobId) {
  const intervalId = setInterval(async () => {
    const response = await fetch(`/api/vehicle-leakage/status/${jobId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    const data = await response.json();
    const status = data.data.status;

    console.log('Estado:', status);

    if (status === 'completed') {
      clearInterval(intervalId);
      console.log('Resultados:', data.data.results);
      console.log('Descargar desde:', data.data.download_url);
      // Mostrar botón de descarga al usuario
    }

    if (status === 'failed') {
      clearInterval(intervalId);
      console.error('Error:', data.data.error_message);
      // Mostrar error al usuario
    }
  }, 5000); // Consultar cada 5 segundos
}
```

---

### 4. Descargar Archivo Procesado

**Endpoint:** `GET /api/vehicle-leakage/download/{job_id}`

**Descripción:** Descarga el archivo Excel enriquecido. Solo funciona si `status = "completed"`.

**Response (200 OK):**
- **Content-Type:** `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- **Content-Disposition:** `attachment; filename="enriquecido_Formato Fugado.xlsx"`
- Descarga directa del archivo

**Response si aún no completó (400 Bad Request):**
```json
{
  "success": false,
  "message": "El archivo aún no está procesado. Estado actual: processing"
}
```

**Response si no existe (404 Not Found):**
```json
{
  "success": false,
  "message": "Job no encontrado"
}
```

**Ejemplo con JavaScript:**
```javascript
function downloadFile(jobId) {
  window.location.href = `/api/vehicle-leakage/download/${jobId}`;
}
```

---

## Columnas Agregadas al Excel

El archivo procesado incluye 4 columnas adicionales al final:

1. **Nombres** - Nombre completo del cliente (`BusinessPartners.full_name`)
2. **Celular** - Teléfono del cliente (`BusinessPartners.phone`)
3. **Correo** - Email del cliente (`BusinessPartners.email`)
4. **Tipo Ultimo Servicio** - Descripción del tipo de planificación de la última OT (`TypePlanningWorkOrder.description`)

**Lógica de Enriquecimiento:**
```
VIN (columna "Rango[vin]")
  → Vehicles (ap_vehicles.vin)
    → customer_id
      → BusinessPartners (business_partners)
        ✓ full_name → Nombres
        ✓ phone → Celular
        ✓ email → Correo
    → vehicle_id
      → ApWorkOrder (última orden no anulada ni eliminada)
        → items (primer item)
          → type_planning_id
            → TypePlanningWorkOrder
              ✓ description → Tipo Ultimo Servicio
```

**Valores por Defecto:**
- Si no se encuentra el VIN: `-`
- Si no hay cliente asociado: `-`
- Si no hay órdenes de trabajo: `-`
- Si hay error en la fila: `-` en todas las columnas

---

## Optimizaciones Implementadas

### 1. Procesamiento en Background (Queue)
- El archivo se procesa en un Job de Laravel Queue
- No bloquea el servidor HTTP
- Permite archivos grandes sin timeout
- Timeout del job: 30 minutos (1800 segundos)
- El usuario puede cerrar el navegador mientras procesa

### 2. Procesamiento por Chunks
- El archivo se procesa en lotes de 50 filas
- Reduce consumo de memoria para archivos grandes
- Permite procesar archivos de más de 30MB sin problemas
- Liberación de memoria inmediata después de procesar cada chunk

### 3. Consultas en Batch
- Los datos de vehículos y clientes se cargan en lotes
- Evita el problema N+1 de consultas
- Mejora significativa en rendimiento
- Solo se seleccionan las columnas necesarias

### 4. Gestión Avanzada de Memoria
- Se aumenta temporalmente el límite de memoria a **4GB** durante todo el procesamiento
- Se restaura al valor original automáticamente al finalizar (bloque finally)
- Recolección de basura forzada cada 250 filas procesadas (5 chunks)
- Logs detallados de uso de memoria en cada etapa

### 5. Eager Loading
- Se cargan todas las relaciones necesarias de una vez
- Se usan `with()` para cargar relaciones de forma eficiente
- Solo se seleccionan campos específicos en las relaciones

### 6. Logs de Progreso
- Logs informativos cada 250 filas procesadas
- Muestra: filas procesadas, enriquecidas, no encontradas, uso de memoria, porcentaje de progreso
- Logs al inicio, durante carga, antes de guardar y al finalizar

---

## Validaciones

### Archivo
- ✓ Campo requerido
- ✓ Debe ser un archivo Excel (.xlsx o .xls)
- ✓ Tamaño máximo: 50MB

### Columnas
- ✓ Debe existir una columna que contenga "vin" en su nombre (ej: "Rango[vin]")
- ✓ Si no existe, el job falla con mensaje descriptivo

---

## Manejo de Errores

### Error 422 - Validación
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "file": ["El archivo debe ser de tipo Excel (.xlsx o .xls)"]
  }
}
```

### Error 404 - Job No Encontrado
```json
{
  "success": false,
  "message": "Job no encontrado"
}
```

### Error 400 - Archivo No Listo
```json
{
  "success": false,
  "message": "El archivo aún no está procesado. Estado actual: processing"
}
```

### Error 500 - Fallo al Encolar
```json
{
  "success": false,
  "message": "Error al encolar el archivo: [detalle del error]"
}
```

---

## Notas Técnicas

### Archivos Temporales
- Se crean en `storage/app/temp/` al subir
- Se eliminan automáticamente después del procesamiento

### Archivos Procesados
- Se guardan en `storage/app/vehicle_leakage_processed/`
- Nombre formato: `enriquecido_YYYYMMDDHHMMSS_nombre_original.xlsx`
- Permanecen disponibles para descarga

### Tabla de Tracking
- Tabla: `vehicle_leakage_jobs`
- Guarda: job_id, user_id, status, resultados, rutas de archivos, timestamps

### Queue Worker
**En desarrollo:**
```bash
php artisan queue:work
```

**En producción (con Supervisor):**
```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=1 --timeout=1800
autostart=true
autorestart=true
numprocs=2
```

### Logs
- Todos los procesos se registran en Laravel Log
- Nivel INFO para operaciones exitosas
- Nivel ERROR para fallos con stack trace completo

### Estados de Orden de Trabajo Excluidos
- Órdenes con `status_id = CANCELED_WORK_ORDER_ID`
- Órdenes con `deleted_at IS NOT NULL` (soft deleted)

---

## Testing

### Caso 1: Flujo Completo Exitoso
```bash
# 1. Subir archivo
POST /api/vehicle-leakage/process
file: Formato Fugado.xlsx

Response: { "job_id": 123, "status": "pending" }

# 2. Consultar estado (repetir cada 5s)
GET /api/vehicle-leakage/status/123

Response: { "status": "processing" }
         ↓ esperar...
Response: { "status": "completed", "results": {...}, "download_url": "..." }

# 3. Descargar archivo
GET /api/vehicle-leakage/download/123

Response: Descarga directa del archivo Excel enriquecido
```

### Caso 2: VIN No Encontrado
```
VIN en Excel: INEXISTENTE123456789
Resultado esperado en columnas:
- Nombres: -
- Celular: -
- Correo: -
- Tipo Ultimo Servicio: -
```

### Caso 3: Columna VIN No Existe
```bash
POST /api/vehicle-leakage/process
# (archivo sin columna "Rango[vin]")

→ Job se crea exitosamente
→ Procesamiento falla
→ GET /status/{id} retorna:
{
  "status": "failed",
  "error_message": "No se encontró la columna 'Rango' con los VIN en el archivo Excel"
}
```

---

## Diagrama de Flujo

```
Usuario → POST /process → Response inmediato (job_id)
                              ↓
                         Job en Queue
                              ↓
                    Queue Worker procesa
                    (puede tomar minutos)
                              ↓
                    Status: completed
                              ↓
Usuario → GET /status/{id} → Obtiene download_url
                              ↓
Usuario → GET /download/{id} → Descarga archivo
```

---

## Soporte
Para reportar problemas o sugerencias, contactar al equipo de desarrollo.