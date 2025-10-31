# CRUD de Documentos Electrónicos - Guía Frontend

## Información General

**Módulo**: Facturación Electrónica (Comercial/Posventa)
**Entidad**: Electronic Documents (Facturas, Boletas, Notas de Crédito/Débito)
**Tabla**: `ap_billing_electronic_documents`

---

## Endpoints Disponibles

### Base URL
```
/api/ap/facturacion/electronic-documents
```

### Listado de Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/` | Listar documentos electrónicos (con paginación y filtros) |
| POST | `/` | Crear nuevo documento electrónico |
| GET | `/{id}` | Obtener detalle de un documento |
| PUT/PATCH | `/{id}` | Actualizar documento electrónico |
| DELETE | `/{id}` | Eliminar documento electrónico |
| GET | `/by-entity/{module}/{entityType}/{entityId}` | Obtener documentos por entidad de origen |
| POST | `/{id}/send` | Enviar documento a SUNAT |
| POST | `/{id}/query` | Consultar estado en SUNAT |
| POST | `/{id}/cancel` | Anular documento en SUNAT |
| POST | `/{id}/credit-note` | Crear nota de crédito desde documento |
| POST | `/{id}/debit-note` | Crear nota de débito desde documento |

---

## Request para Crear/Actualizar Documento

### Estructura del Request (StoreElectronicDocumentRequest)

```typescript
interface ElectronicDocumentRequest {
  // ===== TIPO DE DOCUMENTO Y SERIE =====
  sunat_concept_document_type_id: number;  // REQUIRED - Combo de tipos de documento
  serie: string;                            // REQUIRED - Input de texto (4 caracteres)
  numero?: number;                          // OPCIONAL - Se genera automáticamente si no se envía

  // ===== TIPO DE OPERACIÓN =====
  sunat_concept_transaction_type_id: number;  // REQUIRED - Combo de tipos de transacción

  // ===== ORIGEN DEL DOCUMENTO =====
  origin_module: 'comercial' | 'posventa';  // REQUIRED - Select con 2 opciones
  origin_entity_type?: string;              // OPCIONAL - Tipo de entidad de origen (ej: 'vehicle')
  origin_entity_id?: number;                // OPCIONAL - ID de la entidad de origen
  ap_vehicle_movement_id?: number;          // OPCIONAL - Combo de movimientos de vehículos

  // ===== DATOS DEL CLIENTE =====
  sunat_concept_identity_document_type_id: number;  // REQUIRED - Combo de tipos de documento de identidad
  cliente_numero_de_documento: string;              // REQUIRED - Input de texto (max 15)
  cliente_denominacion: string;                     // REQUIRED - Input de texto (max 100)
  cliente_direccion?: string;                       // OPCIONAL - Input de texto (max 250)
  cliente_email?: string;                           // OPCIONAL - Input email (max 250)
  cliente_email_1?: string;                         // OPCIONAL - Input email (max 250)
  cliente_email_2?: string;                         // OPCIONAL - Input email (max 250)

  // ===== FECHAS =====
  fecha_de_emision: string;      // REQUIRED - Date picker (formato: YYYY-MM-DD)
  fecha_de_vencimiento?: string; // OPCIONAL - Date picker (debe ser después de fecha_de_emision)

  // ===== MONEDA Y CAMBIO =====
  sunat_concept_currency_id: number;  // REQUIRED - Combo de monedas
  tipo_de_cambio?: number;            // OPCIONAL - Input numérico (0-999.999)
  porcentaje_de_igv: number;          // REQUIRED - Input numérico (0-99.99)

  // ===== TOTALES =====
  descuento_global?: number;      // OPCIONAL - Input numérico
  total_descuento?: number;       // OPCIONAL - Input numérico
  total_anticipo?: number;        // OPCIONAL - Input numérico
  total_gravada?: number;         // OPCIONAL - Input numérico
  total_inafecta?: number;        // OPCIONAL - Input numérico
  total_exonerada?: number;       // OPCIONAL - Input numérico
  total_igv?: number;             // OPCIONAL - Input numérico
  total_gratuita?: number;        // OPCIONAL - Input numérico
  total_otros_cargos?: number;    // OPCIONAL - Input numérico
  total_isc?: number;             // OPCIONAL - Input numérico
  total: number;                  // REQUIRED - Input numérico

  // ===== PERCEPCIÓN =====
  percepcion_tipo?: number;               // OPCIONAL - Select (1-3)
  percepcion_base_imponible?: number;     // OPCIONAL - Input numérico
  total_percepcion?: number;              // OPCIONAL - Input numérico
  total_incluido_percepcion?: number;     // OPCIONAL - Input numérico

  // ===== RETENCIÓN =====
  retencion_tipo?: number;            // OPCIONAL - Select (1-2)
  retencion_base_imponible?: number;  // OPCIONAL - Input numérico
  total_retencion?: number;           // OPCIONAL - Input numérico

  // ===== DETRACCIÓN =====
  detraccion?: boolean;                       // OPCIONAL - Checkbox
  sunat_concept_detraction_type_id?: number;  // OPCIONAL - Combo de tipos de detracción (REQUIRED si detraccion=true)
  detraccion_total?: number;                  // OPCIONAL - Input numérico
  detraccion_porcentaje?: number;             // OPCIONAL - Input numérico (0-100)
  medio_de_pago_detraccion?: number;          // OPCIONAL - Select (1-12)

  // ===== NOTAS DE CRÉDITO/DÉBITO =====
  documento_que_se_modifica_tipo?: number;        // OPCIONAL - Select (1=Factura, 2=Boleta)
  documento_que_se_modifica_serie?: string;       // OPCIONAL - Input de texto (4 caracteres)
  documento_que_se_modifica_numero?: number;      // OPCIONAL - Input numérico
  sunat_concept_credit_note_type_id?: number;     // OPCIONAL - Combo de tipos de nota de crédito
  sunat_concept_debit_note_type_id?: number;      // OPCIONAL - Combo de tipos de nota de débito

  // ===== CAMPOS OPCIONALES =====
  observaciones?: string;               // OPCIONAL - Textarea (max 1000)
  condiciones_de_pago?: string;         // OPCIONAL - Input de texto (max 250)
  medio_de_pago?: string;               // OPCIONAL - Input de texto (max 250)
  placa_vehiculo?: string;              // OPCIONAL - Input de texto (max 8)
  orden_compra_servicio?: string;       // OPCIONAL - Input de texto (max 20)
  codigo_unico?: string;                // OPCIONAL - Input de texto (max 20)

  // ===== CONFIGURACIÓN =====
  enviar_automaticamente_a_la_sunat?: boolean;   // OPCIONAL - Checkbox
  enviar_automaticamente_al_cliente?: boolean;   // OPCIONAL - Checkbox
  generado_por_contingencia?: boolean;           // OPCIONAL - Checkbox

  // ===== ITEMS (OBLIGATORIOS) =====
  items: ElectronicDocumentItem[];  // REQUIRED - Array con al menos 1 item

  // ===== GUÍAS (OPCIONALES) =====
  guias?: ElectronicDocumentGuide[];  // OPCIONAL - Array

  // ===== CUOTAS VENTA AL CRÉDITO (OPCIONALES) =====
  venta_al_credito?: ElectronicDocumentInstallment[];  // OPCIONAL - Array
}

interface ElectronicDocumentItem {
  unidad_de_medida: string;              // REQUIRED - Input de texto (max 3) ej: "NIU", "ZZ"
  codigo?: string;                       // OPCIONAL - Input de texto (max 30)
  codigo_producto_sunat?: string;        // OPCIONAL - Input de texto (max 8)
  descripcion: string;                   // REQUIRED - Input de texto (max 250)
  cantidad: number;                      // REQUIRED - Input numérico (min 0.0000000001)
  valor_unitario: number;                // REQUIRED - Input numérico
  precio_unitario: number;               // REQUIRED - Input numérico
  descuento?: number;                    // OPCIONAL - Input numérico
  subtotal: number;                      // REQUIRED - Input numérico
  sunat_concept_igv_type_id: number;     // REQUIRED - Combo de tipos de afectación IGV
  igv: number;                           // REQUIRED - Input numérico
  total: number;                         // REQUIRED - Input numérico
  anticipo_regularizacion?: boolean;     // OPCIONAL - Checkbox
  anticipo_documento_serie?: string;     // OPCIONAL - Input de texto (4 caracteres)
  anticipo_documento_numero?: number;    // OPCIONAL - Input numérico
}

interface ElectronicDocumentGuide {
  guia_tipo: number;        // REQUIRED - Select (1=GR Remitente, 2=GR Transportista)
  guia_serie_numero: string; // REQUIRED - Input de texto (max 20)
}

interface ElectronicDocumentInstallment {
  cuota: number;           // REQUIRED - Input numérico (min 1)
  fecha_de_pago: string;   // REQUIRED - Date picker
  importe: number;         // REQUIRED - Input numérico
}
```

---

## Combos/Selects que Requieren Consultas a la BD

### 1. Tipo de Documento Electrónico
**Campo**: `sunat_concept_document_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_DOCUMENT_TYPE',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Opciones esperadas**:
- Factura Electrónica (ID: 29)
- Boleta de Venta Electrónica (ID: 30)
- Nota de Crédito Electrónica (ID: 31)
- Nota de Débito Electrónica (ID: 32)

**Validaciones especiales**:
- Si es Factura (29), la serie debe empezar con "F"
- Si es Boleta (30), la serie debe empezar con "B"
- Si es NC/ND (31/32), la serie debe empezar con "F" o "B"

---

### 2. Tipo de Operación/Transacción
**Campo**: `sunat_concept_transaction_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_TRANSACTION_TYPE',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Opciones esperadas** (principales):
- Venta Interna (ID: 33, code: '01')
- Venta Interna - Anticipos (ID: 36, code: '04')
- Exportación
- Ventas no domiciliadas
- Etc.

---

### 3. Tipo de Documento de Identidad del Cliente
**Campo**: `sunat_concept_identity_document_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'TYPE_DOCUMENT',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Opciones esperadas**:
- DNI (Documento Nacional de Identidad)
- RUC (Registro Único de Contribuyentes)
- Carnet de Extranjería
- Pasaporte
- Etc.

---

### 4. Moneda
**Campo**: `sunat_concept_currency_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_CURRENCY',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Opciones esperadas**:
- PEN - Soles (Nuevos Soles)
- USD - Dólares Americanos
- EUR - Euros

---

### 5. Tipo de Afectación IGV (para items)
**Campo**: `items[].sunat_concept_igv_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_IGV_TYPE',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Opciones esperadas** (principales):
- Gravado - Operación Onerosa (ID: 49, code: '10')
- Exonerado - Operación Onerosa
- Inafecto - Operación Onerosa
- Exportación (ID: 67, code: '40')
- Gratuito
- Etc.

---

### 6. Tipo de Detracción
**Campo**: `sunat_concept_detraction_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_DETRACTION_TYPE',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Nota**: REQUIRED solo si el campo `detraccion` es `true`

---

### 7. Tipo de Nota de Crédito
**Campo**: `sunat_concept_credit_note_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_CREDIT_NOTE_TYPE',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Nota**: REQUIRED solo si el tipo de documento es Nota de Crédito (31)

**Opciones esperadas**:
- Anulación de la operación
- Anulación por error en el RUC
- Corrección por error en la descripción
- Descuento global
- Descuento por ítem
- Devolución total
- Devolución por ítem
- Bonificación
- Disminución en el valor
- Etc.

---

### 8. Tipo de Nota de Débito
**Campo**: `sunat_concept_debit_note_type_id`

**Endpoint**: `GET /api/gp/mg/sunatConcepts`

**Parámetros de consulta**:
```typescript
{
  type: 'BILLING_DEBIT_NOTE_TYPE',
  status: 1
}
```

**Resource**: `SunatConceptsResource`

**Nota**: REQUIRED solo si el tipo de documento es Nota de Débito (32)

**Opciones esperadas**:
- Intereses por mora
- Aumento en el valor
- Penalidades / otros conceptos
- Etc.

---

### 9. Movimiento de Vehículo (opcional)
**Campo**: `ap_vehicle_movement_id`

**Endpoint**: Necesitarás implementar o usar el endpoint de movimientos de vehículos

**Nota**: Este combo solo se muestra si `origin_module` es "comercial" y se está facturando un vehículo específico.

**Validaciones especiales**:
- El vehículo debe estar en estado "En Travesía" (2) o "Inventario VN" (5)
- El total de la factura + anticipos previos no debe exceder el precio de venta del vehículo

---

## Resource de Respuesta

**Resource**: `ElectronicDocumentResource`

La respuesta incluye todos los campos del modelo, incluyendo:
- Datos del documento
- Relaciones con SunatConcepts (documentType, transactionType, identityDocumentType, currency, etc.)
- Items asociados (items[])
- Guías asociadas (guides[])
- Cuotas asociadas (installments[])
- Enlaces de archivos (enlace_del_pdf, enlace_del_xml, enlace_del_cdr)
- Estado SUNAT (aceptada_por_sunat, sunat_responsecode, sunat_description, etc.)
- Timestamps (created_at, updated_at, sent_at, accepted_at, cancelled_at)

---

## Validaciones Especiales del Backend

### 1. Validación de Serie según Tipo de Documento
- **Factura (29)**: Serie debe empezar con "F" (ej: "F001")
- **Boleta (30)**: Serie debe empezar con "B" (ej: "B001")
- **Nota de Crédito/Débito (31/32)**: Serie debe empezar con "F" o "B"

### 2. Validación para Notas de Crédito/Débito
Si el tipo de documento es NC o ND, son REQUIRED:
- `documento_que_se_modifica_tipo`
- `documento_que_se_modifica_serie`
- `documento_que_se_modifica_numero`
- `sunat_concept_credit_note_type_id` (para NC)
- `sunat_concept_debit_note_type_id` (para ND)

### 3. Validación de Detracción
Si `detraccion` es `true`, entonces `sunat_concept_detraction_type_id` es REQUIRED.

### 4. Validación de Vehículo (si aplica)
Si se proporciona `ap_vehicle_movement_id`:
- El vehículo debe estar en estado "En Travesía" (2) o "Inventario VN" (5)
- El total de la factura + anticipos previos no debe exceder el precio de venta del modelo del vehículo

### 5. Validación de Fecha de Vencimiento
`fecha_de_vencimiento` debe ser posterior a `fecha_de_emision`.

### 6. Items
- Debe haber al menos 1 item
- Todos los campos REQUIRED de cada item son obligatorios

---

## Estados del Documento

El documento puede tener los siguientes estados (`status`):
- **draft**: Borrador (no enviado)
- **sent**: Enviado a SUNAT
- **accepted**: Aceptado por SUNAT
- **rejected**: Rechazado por SUNAT
- **cancelled**: Anulado

---

## Flujo de Trabajo Recomendado

### Crear Documento
1. Usuario selecciona el tipo de documento (Factura/Boleta/NC/ND)
2. Sistema valida automáticamente el prefijo de la serie según el tipo
3. Usuario completa datos del cliente
4. Usuario agrega items (con tabla dinámica)
5. Sistema calcula totales automáticamente
6. Usuario puede agregar guías y cuotas (opcional)
7. Usuario guarda como borrador o envía directamente a SUNAT

### Enviar a SUNAT
1. Documento debe estar en estado "draft"
2. Se llama al endpoint `POST /{id}/send`
3. El sistema cambia el estado a "sent" y luego a "accepted" o "rejected"

### Consultar Estado
1. Se llama al endpoint `POST /{id}/query`
2. El sistema actualiza el estado del documento según respuesta de SUNAT

### Anular Documento
1. Documento debe estar aceptado por SUNAT
2. Se llama al endpoint `POST /{id}/cancel` con un `reason`
3. El sistema marca el documento como anulado

### Crear Nota de Crédito/Débito
1. Se llama al endpoint `POST /{id}/credit-note` o `POST /{id}/debit-note`
2. El sistema crea un nuevo documento (NC o ND) basado en el documento original

---

## Consideraciones Adicionales

### Anticipos
- Los anticipos se identifican con `sunat_concept_transaction_type_id = 36`
- Al regularizar un anticipo, se debe marcar `anticipo_regularizacion = true` en el item
- Se debe referenciar el anticipo con `anticipo_documento_serie` y `anticipo_documento_numero`

### Cálculo de Totales
El frontend debe calcular automáticamente:
- `subtotal` de cada item = `cantidad * precio_unitario - descuento`
- `igv` de cada item según el tipo de afectación IGV
- `total` de cada item = `subtotal + igv`
- Totales globales: `total_gravada`, `total_inafecta`, `total_exonerada`, `total_igv`, `total`

### Número Correlativo
El campo `numero` es opcional en la creación. Si no se envía, el backend lo genera automáticamente como el siguiente número correlativo para esa serie.

### Módulo de Origen
El campo `origin_module` puede ser:
- **comercial**: Para documentos relacionados con ventas de vehículos
- **posventa**: Para documentos relacionados con servicios de taller/repuestos

---

## Ejemplo de Request Completo

```json
{
  "sunat_concept_document_type_id": 29,
  "serie": "F001",
  "sunat_concept_transaction_type_id": 33,
  "origin_module": "comercial",
  "sunat_concept_identity_document_type_id": 6,
  "cliente_numero_de_documento": "20123456789",
  "cliente_denominacion": "EMPRESA EJEMPLO SAC",
  "cliente_direccion": "Av. Ejemplo 123, Lima",
  "cliente_email": "facturacion@ejemplo.com",
  "fecha_de_emision": "2025-10-31",
  "fecha_de_vencimiento": "2025-11-30",
  "sunat_concept_currency_id": 1,
  "tipo_de_cambio": 3.75,
  "porcentaje_de_igv": 18,
  "total_gravada": 10000,
  "total_igv": 1800,
  "total": 11800,
  "observaciones": "Venta de vehículo",
  "condiciones_de_pago": "Contado",
  "enviar_automaticamente_a_la_sunat": true,
  "enviar_automaticamente_al_cliente": true,
  "items": [
    {
      "unidad_de_medida": "NIU",
      "codigo": "VEH001",
      "descripcion": "VEHÍCULO TOYOTA COROLLA 2024",
      "cantidad": 1,
      "valor_unitario": 10000,
      "precio_unitario": 11800,
      "subtotal": 10000,
      "sunat_concept_igv_type_id": 49,
      "igv": 1800,
      "total": 11800
    }
  ]
}
```

---

## Mensajes de Error Comunes

| Campo | Error | Mensaje |
|-------|-------|---------|
| sunat_concept_document_type_id | required | El tipo de documento es obligatorio |
| sunat_concept_document_type_id | exists | El tipo de documento seleccionado no es válido |
| serie | required | La serie es obligatoria |
| serie | size | La serie debe tener exactamente 4 caracteres |
| serie | validation | La serie no corresponde al tipo de documento seleccionado |
| cliente_numero_de_documento | required | El número de documento del cliente es obligatorio |
| cliente_denominacion | required | El nombre o razón social del cliente es obligatorio |
| fecha_de_emision | required | La fecha de emisión es obligatoria |
| total | required | El total del documento es obligatorio |
| items | required | Debe agregar al menos un item al documento |
| items | min | Debe agregar al menos un item al documento |
| items.*.descripcion | required | La descripción del item es obligatoria |
| items.*.cantidad | required | La cantidad del item es obligatoria |
| items.*.cantidad | min | La cantidad debe ser mayor a 0 |
| documento_que_se_modifica_tipo | required | Debe especificar el documento que se modifica (para NC/ND) |
| sunat_concept_detraction_type_id | required | Debe especificar el tipo de detracción |
| ap_vehicle_movement_id | validation | El vehículo debe estar en estado 'En Travesía' o 'Inventario VN' |
| total | validation | El total excede el precio de venta del vehículo |

---

## Notas Finales

1. **Seguridad**: Todos los endpoints están protegidos por autenticación.
2. **Paginación**: El endpoint de listado soporta paginación estándar.
3. **Filtros**: Se pueden filtrar por múltiples campos (ver constante `filters` en el modelo).
4. **Ordenamiento**: Se puede ordenar por `id`, `fecha_de_emision`, `numero`, `total`.
5. **Soft Deletes**: Los documentos eliminados usan soft delete, no se eliminan físicamente.
6. **Logs**: Todas las operaciones con SUNAT se registran en logs (tabla `nubefact_logs`).
7. **Auditoría**: Se registra `created_by` y `updated_by` automáticamente.

---

## Contacto

Para más información sobre la integración con SUNAT o el servicio de Nubefact, consultar:
- `app/Http/Services/ap/facturacion/ElectronicDocumentService.php`
- `app/Http/Services/ap/facturacion/NubefactApiService.php`
