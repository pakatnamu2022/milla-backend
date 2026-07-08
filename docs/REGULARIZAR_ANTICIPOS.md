# Endpoint para Regularizar Anticipos

## Descripción
Este endpoint permite registrar/regularizar anticipos de forma referencial **SIN enviar a Nubefact/SUNAT**. El documento se crea automáticamente con estado `accepted` y `aceptada_por_sunat = 1`.

## Endpoint
```
POST /api/ap/facturacion/electronic-documents/regularize-advance-payment
```

## Características
- ✅ NO envía a Nubefact (solo registro interno)
- ✅ Ya marca como aceptada_por_sunat = 1
- ✅ Estado automático: `accepted`
- ✅ Migration status: `completed`
- ✅ Solo para anticipos (is_advance_payment = 1)
- ✅ Tipo de operación automático: 36 (Anticipos)
- ✅ Calcula automáticamente el siguiente número correlativo
- ✅ Obtiene automáticamente datos del cliente
- ✅ Obtiene automáticamente tipo de cambio del día

## Campos Requeridos

### Datos del Documento
```json
{
  "sunat_concept_document_type_id": 30,  // ID del tipo de documento (Factura/Boleta)
  "series_id": 83,                       // ID de la serie
  "area_id": 882,                        // ID del área

  // Cliente
  "client_id": 14391,                    // ID del cliente

  // Fechas
  "fecha_de_emision": "2026-06-29",      // Fecha de emisión
  "fecha_de_vencimiento": null,          // Opcional (null si es al contado)

  // Moneda
  "sunat_concept_currency_id": 86,       // ID de la moneda (PEN/USD)

  // Totales
  "total_gravada": 423.73,               // Base imponible
  "total_inafecta": 0.00,                // Total inafecto (opcional)
  "total_exonerada": 0.00,               // Total exonerado (opcional)
  "total_igv": 76.27,                    // Total IGV
  "total": 500.00,                       // TOTAL DEL ANTICIPO (debe ser > 0)

  // Items
  "items": [
    {
      "account_plan_id": 29,             // ID del plan de cuentas
      "unidad_de_medida": "NIU",         // Unidad de medida
      "codigo": "471",                   // Código del producto/servicio
      "descripcion": "ANTICIPO DE REPUESTOS O.T 2594811 / TALLER ASESOR LUCERO ORMEÑO",
      "cantidad": 1.0000,                // Cantidad
      "valor_unitario": 423.73,          // Valor unitario SIN IGV
      "precio_unitario": 500.00,         // Precio unitario CON IGV
      "descuento": 0.00,                 // Descuento (opcional)
      "subtotal": 423.73,                // Subtotal
      "sunat_concept_igv_type_id": 49,   // ID tipo afectación IGV
      "igv": 76.27,                      // IGV del item
      "total": 500.00                    // Total del item
    }
  ]
}
```

### Campos Opcionales
```json
{
  // Origen (opcional)
  "origin_entity_type": "ApWorkOrder",   // Tipo: 'ApOrderQuotations' o 'ApWorkOrder'
  "origin_entity_id": 349,               // ID de la entidad origen
  "order_quotation_id": null,            // ID de cotización (si aplica)
  "work_order_id": 349,                  // ID de orden de trabajo (si aplica)

  // Información adicional
  "observaciones": "Anticipo por compra de vehículo",
  "condiciones_de_pago": "EFECTIVO",
  "medio_de_pago": "contado",
  "bank_id": 44,                         // ID del banco (opcional)
  "operation_number": null,              // Número de operación (opcional)
  "orden_compra_servicio": null          // Orden de compra (opcional)
}
```

## Ejemplo Completo

```json
{
  "sunat_concept_document_type_id": 30,
  "series_id": 83,
  "area_id": 882,
  "client_id": 14391,
  "fecha_de_emision": "2026-06-29",
  "fecha_de_vencimiento": null,
  "sunat_concept_currency_id": 86,
  "total_gravada": 423.73,
  "total_inafecta": 0.00,
  "total_exonerada": 0.00,
  "total_igv": 76.27,
  "total": 500.00,
  "origin_entity_type": "ApWorkOrder",
  "origin_entity_id": 349,
  "work_order_id": 349,
  "observaciones": "Anticipo por compra de vehículo",
  "condiciones_de_pago": "EFECTIVO",
  "medio_de_pago": "contado",
  "bank_id": 44,
  "items": [
    {
      "account_plan_id": 29,
      "unidad_de_medida": "NIU",
      "codigo": "471",
      "descripcion": "ANTICIPO DE REPUESTOS O.T 2594811 / TALLER ASESOR LUCERO ORMEÑO",
      "cantidad": 1.0000,
      "valor_unitario": 423.73,
      "precio_unitario": 500.00,
      "descuento": 0.00,
      "subtotal": 423.73,
      "sunat_concept_igv_type_id": 49,
      "igv": 76.27,
      "total": 500.00
    }
  ]
}
```

## Respuesta Exitosa

```json
{
  "success": true,
  "message": "Anticipo regularizado correctamente",
  "data": {
    "id": 1234,
    "full_number": "BO56-00000752",
    "serie": "BO56",
    "numero": 752,
    "is_advance_payment": 1,
    "status": "accepted",
    "aceptada_por_sunat": 1,
    "migration_status": "completed",
    "total": 500.00,
    // ... más datos del documento
  }
}
```

## Valores Automáticos (NO enviar)

Los siguientes campos se setean automáticamente:

- `numero`: Se calcula automáticamente (siguiente correlativo)
- `serie`: Se obtiene de `series_id`
- `full_number`: Se genera automáticamente (serie-numero)
- `is_advance_payment`: Siempre 1
- `sunat_concept_transaction_type_id`: Automático (36 - Anticipos)
- `status`: Automático ('accepted')
- `aceptada_por_sunat`: Automático (1)
- `migration_status`: Automático ('completed')
- `enviado_a_sunat_el`: Fecha actual
- `tipo_de_cambio`: Se obtiene automáticamente del día de emisión
- `exchange_rate_id`: Se obtiene automáticamente
- `created_by`: Usuario autenticado
- `sunat_concept_identity_document_type_id`: Se obtiene del cliente
- `cliente_numero_de_documento`: Se obtiene del cliente
- `cliente_denominacion`: Se obtiene del cliente
- `cliente_direccion`: Se obtiene del cliente
- `cliente_email`: Se obtiene del cliente
- `porcentaje_de_igv`: Se obtiene del cliente

## Validaciones

1. ✅ El total debe ser mayor a 0
2. ✅ Debe haber al menos 1 item
3. ✅ La serie debe ser válida para el tipo de documento
4. ✅ Debe existir tipo de cambio para la fecha de emisión
5. ✅ El cliente debe existir
6. ✅ Todos los campos requeridos deben estar presentes

## Errores Comunes

### Error: Total igual a 0
```json
{
  "success": false,
  "message": "Un anticipo no puede ser por 0 soles. El total debe ser mayor a 0."
}
```

### Error: Serie inválida
```json
{
  "success": false,
  "message": "La serie no es válida para el tipo de documento seleccionado"
}
```

### Error: Sin tipo de cambio
```json
{
  "success": false,
  "message": "No se ha registrado la tasa de cambio para la fecha de emisión (2026-06-29)."
}
```

## Notas Importantes

⚠️ **Este endpoint NO envía a Nubefact**
- Es solo para registros referenciales
- Los documentos se crean con estado `accepted`
- Ya están marcados como aceptados por SUNAT
- NO se puede usar el endpoint `/send` con estos documentos

⚠️ **Solo para anticipos**
- Este endpoint solo crea documentos con `is_advance_payment = 1`
- Para documentos normales, usar el endpoint `/store` normal