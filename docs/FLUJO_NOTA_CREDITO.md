# Flujo de Manejo de Notas de Crédito en Órdenes de Compra

## Resumen
Este documento explica el flujo completo cuando una Orden de Compra (OC) es anulada mediante una Nota de Crédito (NC) en Dynamics y cómo se reenvía corregida.

---

## Flujo Completo

### 1. OC Migrada Exitosamente
```
OC: OC1400000001
Estado: migration_status = 'completed'
Factura: invoice_dynamics = 'F008-00109911-FAC'
Status: true (activa)
```

### 2. Dynamics Emite Nota de Crédito
**Job:** `SyncCreditNoteDynamicsJob` (cada minuto)
- Consulta: `EXEC nePoReporteSeguimientoOrdenCompra_NotaCreditoDevolucion 'OC1400000001'`
- Si encuentra `DocumentoNumero`:
  ```
  credit_note_dynamics = 'NC-00123'
  status = false (anulada)
  ap_vehicle_status_id = VEHICULO_TRANSITO_DEVUELTO
  ```
- Crea movimiento de devolución

### 3. Dynamics Elimina Dato Tributario
**Job:** `SyncInvoiceDynamicsJob` (cada minuto)
- Procesa OCs con `migration_status='completed'` Y `credit_note_dynamics` lleno
- Consulta: `EXEC nePoReporteSeguimientoOrdenCompra_Factura 'OC1400000001'`
- Si `NroDocProvDocumento` cambió:
  ```
  invoice_dynamics = 'RE00000003' (nuevo valor)
  migration_status = 'updated_with_nc'
  ```

**Log:**
```
[2025-10-14 16:44:01] Invoice changed detected for PO OC1400000001: F008-00109911-FAC -> RE00000003
[2025-10-14 16:44:01] PO OC1400000001 updated with new invoice and marked as 'updated_with_nc'
```

### 4. Usuario Reenvía desde Frontend
**Endpoint:** `POST /api/ap/commercial/vehiclePurchaseOrder/{id}/resend`

**Request:**
```json
{
  "vin": "...",
  "year": 2024,
  "unit_price": 50000,
  "discount": 1000,
  ... // Datos corregidos
}
```

**Backend automáticamente:**
1. Valida que tenga `credit_note_dynamics` y `status=false`
2. Crea nueva OC con:
   ```
   number: OC1400000001.  (con punto)
   number_guide: NI1400000001.  (con punto)
   original_purchase_order_id: 123
   status: true
   migration_status: 'pending'
   ```
3. Sincroniza a tabla intermedia (`dbtp`) **con los números con punto**
4. Dynamics procesa normalmente

---

## Jobs Activos

| Job | Frecuencia | Función |
|-----|------------|---------|
| `SyncInvoiceDynamicsJob` | Cada minuto | Sincroniza facturas y detecta cambios después de NC |
| `SyncCreditNoteDynamicsJob` | Cada minuto | Detecta NCs y anula OCs |

---

## Jobs Deshabilitados (No se usan)

- ❌ `UpdatePurchaseOrderWithCreditNoteJob` - No se actualiza tabla intermedia
- ❌ `ProcessCreditNoteUpdatesCommand` - El reenvío se hace desde frontend

**Razón:** La nueva OC se crea directamente con punto (.) y se inserta como nueva en la tabla intermedia. NO se actualizan registros existentes.

---

## Estados de `migration_status`

| Estado | Significado |
|--------|-------------|
| `pending` | Pendiente de migrar |
| `in_progress` | En proceso |
| `completed` | Migrada exitosamente |
| `failed` | Falló la migración |
| **`updated_with_nc`** | Factura cambió después de NC (lista para reenvío) |

---

## Campo `status` (Boolean)

| Valor | Significado |
|-------|-------------|
| `true` | OC activa |
| `false` | OC anulada (tiene NC) |

---

## Para Ejecutar el Sistema

**Terminal 1 - Scheduler:**
```bash
php artisan schedule:work
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:work --queue=sync --tries=3
```

---

## Comandos Manuales para Pruebas

**Sincronizar factura de una OC:**
```bash
php artisan po:sync-invoice-dynamics --id=1
```

**Sincronizar NC de una OC:**
```bash
php artisan po:sync-credit-note-dynamics --id=1
```

**Ejecutar todos los schedulers ahora:**
```bash
php artisan schedule:run
```

---

## Logs Importantes

**Detección de cambio de factura:**
```
Invoice changed detected for PO OC1400000001: F008-00109911-FAC -> RE00000003
PO OC1400000001 updated with new invoice and marked as 'updated_with_nc'
```

**Detección de NC:**
```
Credit Note Dynamics updated for PO OC1400000001: NC-00123
```

---

## Frontend - Condición para Mostrar "Reenviar"

**Condición correcta (4 validaciones):**
```javascript
if (
  purchaseOrder.po_status === false &&              // OC anulada
  purchaseOrder.credit_note_dynamics &&             // Tiene NC
  purchaseOrder.migration_status === 'updated_with_nc' &&  // Factura cambió
  purchaseOrder.resent === false                    // NO ha sido reenviada
) {
  // ✅ Habilitar botón "Reenviar"
  // Cargar datos de la OC original para edición
}
```

**Estados durante el proceso:**

| Momento | po_status | credit_note_dynamics | migration_status | resent | Botón |
|---------|-----------|---------------------|------------------|--------|-------|
| 1. OC migrada | `true` | `null` | `completed` | `false` | ❌ No |
| 2. Se detecta NC | `false` | `NC-00123` | `completed` | `false` | ❌ No (aún no lista) |
| 3. Factura cambia | `false` | `NC-00123` | `updated_with_nc` | `false` | ✅ **Sí** |
| 4. Usuario reenvía | `false` | `NC-00123` | `updated_with_nc` | **`true`** | ❌ No (ya reenviada) |

**Razones:**
1. Esperamos a que Dynamics elimine el dato tributario (`updated_with_nc`)
2. El campo `resent` evita reenvíos múltiples de la misma OC

---

**Última actualización:** 2025-10-14
