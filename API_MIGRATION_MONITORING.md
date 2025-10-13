# API de Monitoreo de Migración de Órdenes de Compra

## Base URL
```
/api/ap/commercial/vehiclePurchaseOrder/migration
```

Todas las rutas requieren autenticación con token Bearer.

---

## Endpoints Disponibles

### 1. **Resumen General**
Obtiene un resumen de todas las OCs por estado de migración.

**GET** `/summary`

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 150,
    "pending": 10,
    "in_progress": 5,
    "completed": 130,
    "failed": 5
  }
}
```

---

### 2. **Listar Órdenes con Estado de Migración**
Lista todas las órdenes de compra con su estado de migración y resumen de pasos.

**GET** `/orders`

**Query Parameters:**
- `per_page` (opcional): Número de resultados por página (default: 15)
- `status` (opcional): Filtrar por estado (`pending`, `in_progress`, `completed`, `failed`)
- `page` (opcional): Número de página

**Ejemplos:**
```
GET /orders?per_page=20
GET /orders?status=pending
GET /orders?status=failed&per_page=10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "number": "OC0200000001",
        "number_guide": "NI0200000001",
        "vin": "ABC123456789",
        "migration_status": "completed",
        "migrated_at": "2025-10-13 16:35:00",
        "created_at": "2025-10-13 16:30:00",
        "updated_at": "2025-10-13 16:35:00",
        "steps_summary": {
          "pending": 0,
          "in_progress": 0,
          "completed": 8,
          "failed": 0,
          "total": 8
        }
      },
      {
        "id": 2,
        "number": "OC1400000001",
        "number_guide": "NI1400000001",
        "vin": "XYZ987654321",
        "migration_status": "in_progress",
        "migrated_at": null,
        "created_at": "2025-10-13 16:31:00",
        "updated_at": "2025-10-13 16:32:00",
        "steps_summary": {
          "pending": 0,
          "in_progress": 3,
          "completed": 5,
          "failed": 0,
          "total": 8
        }
      }
    ],
    "per_page": 15,
    "total": 150
  }
}
```

---

### 3. **Logs de Migración de una OC**
Obtiene todos los logs de migración de una orden de compra específica.

**GET** `/{id}/logs`

**Ejemplo:** `GET /1/logs`

**Response:**
```json
{
  "success": true,
  "data": {
    "purchase_order": {
      "id": 1,
      "number": "OC0200000001",
      "number_guide": "NI0200000001",
      "migration_status": "completed",
      "migrated_at": "2025-10-13 16:35:00",
      "created_at": "2025-10-13 16:30:00"
    },
    "logs": [
      {
        "id": 1,
        "step": "supplier",
        "step_name": "Proveedor",
        "status": "completed",
        "status_name": "Completado",
        "table_name": "neInTbProveedor",
        "external_id": "20123456789",
        "proceso_estado": 1,
        "proceso_estado_name": "Procesado Exitosamente",
        "error_message": null,
        "attempts": 1,
        "last_attempt_at": "2025-10-13 16:31:10",
        "completed_at": "2025-10-13 16:31:10",
        "created_at": "2025-10-13 16:30:39",
        "updated_at": "2025-10-13 16:31:10"
      },
      {
        "id": 2,
        "step": "article",
        "step_name": "Artículo",
        "status": "completed",
        "status_name": "Completado",
        "table_name": "neInTbArticulo",
        "external_id": "HILUX2024",
        "proceso_estado": 1,
        "proceso_estado_name": "Procesado Exitosamente",
        "error_message": null,
        "attempts": 1,
        "last_attempt_at": "2025-10-13 16:31:12",
        "completed_at": "2025-10-13 16:31:12",
        "created_at": "2025-10-13 16:30:39",
        "updated_at": "2025-10-13 16:31:12"
      },
      {
        "id": 6,
        "step": "reception",
        "step_name": "Recepción",
        "status": "in_progress",
        "status_name": "En Progreso",
        "table_name": "neInTbRecepcion",
        "external_id": "NI0200000001",
        "proceso_estado": 0,
        "proceso_estado_name": "Pendiente de Procesar",
        "error_message": "OC aún no procesada. ProcesoEstado: 0...",
        "attempts": 3,
        "last_attempt_at": "2025-10-13 16:32:45",
        "completed_at": null,
        "created_at": "2025-10-13 16:30:39",
        "updated_at": "2025-10-13 16:32:45"
      }
    ]
  }
}
```

---

### 4. **Historial Timeline de una OC**
Obtiene el historial completo de eventos de migración en formato timeline.

**GET** `/{id}/history`

**Ejemplo:** `GET /1/history`

**Response:**
```json
{
  "success": true,
  "data": {
    "purchase_order": {
      "id": 1,
      "number": "OC0200000001",
      "number_guide": "NI0200000001",
      "migration_status": "completed",
      "migrated_at": "2025-10-13 16:35:00"
    },
    "timeline": [
      {
        "step": "supplier",
        "step_name": "Proveedor",
        "events": [
          {
            "timestamp": "2025-10-13 16:30:39",
            "event": "created",
            "description": "Paso 'supplier' creado",
            "status": "pending"
          },
          {
            "timestamp": "2025-10-13 16:31:10",
            "event": "attempt",
            "description": "Intento #1 de sincronización",
            "status": "completed",
            "error": null
          },
          {
            "timestamp": "2025-10-13 16:31:10",
            "event": "completed",
            "description": "Paso completado exitosamente",
            "status": "completed",
            "proceso_estado": 1
          }
        ]
      },
      {
        "step": "reception",
        "step_name": "Recepción",
        "events": [
          {
            "timestamp": "2025-10-13 16:30:39",
            "event": "created",
            "description": "Paso 'reception' creado",
            "status": "pending"
          },
          {
            "timestamp": "2025-10-13 16:31:25",
            "event": "attempt",
            "description": "Intento #1 de sincronización",
            "status": "failed",
            "error": "OC aún no procesada. ProcesoEstado: 0"
          },
          {
            "timestamp": "2025-10-13 16:32:25",
            "event": "attempt",
            "description": "Intento #2 de sincronización",
            "status": "failed",
            "error": "OC aún no procesada. ProcesoEstado: 0"
          },
          {
            "timestamp": "2025-10-13 16:35:00",
            "event": "completed",
            "description": "Paso completado exitosamente",
            "status": "completed",
            "proceso_estado": 1
          }
        ]
      }
    ]
  }
}
```

---

### 5. **Estadísticas de Migración**
Obtiene estadísticas generales del proceso de migración.

**GET** `/statistics`

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_orders": 150,
      "completed_orders": 130,
      "failed_orders": 5,
      "success_rate": 86.67
    },
    "performance": {
      "avg_attempts_per_step": 1.35,
      "avg_migration_time_seconds": 245.5,
      "avg_migration_time_minutes": 4.09
    },
    "failed_steps": [
      {
        "step": "reception",
        "step_name": "Recepción",
        "failures": 12
      },
      {
        "step": "article",
        "step_name": "Artículo",
        "failures": 3
      }
    ]
  }
}
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Éxito |
| 401 | No autenticado |
| 404 | Recurso no encontrado |
| 500 | Error del servidor |

---

## Ejemplos de Uso

### JavaScript (Fetch API)

```javascript
// Obtener resumen
fetch('/api/ap/commercial/vehiclePurchaseOrder/migration/summary', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => console.log(data));

// Obtener logs de una OC
fetch('/api/ap/commercial/vehiclePurchaseOrder/migration/1/logs', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => console.log(data));
```

### cURL

```bash
# Resumen
curl -X GET \
  "http://localhost/api/ap/commercial/vehiclePurchaseOrder/migration/summary" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Listar órdenes pendientes
curl -X GET \
  "http://localhost/api/ap/commercial/vehiclePurchaseOrder/migration/orders?status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Logs de una OC
curl -X GET \
  "http://localhost/api/ap/commercial/vehiclePurchaseOrder/migration/1/logs" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Notas

- Todos los endpoints requieren autenticación con token Bearer
- Las respuestas están en formato JSON
- Los timestamps están en formato `Y-m-d H:i:s` (América/Lima)
- Los estados de migración son: `pending`, `in_progress`, `completed`, `failed`
- Los pasos del proceso son 8 en total (ver logs para detalles)
