# RESUMEN EJECUTIVO - INTEGRACIÓN NUBEFACT API

## Archivos Extraídos

1. **C:\laragon\www\milla-backend\NUBEFACT_API_DOC_EXTRACTED.txt** - Documentación completa de la API
2. **C:\laragon\www\milla-backend\NUBEFACT_CALCULOS_MANUAL_EXTRACTED.txt** - Catálogos SUNAT y fórmulas de cálculo

## Datos de Autenticación

### Se requieren 2 datos:

1. **RUTA** (única por cliente):
   - Online: `https://api.nubefact.com/api/v1/[CODIGO_UNICO]`
   - Offline: `http://localhost:8000/api/v1/[CODIGO_UNICO]`
   - Reseller: `https://[subdominio].pse.pe/api/v1/[CODIGO_UNICO]`

2. **TOKEN** (uno o varios por cuenta)

### Cómo usar:

```
POST [RUTA]
Headers:
  Authorization: [TOKEN]
  Content-Type: application/json
Body: [JSON del comprobante]
```

## 4 Operaciones Principales

### 1. Generar Comprobante
```json
{
  "operacion": "generar_comprobante",
  "tipo_de_comprobante": 1,  // 1=Factura, 2=Boleta, 3=NC, 4=ND
  "serie": "FFF1",
  "numero": 1,
  ...
}
```

### 2. Consultar Comprobante
```json
{
  "operacion": "consultar_comprobante",
  "tipo_de_comprobante": 1,
  "serie": "FFF1",
  "numero": 1
}
```

### 3. Anular Comprobante
```json
{
  "operacion": "generar_anulacion",
  "tipo_de_comprobante": 1,
  "serie": "FFF1",
  "numero": 1,
  "motivo": "ERROR DEL SISTEMA"
}
```

### 4. Consultar Anulación
```json
{
  "operacion": "consultar_anulacion",
  "tipo_de_comprobante": 1,
  "serie": "FFF1",
  "numero": 1
}
```

## Estructura JSON para Factura/Boleta

### Campos Obligatorios de Cabecera

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| operacion | String | "generar_comprobante" | "generar_comprobante" |
| tipo_de_comprobante | Integer | 1=Factura, 2=Boleta, 3=NC, 4=ND | 1 |
| serie | String | Inicia con "F" (facturas) o "B" (boletas) | "FFF1" |
| numero | Integer | Correlativo sin ceros a la izquierda | 1 |
| sunat_transaction | Integer | Tipo de operación SUNAT | 1 |
| cliente_tipo_de_documento | String | 6=RUC, 1=DNI, -=Varios, 7=Pasaporte | "6" |
| cliente_numero_de_documento | String | Número de documento | "20600695771" |
| cliente_denominacion | String | Razón social o nombre | "EMPRESA SAC" |
| cliente_direccion | String | Dirección completa | "AV. PRINCIPAL 123" |
| fecha_de_emision | Date | Formato DD-MM-YYYY | "10-05-2024" |
| moneda | Integer | 1=Soles, 2=Dólares, 3=Euros | 1 |
| porcentaje_de_igv | Numeric | Porcentaje de IGV | 18.00 |
| total | Numeric | Total del comprobante | 1180.00 |
| enviar_automaticamente_a_la_sunat | Boolean | Enviar a SUNAT | true |
| enviar_automaticamente_al_cliente | Boolean | Enviar por email | false |
| items | Array | Array de items del comprobante | [...] |

### Campos Condicionales de Cabecera

| Campo | Cuándo es requerido |
|-------|---------------------|
| tipo_de_cambio | Cuando moneda != 1 (no es Soles) |
| total_gravada | Cuando hay operaciones gravadas |
| total_exonerada | Cuando hay operaciones exoneradas |
| total_inafecta | Cuando hay operaciones inafectas |
| total_gratuita | Cuando hay operaciones gratuitas |
| total_igv | Cuando hay operaciones gravadas |
| documento_que_se_modifica_* | Solo para Notas de Crédito/Débito |
| tipo_de_nota_de_credito | Solo para Notas de Crédito |
| tipo_de_nota_de_debito | Solo para Notas de Débito |
| percepcion_* | Solo si aplica percepción |
| retencion_* | Solo si aplica retención |
| detraccion_* | Solo si aplica detracción |

### Estructura de Items

```json
{
  "unidad_de_medida": "NIU",           // Código SUNAT (NIU, ZZ, KGM, etc.)
  "codigo": "PROD001",                 // Código interno (opcional)
  "codigo_producto_sunat": "10000000", // Código SUNAT UNSPSC (opcional)
  "descripcion": "Laptop HP 15",       // Descripción del producto/servicio
  "cantidad": 2,                        // Cantidad
  "valor_unitario": 1000.00,           // Valor sin IGV
  "precio_unitario": 1180.00,          // Precio con IGV
  "subtotal": 2000.00,                 // cantidad * valor_unitario
  "tipo_de_igv": 1,                    // Ver catálogo 07
  "igv": 360.00,                       // IGV del item
  "total": 2360.00,                    // subtotal + igv
  "anticipo_regularizacion": false,     // Solo para anticipos
  "anticipo_documento_serie": "",       // Solo para anticipos
  "anticipo_documento_numero": ""       // Solo para anticipos
}
```

## Catálogos Principales para Implementación

### Tipo de Comprobante
- 1 = Factura
- 2 = Boleta
- 3 = Nota de Crédito
- 4 = Nota de Débito

### Tipo de Operación SUNAT (sunat_transaction)
- 1 = Venta Interna
- 2 = Exportación
- 30 = Operación Sujeta a Detracción
- 33 = Detracción - Servicios de Transporte Carga
- 34 = Operación Sujeta a Percepción

### Tipo de Documento Identidad (cliente_tipo_de_documento)
- 6 = RUC
- 1 = DNI
- 4 = Carnet de Extranjería
- 7 = Pasaporte
- 0 = No Domiciliado sin RUC (Exportación)
- \- = Varios (ventas menores a S/. 700)

### Moneda
- 1 = Soles (PEN)
- 2 = Dólares (USD)
- 3 = Euros (EUR)
- 4 = Libra Esterlina (GBP)

### Tipo de IGV (tipo_de_igv) - Los más comunes

| Código | Descripción | Tributo |
|--------|-------------|---------|
| 1 | Gravado - Operación Onerosa | 1000 |
| 9 | Exonerado - Operación Onerosa | 9997 |
| 10 | Inafecto - Operación Onerosa | 9998 |
| 17 | Exportación | 9995 |
| 21 | Gratuito (NO suma al total) | 9996 |

### Tipo de Nota de Crédito
- 1 = Anulación de la operación
- 2 = Anulación por error en el RUC
- 3 = Corrección por error en la descripción
- 4 = Descuento global
- 5 = Descuento por ítem
- 6 = Devolución total
- 7 = Devolución por ítem
- 13 = Ajustes - montos y/o fechas de pago

### Tipo de Nota de Débito
- 1 = Intereses por mora
- 2 = Aumento en el valor
- 3 = Penalidades/otros conceptos

### Unidades de Medida (las más comunes)
- NIU = Unidad
- ZZ = Servicio
- KGM = Kilogramo
- MTR = Metro
- LTR = Litro
- DAY = Día
- HUR = Hora
- MON = Mes

## Fórmulas de Cálculo

### Operación Gravada (CON IGV 18%)

```
subtotal = cantidad * valor_unitario
igv = subtotal * 0.18
total_item = subtotal + igv
precio_unitario = valor_unitario * 1.18

Totales comprobante:
total_gravada = Σ subtotales_gravados
total_igv = Σ igv_items
total = total_gravada + total_igv
```

### Operación Exonerada/Inafecta (SIN IGV)

```
subtotal = cantidad * valor_unitario
igv = 0
total_item = subtotal
precio_unitario = valor_unitario

Totales comprobante:
total_exonerada (o total_inafecta) = Σ subtotales
total_igv = 0
total = total_exonerada (o total_inafecta)
```

### Operación Gratuita (NO suma al total)

```
subtotal = cantidad * valor_unitario
igv = subtotal * 0.18  [solo referencial]
total_item = 0  [NO suma al total del comprobante]
precio_unitario = valor_unitario * 1.18

Totales comprobante:
total_gratuita = Σ subtotales  [solo informativo]
total_igv = 0
total = 0  [las gratuitas NO se cobran]
```

### Descuento Global (que afecta base imponible)

```
1. Calcular totales normalmente
2. descuento_sin_igv = descuento_global / 1.18
3. nueva_base_gravada = total_gravada - descuento_sin_igv
4. nuevo_igv = nueva_base_gravada * 0.18
5. total = nueva_base_gravada + nuevo_igv
```

### Percepción (se suma al total)

```
Tipo 1 (Venta Interna): 2%
Tipo 2 (Combustible): 1%
Tipo 3 (Tasa Especial): 0.5%

percepcion_base_imponible = total
total_percepcion = total * (porcentaje / 100)
total_incluido_percepcion = total + total_percepcion
```

### Retención (informativa, no modifica total)

```
Tipo 1: 3%
Tipo 2: 6%

retencion_base_imponible = total
total_retencion = total * (porcentaje / 100)
```

## Validaciones Críticas

### 1. Series
- Facturas: Deben empezar con "F" (ejemplo: "FFF1", "F001")
- Boletas: Deben empezar con "B" (ejemplo: "BBB1", "B001")
- Contingencia: NO empezar con "F" ni "B" (ejemplo: "0001")
- Longitud: Exactamente 4 caracteres

### 2. Numeración
- Empezar desde 1 para cada serie nueva
- Debe ser correlativa (sin saltos)
- Sin ceros a la izquierda

### 3. Cliente
- Facturas: Requieren RUC obligatorio (tipo_de_documento = 6)
- Boletas: Pueden usar DNI, RUC o "-" para varios
- Dirección es obligatoria en facturas, opcional en boletas

### 4. Totales
- La suma de items debe coincidir con totales
- Validar: `total = total_gravada + total_exonerada + total_inafecta + total_igv`
- Usar máximo 2 decimales para montos
- Redondear correctamente

### 5. Notas de Crédito/Débito
- Deben referenciar un comprobante existente
- Usar `documento_que_se_modifica_tipo`, `_serie`, `_numero`
- Indicar `tipo_de_nota_de_credito` o `tipo_de_nota_de_debito`

## Respuesta de Nubefact

```json
{
  "tipo_de_comprobante": 1,
  "serie": "FFF1",
  "numero": 1,
  "enlace": "https://www.nubefact.com/cpe/...",
  "enlace_del_pdf": "https://...",
  "enlace_del_xml": "https://...",
  "enlace_del_cdr": "https://...",
  "aceptada_por_sunat": true,
  "sunat_description": "La Factura numero FFF1-1, ha sido aceptada",
  "sunat_note": null,
  "sunat_responsecode": "0",
  "sunat_soap_error": "",
  "anulado": false,
  "cadena_para_codigo_qr": "20600695771 | 01 | FFF1 | 000001 | ...",
  "codigo_hash": "xMLFMnbgp1/bHEy572RKRTE9hPY="
}
```

### Campos importantes de respuesta:
- `aceptada_por_sunat`: Indica si SUNAT aceptó el documento
- `sunat_responsecode`: "0" = aceptado, otro código = error
- `sunat_description`: Mensaje de SUNAT
- `anulado`: Indica si el comprobante está anulado
- `enlace_del_pdf`: URL del PDF generado
- `enlace_del_xml`: URL del XML generado
- `enlace_del_cdr`: URL del CDR (Constancia de Recepción)

## Ejemplo Completo: Factura Simple

```json
{
  "operacion": "generar_comprobante",
  "tipo_de_comprobante": 1,
  "serie": "FFF1",
  "numero": 1,
  "sunat_transaction": 1,
  "cliente_tipo_de_documento": "6",
  "cliente_numero_de_documento": "20600695771",
  "cliente_denominacion": "EMPRESA DEMO SAC",
  "cliente_direccion": "AV. PRINCIPAL 123 LIMA - LIMA - PERU",
  "cliente_email": "cliente@empresa.com",
  "fecha_de_emision": "27-10-2024",
  "moneda": 1,
  "porcentaje_de_igv": 18.00,
  "total_gravada": 1000.00,
  "total_igv": 180.00,
  "total": 1180.00,
  "enviar_automaticamente_a_la_sunat": true,
  "enviar_automaticamente_al_cliente": false,
  "items": [
    {
      "unidad_de_medida": "NIU",
      "codigo": "PROD001",
      "descripcion": "Laptop HP 15",
      "cantidad": 1,
      "valor_unitario": 1000.00,
      "precio_unitario": 1180.00,
      "subtotal": 1000.00,
      "tipo_de_igv": 1,
      "igv": 180.00,
      "total": 1180.00
    }
  ]
}
```

## Ejemplo: Boleta con múltiples items

```json
{
  "operacion": "generar_comprobante",
  "tipo_de_comprobante": 2,
  "serie": "BBB1",
  "numero": 1,
  "sunat_transaction": 1,
  "cliente_tipo_de_documento": "1",
  "cliente_numero_de_documento": "12345678",
  "cliente_denominacion": "JUAN PEREZ GARCIA",
  "cliente_direccion": "",
  "cliente_email": "juan@email.com",
  "fecha_de_emision": "27-10-2024",
  "moneda": 1,
  "porcentaje_de_igv": 18.00,
  "total_gravada": 150.00,
  "total_igv": 27.00,
  "total": 177.00,
  "enviar_automaticamente_a_la_sunat": true,
  "enviar_automaticamente_al_cliente": false,
  "items": [
    {
      "unidad_de_medida": "NIU",
      "descripcion": "Mouse inalámbrico",
      "cantidad": 2,
      "valor_unitario": 50.00,
      "precio_unitario": 59.00,
      "subtotal": 100.00,
      "tipo_de_igv": 1,
      "igv": 18.00,
      "total": 118.00
    },
    {
      "unidad_de_medida": "NIU",
      "descripcion": "Teclado USB",
      "cantidad": 1,
      "valor_unitario": 50.00,
      "precio_unitario": 59.00,
      "subtotal": 50.00,
      "tipo_de_igv": 1,
      "igv": 9.00,
      "total": 59.00
    }
  ]
}
```

## Ejemplo: Nota de Crédito

```json
{
  "operacion": "generar_comprobante",
  "tipo_de_comprobante": 3,
  "serie": "FFF1",
  "numero": 2,
  "sunat_transaction": 1,
  "cliente_tipo_de_documento": "6",
  "cliente_numero_de_documento": "20600695771",
  "cliente_denominacion": "EMPRESA DEMO SAC",
  "cliente_direccion": "AV. PRINCIPAL 123 LIMA - LIMA - PERU",
  "fecha_de_emision": "27-10-2024",
  "moneda": 1,
  "porcentaje_de_igv": 18.00,
  "total_gravada": 1000.00,
  "total_igv": 180.00,
  "total": 1180.00,
  "documento_que_se_modifica_tipo": 1,
  "documento_que_se_modifica_serie": "FFF1",
  "documento_que_se_modifica_numero": 1,
  "tipo_de_nota_de_credito": 1,
  "enviar_automaticamente_a_la_sunat": true,
  "enviar_automaticamente_al_cliente": false,
  "items": [
    {
      "unidad_de_medida": "NIU",
      "descripcion": "Anulación de Laptop HP 15",
      "cantidad": 1,
      "valor_unitario": 1000.00,
      "precio_unitario": 1180.00,
      "subtotal": 1000.00,
      "tipo_de_igv": 1,
      "igv": 180.00,
      "total": 1180.00
    }
  ]
}
```

## Consideraciones de Implementación

### 1. Codificación
- Usar UTF-8 para todos los textos
- Escapar comillas dobles en descripciones: `"Clavos 3\" pulgadas"`
- No usar caracteres especiales que rompan el JSON

### 2. Fechas
- Formato: DD-MM-YYYY (con guiones medios)
- Ejemplo: "27-10-2024"
- La fecha de emisión debe ser la fecha actual

### 3. Decimales
- Montos: Máximo 2 decimales
- Valores/precios unitarios: Hasta 10 decimales
- Cantidades: Hasta 10 decimales
- Siempre usar punto (.) como separador decimal

### 4. Manejo de Errores
- Verificar `aceptada_por_sunat` en la respuesta
- Leer `sunat_description` para mensajes de error
- Revisar `sunat_soap_error` para errores técnicos
- Guardar los enlaces del PDF, XML y CDR

### 5. Contingencia
- Si no hay internet o SUNAT está caída
- Emitir con serie que NO empiece con "F" ni "B"
- Ejemplo: serie "0001"
- Marcar `generado_por_contingencia`: true
- Posteriormente comunicar a SUNAT

### 6. Venta al Crédito
```json
{
  "venta_al_credito": [
    {
      "cuota": 1,
      "fecha_de_pago": "27-11-2024",
      "importe": 590.00
    },
    {
      "cuota": 2,
      "fecha_de_pago": "27-12-2024",
      "importe": 590.00
    }
  ]
}
```

### 7. Guías de Remisión Adjuntas
```json
{
  "guias": [
    {
      "guia_tipo": 1,
      "guia_serie_numero": "T001-123"
    }
  ]
}
```

## Recursos Adicionales

### Documentación y Ejemplos
- Ejemplos JSON: https://www.nubefact.com/downloads/EJEMPLOS-DE-ARCHIVOS-JSON
- Código PHP: https://www.nubefact.com/downloads/PHP-INTEGRACION-CON-NUBEFACT-EJEMPLO-CODIGO-JSON
- Código C#: https://www.nubefact.com/downloads/CSHARP-INTEGRACION-CON-NUBEFACT-EJEMPLO-CODIGO-JSON
- Código Java: https://www.nubefact.com/downloads/JAVA-INTEGRACION-CON-NUBEFACT-EJEMPLO-CODIGO-JSON

### Herramientas Recomendadas
- **Insomnia**: Probador de API https://insomnia.rest/
- **JSONLint**: Validador de JSON https://jsonlint.com/
- **Postman**: Alternativa para probar API

### Catálogos SUNAT
- Catálogos completos: https://drive.google.com/file/d/160RszuLGOAxe0Lh1dLCQzuzJPpVeLZN4/view
- Unidades de medida: https://www.unece.org/fileadmin/DAM/uncefact/recommendations/rec20/
- Códigos de producto: https://www.unspsc.org/
- Ubigeos: http://webinei.inei.gob.pe:8080/sisconcode/proyecto/

## Pasos para Pasar a Producción

1. Registrarse en www.nubefact.com o tuempresa.pse.pe
2. Ir a opción API (Integración)
3. Obtener RUTA y TOKEN
4. Probar generación de documentos en ambiente DEMO
5. Generar documentos de prueba (ver lista en documentación)
6. Validar con Nubefact que todo funciona correctamente
7. Activar emisión en producción

### Documentos mínimos para validación:
- ✓ 1 Factura en Soles
- ✓ 1 Factura en Dólares
- ✓ 1 Nota de Crédito
- ✓ 1 Nota de Débito
- ✓ 1 Boleta en Soles
- ✓ 1 Consulta de estado
- ✓ 1 Comunicación de baja

## Soporte

Para más información o soporte:
- Web: www.nubefact.com
- Tickets: ayuda.nubefact.com
- Documentación: Este documento y los archivos extraídos
