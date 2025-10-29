# DOCUMENTACIÓN - INTEGRACIÓN GUÍAS DE REMISIÓN CON NUBEFACT

## ÍNDICE
1. [Configuración de Ambiente](#configuración-de-ambiente)
2. [Modo DEMO vs Modo PRODUCCIÓN](#modo-demo-vs-modo-producción)
3. [Endpoints Disponibles](#endpoints-disponibles)
4. [Cómo Realizar Pruebas](#cómo-realizar-pruebas)
5. [Ejemplos de Uso](#ejemplos-de-uso)
6. [Respuestas de la API](#respuestas-de-la-api)
7. [Solución de Problemas](#solución-de-problemas)

---

## CONFIGURACIÓN DE AMBIENTE

### Variables de Entorno (.env)

Para usar Nubefact, debes configurar las siguientes variables en tu archivo `.env`:

```env
# NUBEFACT API Configuration
NUBEFACT_API_URL=https://api.nubefact.com/api/v1/TU_RUTA_AQUI
NUBEFACT_TOKEN=tu_token_aqui
NUBEFACT_RUC=tu_ruc_aqui
NUBEFACT_ENVIRONMENT=demo  # 'demo' o 'production'
NUBEFACT_TIMEOUT=60
```

### Archivo de Configuración (config/nubefact.php)

El archivo de configuración ya está creado:

```php
return [
    'api_url' => env('NUBEFACT_API_URL', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN', ''),
    'ruc' => env('NUBEFACT_RUC', ''),
    'environment' => env('NUBEFACT_ENVIRONMENT', 'demo'),
    'timeout' => env('NUBEFACT_TIMEOUT', 60),
];
```

---

## MODO DEMO VS MODO PRODUCCIÓN

### 🔵 MODO DEMO (Pruebas - NO envía a SUNAT real)

**¿Qué es el modo DEMO?**
- Es un ambiente de pruebas proporcionado por Nubefact
- **NO envía documentos a la SUNAT real**
- Las validaciones son parciales (no valida si RUC o DNI existen realmente)
- Ideal para desarrollo y pruebas
- Los documentos generados NO tienen validez legal

**Cómo activar modo DEMO:**

1. **En tu archivo `.env`:**
```env
NUBEFACT_ENVIRONMENT=demo
```

2. **Obtener credenciales DEMO:**
   - Ve a https://www.nubefact.com/register (crear cuenta demo)
   - O ingresa a tu cuenta en https://tuempresa.pse.pe (Reseller)
   - Ve a la sección **API (Integración)**
   - Copia tu RUTA y TOKEN de pruebas

3. **Ejemplo de configuración DEMO:**
```env
NUBEFACT_API_URL=https://api.nubefact.com/api/v1/48239908-7ae7-4353-824d-071765d4
NUBEFACT_TOKEN=1c4239064a3f441880d7ced75eea4383b831c0bf26944169b
NUBEFACT_RUC=20600000001
NUBEFACT_ENVIRONMENT=demo
```

### 🔴 MODO PRODUCCIÓN (Real - SÍ envía a SUNAT)

**¿Qué es el modo PRODUCCIÓN?**
- Envía documentos a la SUNAT real
- Validaciones completas y estrictas
- Los documentos tienen validez legal
- **¡CUIDADO! Los documentos enviados se registran oficialmente**

**Cómo activar modo PRODUCCIÓN:**

```env
NUBEFACT_ENVIRONMENT=production
```

### ✅ Verificar en qué modo estás

Puedes verificar el modo actual ejecutando en Tinker:

```bash
php artisan tinker
```

Luego ejecuta:
```php
config('nubefact.environment')
// Debe retornar: "demo" o "production"

config('nubefact.api_url')
// Verifica que la URL sea correcta
```

---

## ENDPOINTS DISPONIBLES

### 1. 📤 ENVIAR GUÍA A NUBEFACT/SUNAT

**Endpoint:**
```
POST /api/v1/comercial/shippingGuides/{id}/send-to-nubefact
```

**Descripción:**
Envía la guía de remisión a SUNAT mediante Nubefact. Según la documentación de Nubefact, este es el **PASO 1** del proceso.

**Headers requeridos:**
```json
{
  "Authorization": "Bearer {tu_token_jwt}",
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

**Parámetros:**
- `{id}`: ID de la guía de remisión que deseas enviar

**Ejemplo de Request:**
```bash
POST http://localhost/api/v1/comercial/shippingGuides/123/send-to-nubefact
```

**Validaciones previas:**
- La guía NO debe estar ya aceptada por SUNAT
- La guía NO debe estar anulada
- El campo `requires_sunat` debe ser `true`

**Respuesta exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Guía enviada a Nubefact. Use la operación de consulta para verificar si SUNAT la aceptó.",
  "data": {
    "id": 123,
    "document_number": "T001-00000001",
    "series": "T001",
    "correlative": "00000001",
    "is_sunat_registered": true,
    "aceptada_por_sunat": false,
    "sent_at": "2025-10-29T12:35:47.000000Z",
    "enlace": "",
    "enlace_del_pdf": "",
    "enlace_del_xml": "",
    "enlace_del_cdr": ""
  },
  "nubefact_response": {
    "nota_importante": "Para generar el PDF en las Guías de Remisión se requiere que la Sunat la acepte primero...",
    "tipo_de_comprobante": 7,
    "serie": "T001",
    "numero": 1,
    "aceptada_por_sunat": false
  }
}
```

**⚠️ IMPORTANTE - Proceso de 2 pasos según Nubefact:**

Según la documentación oficial de Nubefact:

1. **PASO 1 - Enviar** (`send-to-nubefact`):
   - Envía la guía a Nubefact
   - Nubefact la registra pero **NO genera PDF/XML/CDR inmediatamente**
   - La respuesta inicial tendrá `aceptada_por_sunat: false`
   - Los enlaces (PDF, XML, CDR) estarán vacíos

2. **PASO 2 - Consultar** (`query-from-nubefact`):
   - Espera unos segundos/minutos
   - Consulta el estado de la guía
   - Si SUNAT ya la aceptó, recibirás los enlaces (PDF, XML, CDR)

**Respuesta con error (400 Bad Request):**
```json
{
  "success": false,
  "message": "Error al enviar la guía a Nubefact: La guía ya ha sido aceptada por SUNAT"
}
```

---

### 2. 🔍 CONSULTAR ESTADO DE GUÍA

**Endpoint:**
```
POST /api/v1/comercial/shippingGuides/{id}/query-from-nubefact
```

**Descripción:**
Consulta el estado de una guía previamente enviada. Este es el **PASO 2** del proceso según Nubefact. La SUNAT puede tomar segundos o minutos en aceptar la guía.

**Headers requeridos:**
```json
{
  "Authorization": "Bearer {tu_token_jwt}",
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

**Parámetros:**
- `{id}`: ID de la guía de remisión que deseas consultar

**Ejemplo de Request:**
```bash
POST http://localhost/api/v1/comercial/shippingGuides/123/query-from-nubefact
```

**Validaciones previas:**
- La guía DEBE haber sido enviada previamente (tener `sent_at` no nulo)

**Respuesta exitosa - Aceptada por SUNAT (200 OK):**
```json
{
  "success": true,
  "message": "La guía ha sido aceptada por SUNAT",
  "data": {
    "id": 123,
    "document_number": "T001-00000001",
    "is_sunat_registered": true,
    "aceptada_por_sunat": true,
    "accepted_at": "2025-10-29T12:36:30.000000Z",
    "enlace": "http://www.nubefact.com/guia/564db835-fd3f-4ac0-937b-cdabb9d8a04f",
    "enlace_del_pdf": "http://www.nubefact.com/guia/564db835-fd3f-4ac0-937b-cdabb9d8a04f.pdf",
    "enlace_del_xml": "http://www.nubefact.com/guia/564db835-fd3f-4ac0-937b-cdabb9d8a04f.xml",
    "enlace_del_cdr": "http://www.nubefact.com/guia/564db835-fd3f-4ac0-937b-cdabb9d8a04f.cdr",
    "cadena_para_codigo_qr": "https://e-factura.sunat.gob.pe/v1/contribuyente/gre/comprobantes/descargaqr?hashqr=PB9GHszJi9h3WYsVW00fVfgqrn"
  },
  "nubefact_response": {
    "tipo_de_comprobante": 7,
    "serie": "T001",
    "numero": 1,
    "aceptada_por_sunat": true,
    "enlace": "http://www.nubefact.com/guia/...",
    "enlace_del_pdf": "http://www.nubefact.com/guia/....pdf",
    "enlace_del_xml": "http://www.nubefact.com/guia/....xml",
    "enlace_del_cdr": "http://www.nubefact.com/guia/....cdr",
    "cadena_para_codigo_qr": "https://e-factura.sunat.gob.pe/..."
  }
}
```

**Respuesta exitosa - Aún NO aceptada (200 OK):**
```json
{
  "success": true,
  "message": "Estado consultado. La guía aún no ha sido aceptada por SUNAT.",
  "data": {
    "id": 123,
    "aceptada_por_sunat": false,
    "enlace": "",
    "enlace_del_pdf": "",
    "enlace_del_xml": "",
    "enlace_del_cdr": ""
  }
}
```

**Respuesta con error (400 Bad Request):**
```json
{
  "success": false,
  "message": "Error al consultar la guía en Nubefact: La guía no ha sido enviada a SUNAT aún"
}
```

---

## CÓMO REALIZAR PRUEBAS

### 🧪 Escenario 1: Pruebas en Modo DEMO (Recomendado)

#### Paso 1: Configurar ambiente DEMO

```env
NUBEFACT_ENVIRONMENT=demo
NUBEFACT_API_URL=https://api.nubefact.com/api/v1/TU_RUTA_DEMO
NUBEFACT_TOKEN=TU_TOKEN_DEMO
NUBEFACT_RUC=20600000001
```

#### Paso 2: Verificar que estás en modo DEMO

```bash
php artisan tinker
```

```php
// Verificar configuración
config('nubefact.environment'); // Debe retornar "demo"

// Limpiar caché de configuración si no se actualiza
php artisan config:clear
```

#### Paso 3: Crear una guía de remisión de prueba

Usa el endpoint normal de creación:

```bash
POST /api/v1/comercial/shippingGuides
```

Con datos de prueba:
```json
{
  "document_type": 7,
  "issuer_type": "NOSOTROS",
  "document_series_id": 1,
  "issue_date": "2025-10-29",
  "requires_sunat": true,
  "total_packages": 5,
  "total_weight": 100.50,
  "sede_transmitter_id": 1,
  "sede_receiver_id": 2,
  "transmitter_id": 1,
  "receiver_id": 2,
  "transfer_reason_id": 1,
  "transfer_modality_id": 1,
  "plate": "ABC123",
  "driver_doc": "12345678",
  "driver_name": "JUAN PEREZ",
  "license": "Q12345678",
  "notes": "Guía de prueba en modo DEMO"
}
```

#### Paso 4: Enviar a Nubefact (PASO 1)

```bash
POST /api/v1/comercial/shippingGuides/123/send-to-nubefact
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Guía enviada a Nubefact. Use la operación de consulta...",
  "data": {
    "aceptada_por_sunat": false
  }
}
```

#### Paso 5: Esperar y Consultar (PASO 2)

**⏱️ Espera 10-30 segundos** y luego consulta:

```bash
POST /api/v1/comercial/shippingGuides/123/query-from-nubefact
```

**Respuesta esperada (si SUNAT aceptó):**
```json
{
  "success": true,
  "message": "La guía ha sido aceptada por SUNAT",
  "data": {
    "aceptada_por_sunat": true,
    "enlace_del_pdf": "http://www.nubefact.com/guia/...pdf"
  }
}
```

#### Paso 6: Descargar el PDF de prueba

Si la guía fue aceptada, puedes descargar el PDF desde:

```
http://www.nubefact.com/guia/XXXXXXXX.pdf
```

**⚠️ NOTA IMPORTANTE EN MODO DEMO:**
- El PDF generado NO tiene validez legal
- Las validaciones son parciales
- No verifica que DNI/RUC existan realmente en SUNAT
- Es solo para pruebas de integración

---

### 🔴 Escenario 2: Pruebas en Modo PRODUCCIÓN (CON CUIDADO)

**⚠️ ADVERTENCIA: En modo producción SÍ se envía a SUNAT real**

Solo usa este modo cuando:
- ✅ Ya probaste todo en modo DEMO
- ✅ Los datos son reales y correctos
- ✅ La guía debe tener validez legal
- ✅ Estás autorizado para generar documentos oficiales

```env
NUBEFACT_ENVIRONMENT=production
```

El proceso es idéntico al modo DEMO, pero los documentos se registran oficialmente.

---

## EJEMPLOS DE USO

### Ejemplo con cURL

#### 1. Enviar guía a Nubefact:

```bash
curl -X POST \
  http://localhost/api/v1/comercial/shippingGuides/123/send-to-nubefact \
  -H 'Authorization: Bearer tu_token_jwt' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json'
```

#### 2. Consultar estado:

```bash
curl -X POST \
  http://localhost/api/v1/comercial/shippingGuides/123/query-from-nubefact \
  -H 'Authorization: Bearer tu_token_jwt' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json'
```

### Ejemplo con Postman/Insomnia

**Colección para importar:**

```json
{
  "name": "Guías de Remisión - Nubefact",
  "requests": [
    {
      "name": "1. Enviar Guía a Nubefact",
      "method": "POST",
      "url": "{{base_url}}/api/v1/comercial/shippingGuides/{{guide_id}}/send-to-nubefact",
      "headers": {
        "Authorization": "Bearer {{token}}",
        "Content-Type": "application/json",
        "Accept": "application/json"
      }
    },
    {
      "name": "2. Consultar Estado de Guía",
      "method": "POST",
      "url": "{{base_url}}/api/v1/comercial/shippingGuides/{{guide_id}}/query-from-nubefact",
      "headers": {
        "Authorization": "Bearer {{token}}",
        "Content-Type": "application/json",
        "Accept": "application/json"
      }
    }
  ]
}
```

**Variables de entorno:**
```
base_url = http://localhost
token = tu_token_jwt
guide_id = 123
```

### Ejemplo con PHP/Guzzle

```php
use GuzzleHttp\Client;

$client = new Client();

// PASO 1: Enviar guía
$response = $client->post('http://localhost/api/v1/comercial/shippingGuides/123/send-to-nubefact', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ]
]);

$result = json_decode($response->getBody(), true);
echo "Guía enviada: " . $result['message'] . "\n";

// PASO 2: Esperar 15 segundos
sleep(15);

// PASO 3: Consultar estado
$response = $client->post('http://localhost/api/v1/comercial/shippingGuides/123/query-from-nubefact', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ]
]);

$result = json_decode($response->getBody(), true);

if ($result['data']['aceptada_por_sunat']) {
    echo "✅ Guía aceptada por SUNAT\n";
    echo "PDF: " . $result['data']['enlace_del_pdf'] . "\n";
} else {
    echo "⏳ Guía aún en proceso, consultar nuevamente\n";
}
```

### Ejemplo con JavaScript/Axios

```javascript
const axios = require('axios');

const baseUrl = 'http://localhost/api/v1/comercial';
const token = 'tu_token_jwt';
const guideId = 123;

const headers = {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
};

// PASO 1: Enviar guía
async function enviarGuia() {
  try {
    const response = await axios.post(
      `${baseUrl}/shippingGuides/${guideId}/send-to-nubefact`,
      {},
      { headers }
    );

    console.log('✅ Guía enviada:', response.data.message);
    return response.data;
  } catch (error) {
    console.error('❌ Error:', error.response?.data?.message);
    throw error;
  }
}

// PASO 2: Consultar estado
async function consultarGuia() {
  try {
    const response = await axios.post(
      `${baseUrl}/shippingGuides/${guideId}/query-from-nubefact`,
      {},
      { headers }
    );

    if (response.data.data.aceptada_por_sunat) {
      console.log('✅ Guía aceptada por SUNAT');
      console.log('📄 PDF:', response.data.data.enlace_del_pdf);
    } else {
      console.log('⏳ Guía aún en proceso');
    }

    return response.data;
  } catch (error) {
    console.error('❌ Error:', error.response?.data?.message);
    throw error;
  }
}

// Ejecutar proceso completo
(async () => {
  await enviarGuia();

  // Esperar 15 segundos
  console.log('⏳ Esperando 15 segundos...');
  await new Promise(resolve => setTimeout(resolve, 15000));

  await consultarGuia();
})();
```

---

## RESPUESTAS DE LA API

### Códigos de Estado HTTP

| Código | Significado | Cuándo ocurre |
|--------|-------------|---------------|
| 200 | OK | Operación exitosa |
| 400 | Bad Request | Error de validación o lógica de negocio |
| 401 | Unauthorized | Token JWT inválido o expirado |
| 404 | Not Found | Guía no encontrada |
| 500 | Server Error | Error interno del servidor |

### Estructura de Respuesta Exitosa

```json
{
  "success": true,
  "message": "Mensaje descriptivo",
  "data": {
    // Datos de la guía actualizada
  },
  "nubefact_response": {
    // Respuesta completa de Nubefact
  }
}
```

### Estructura de Respuesta con Error

```json
{
  "success": false,
  "message": "Descripción del error"
}
```

### Campos Importantes en la Respuesta

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `is_sunat_registered` | boolean | Si la guía fue registrada en SUNAT |
| `aceptada_por_sunat` | boolean | Si SUNAT aceptó la guía |
| `sent_at` | datetime | Fecha y hora de envío |
| `accepted_at` | datetime | Fecha y hora de aceptación |
| `enlace` | string | URL base del documento |
| `enlace_del_pdf` | string | URL del PDF |
| `enlace_del_xml` | string | URL del XML |
| `enlace_del_cdr` | string | URL del CDR (Constancia de SUNAT) |
| `cadena_para_codigo_qr` | string | Cadena para generar código QR |
| `sunat_description` | string | Descripción de SUNAT (errores) |
| `sunat_responsecode` | string | Código de respuesta de SUNAT |
| `error_message` | string | Mensaje de error si falló |

---

## SOLUCIÓN DE PROBLEMAS

### ❌ Error: "La guía ya ha sido aceptada por SUNAT"

**Causa:** Intentas enviar una guía que ya fue aceptada.

**Solución:** No puedes re-enviar una guía aceptada. Si necesitas corregirla, debes:
1. Anular la guía actual
2. Crear una nueva guía con los datos correctos

---

### ❌ Error: "No se puede enviar una guía anulada"

**Causa:** La guía tiene `cancelled_at` no nulo.

**Solución:** No puedes enviar guías anuladas. Crea una nueva guía.

---

### ❌ Error: "Esta guía no requiere registro en SUNAT"

**Causa:** El campo `requires_sunat` es `false`.

**Solución:** Actualiza la guía y establece `requires_sunat: true` antes de enviarla.

---

### ❌ Error: "La guía no ha sido enviada a SUNAT aún"

**Causa:** Intentas consultar una guía que nunca fue enviada.

**Solución:** Primero usa el endpoint `send-to-nubefact` antes de consultar.

---

### ⚠️ La consulta retorna "aceptada_por_sunat: false" siempre

**Posibles causas:**
1. **La SUNAT aún no procesó la guía** → Espera más tiempo (30-60 segundos) y vuelve a consultar
2. **Hay errores en los datos de la guía** → Revisa los logs de Nubefact en la tabla `nubefact_shipping_guide_logs`
3. **Problemas con credenciales** → Verifica que tu token y RUC sean correctos

**Cómo revisar los logs:**

```php
php artisan tinker
```

```php
// Ver el último log de una guía
$guide = \App\Models\ap\comercial\ShippingGuides::find(123);
$lastLog = $guide->logs()->latest()->first();

echo "Operación: " . $lastLog->operation . "\n";
echo "Éxito: " . ($lastLog->success ? 'Sí' : 'No') . "\n";
echo "Error: " . $lastLog->error_message . "\n";
echo "\nRequest:\n" . $lastLog->request_payload . "\n";
echo "\nResponse:\n" . $lastLog->response_payload . "\n";
```

---

### ⚠️ Error: Datos inválidos en el payload

**Causa:** Faltan campos obligatorios o tienen formato incorrecto.

**Solución:** Verifica que la guía tenga:
- ✅ Serie válida (T001 para remitente, V001 para transportista)
- ✅ Correlativo numérico
- ✅ Fecha de emisión
- ✅ Peso bruto > 0
- ✅ Datos del conductor (si es transporte privado)
- ✅ Puntos de partida y llegada con ubigeos válidos
- ✅ Items/productos
- ✅ Destinatario y remitente

---

### 🔍 Verificar Logs Completos

Todos los requests y responses se guardan en la tabla `nubefact_shipping_guide_logs`:

```sql
SELECT
    id,
    shipping_guide_id,
    operation,
    success,
    error_message,
    http_status_code,
    created_at
FROM nubefact_shipping_guide_logs
WHERE shipping_guide_id = 123
ORDER BY created_at DESC;
```

---

## CHECKLIST DE PRUEBAS

### ✅ Antes de Probar

- [ ] Configurar variables de entorno en `.env`
- [ ] Establecer `NUBEFACT_ENVIRONMENT=demo`
- [ ] Ejecutar `php artisan config:clear`
- [ ] Verificar con `php artisan tinker` que `config('nubefact.environment')` retorna "demo"
- [ ] Tener credenciales válidas de Nubefact (RUTA y TOKEN)

### ✅ Durante las Pruebas

- [ ] Crear guía con `requires_sunat = true`
- [ ] Enviar guía con `POST /shippingGuides/{id}/send-to-nubefact`
- [ ] Verificar respuesta: `is_sunat_registered = true`
- [ ] Esperar 15-30 segundos
- [ ] Consultar con `POST /shippingGuides/{id}/query-from-nubefact`
- [ ] Si aún no está aceptada, esperar más y volver a consultar
- [ ] Verificar que `aceptada_por_sunat = true`
- [ ] Descargar PDF desde `enlace_del_pdf`

### ✅ Revisar Logs

- [ ] Consultar tabla `nubefact_shipping_guide_logs`
- [ ] Verificar que `success = 1`
- [ ] Si hay errores, revisar `error_message`
- [ ] Revisar `request_payload` para ver qué se envió
- [ ] Revisar `response_payload` para ver qué respondió Nubefact

---

## CONTACTO Y SOPORTE

- **Documentación Nubefact:** https://docs.nubefact.com
- **Soporte Nubefact:** ayuda.nubefact.com
- **Cuenta Demo:** https://www.nubefact.com/register

---

**Última actualización:** 2025-10-29
**Versión:** 1.0
