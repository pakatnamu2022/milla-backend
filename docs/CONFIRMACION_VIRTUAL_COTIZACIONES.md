# Sistema de Confirmación Virtual de Cotizaciones

## Descripción

Sistema que permite a los clientes confirmar cotizaciones desde su dispositivo móvil mediante un link único y seguro, sin necesidad de estar presentes en el taller.

## Características

- ✅ Generación de token único por cotización
- ✅ Link con expiración configurable (30 días por defecto)
- ✅ Confirmación sin autenticación (público)
- ✅ Trazabilidad completa (IP, dispositivo, fecha/hora)
- ✅ Envío automático por email
- ✅ Diferenciación entre confirmación presencial y virtual
- ✅ Regeneración de token si expira

## Campos Agregados al Modelo

```php
- confirmation_token (string, 64 chars, unique)
- confirmation_token_expires_at (datetime)
- confirmed_at (datetime)
- confirmation_channel (string: 'presencial' | 'virtual')
- confirmation_ip (string, 45 chars)
- confirmation_metadata (json: user_agent, platform, mobile, confirmed_by_name)
```

## Constantes en el Modelo

```php
ApOrderQuotations::CONFIRMATION_CHANNEL_PRESENCIAL = 'presencial'
ApOrderQuotations::CONFIRMATION_CHANNEL_VIRTUAL = 'virtual'
ApOrderQuotations::CONFIRMATION_TOKEN_VALIDITY_DAYS = 30
```

## API Endpoints

### 1. Enviar Link de Confirmación Virtual (Autenticado)

**POST** `/api/ap/postVenta/orderQuotations/{id}/send-virtual-confirmation`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Link de confirmación enviado exitosamente al correo del cliente.",
    "confirmation_link": "https://frontend.com/confirmacion-cotizacion/ABC123...",
    "sent_to": "cliente@email.com",
    "expires_at": "2026-05-15 12:00:00"
  }
}
```

### 2. Regenerar Token (Autenticado)

**POST** `/api/ap/postVenta/orderQuotations/{id}/regenerate-token`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Token regenerado exitosamente.",
    "confirmation_link": "https://frontend.com/confirmacion-cotizacion/XYZ789...",
    "expires_at": "2026-05-15 12:00:00"
  }
}
```

### 3. Ver Cotización por Token (Público - Sin Autenticación)

**GET** `/api/public/quotation-confirmation/{token}`

**Response Exitosa:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "quotation_number": "COT-2026-0001",
    "quotation_date": "2026-04-15",
    "expiration_date": "2026-04-30",
    "total_amount": 1500.00,
    "currency": {
      "code": "PEN",
      "symbol": "S/"
    },
    "vehicle": {
      "plate": "ABC-123",
      "brand": "Toyota",
      "model": "Corolla"
    },
    "client": {
      "full_name": "Juan Pérez",
      "email": "juan@email.com"
    },
    "details": [
      {
        "product_name": "Cambio de aceite",
        "quantity": 1,
        "unit_price": 150.00,
        "total": 150.00
      }
    ]
  }
}
```

**Response si ya está confirmada:**
```json
{
  "success": false,
  "message": "Esta cotización ya fue confirmada anteriormente.",
  "data": {
    "already_confirmed": true,
    "confirmed_at": "2026-04-10 14:30:00",
    "confirmation_channel": "virtual"
  }
}
```

**Response si el token expiró:**
```json
{
  "success": false,
  "message": "El enlace de confirmación ha expirado."
}
```

### 4. Confirmar Cotización (Público - Sin Autenticación)

**POST** `/api/public/quotation-confirmation/{token}`

**Body (Opcional):**
```json
{
  "notes": "Acepto la cotización. Por favor agendar para el próximo lunes.",
  "confirmed_by_name": "Juan Pérez"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cotización confirmada exitosamente. Gracias por su preferencia.",
  "data": {
    "id": 123,
    "quotation_number": "COT-2026-0001",
    "confirmed_at": "2026-04-15 10:30:00",
    "confirmation_channel": "virtual",
    "status": "por_facturar"
  }
}
```

## Flujo de Trabajo

### Desde el Backend (Asesor)

1. El asesor crea una cotización normalmente
2. Desde el detalle de la cotización, hace clic en "Enviar Link de Confirmación Virtual"
3. El sistema:
   - Genera un token único de 64 caracteres
   - Configura la expiración (30 días)
   - Envía un email al cliente con el link
   - Muestra confirmación con el link generado

### Desde el Frontend (Cliente)

1. El cliente recibe un email con un botón/link
2. Al hacer clic, se abre la página de confirmación (sin login)
3. Se muestra:
   - Detalles de la cotización
   - Productos/servicios incluidos
   - Monto total
   - Datos del vehículo
   - Información de contacto del asesor
4. El cliente puede:
   - Agregar notas (opcional)
   - Escribir su nombre (opcional)
   - Confirmar la cotización

## Página de Confirmación del Cliente (Frontend)

### Ruta Recomendada
```
/confirmacion-cotizacion/:token
```

### Componente de Ejemplo (React/Vue)

```typescript
// URL: /confirmacion-cotizacion/ABC123XYZ789...

import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

interface QuotationData {
  quotation_number: string;
  total_amount: number;
  client: { full_name: string };
  // ... otros campos
}

export default function QuotationConfirmation() {
  const { token } = useParams();
  const [quotation, setQuotation] = useState<QuotationData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [notes, setNotes] = useState('');
  const [confirmedByName, setConfirmedByName] = useState('');

  useEffect(() => {
    // Cargar cotización
    fetch(`${API_URL}/public/quotation-confirmation/${token}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setQuotation(data.data);
        } else {
          setError(data.message);
        }
      })
      .catch(() => setError('Error al cargar la cotización'))
      .finally(() => setLoading(false));
  }, [token]);

  const handleConfirm = async () => {
    try {
      const response = await fetch(
        `${API_URL}/public/quotation-confirmation/${token}`,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ notes, confirmed_by_name: confirmedByName })
        }
      );

      const data = await response.json();

      if (data.success) {
        // Mostrar mensaje de éxito y redirigir
        alert('¡Cotización confirmada exitosamente!');
      } else {
        alert(data.message);
      }
    } catch (error) {
      alert('Error al confirmar la cotización');
    }
  };

  if (loading) return <div>Cargando...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!quotation) return <div>Cotización no encontrada</div>;

  return (
    <div className="quotation-confirmation">
      <h1>Confirmación de Cotización</h1>
      <div className="quotation-details">
        <p><strong>Número:</strong> {quotation.quotation_number}</p>
        <p><strong>Cliente:</strong> {quotation.client.full_name}</p>
        <p><strong>Total:</strong> S/ {quotation.total_amount}</p>
        {/* Mostrar más detalles */}
      </div>

      <div className="confirmation-form">
        <label>
          Su nombre (opcional):
          <input
            value={confirmedByName}
            onChange={(e) => setConfirmedByName(e.target.value)}
            placeholder="Ej: Juan Pérez"
          />
        </label>

        <label>
          Notas adicionales (opcional):
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Comentarios o solicitudes especiales..."
          />
        </label>

        <button onClick={handleConfirm}>
          Confirmar Cotización
        </button>
      </div>
    </div>
  );
}
```

## Template de Email

El email enviado al cliente debe contener (como mínimo):

```html
<!DOCTYPE html>
<html>
<head>
  <title>Confirmación de Cotización</title>
</head>
<body>
  <h1>Confirmación de Cotización {{ quotation_number }}</h1>

  <p>Estimado/a {{ customer_name }},</p>

  <p>
    Le hemos enviado una cotización por un monto de
    <strong>{{ currency }} {{ total_amount }}</strong>
  </p>

  <p>Detalles del servicio:</p>
  <ul>
    <li>Vehículo: {{ vehicle_brand }} {{ vehicle_model }} - {{ vehicle_plate }}</li>
    <li>Fecha de cotización: {{ quotation_date }}</li>
    <li>Válida hasta: {{ expiration_date }}</li>
  </ul>

  <p>
    <strong>Para confirmar esta cotización, haga clic en el siguiente botón:</strong>
  </p>

  <a href="{{ confirmation_link }}"
     style="background: #007bff; color: white; padding: 12px 24px;
            text-decoration: none; border-radius: 4px; display: inline-block;">
    Confirmar Cotización
  </a>

  <p>
    O copie y pegue el siguiente enlace en su navegador:<br>
    <a href="{{ confirmation_link }}">{{ confirmation_link }}</a>
  </p>

  <p><small>Este enlace es válido hasta: {{ token_expires_at }}</small></p>

  <hr>

  <p>
    <strong>Su asesor de servicio:</strong><br>
    {{ advisor_name }}<br>
    {{ advisor_email }}<br>
    {{ advisor_phone }}
  </p>

  <p>
    <strong>Sede:</strong> {{ sede_name }}
  </p>
</body>
</html>
```

## Métodos Útiles del Modelo

```php
// Generar o regenerar token
$quotation->generateConfirmationToken();

// Obtener link de confirmación
$link = $quotation->getConfirmationLink();

// Verificar si el token expiró
if ($quotation->isConfirmationTokenExpired()) {
    // Token expirado
}

// Verificar si ya fue confirmada
if ($quotation->isConfirmed()) {
    // Ya confirmada
}
```

## Configuración

### Variable de Entorno (.env)

```env
FRONTEND_URL=https://tu-dominio.com
```

Esta URL se usa para generar el link de confirmación.

## Seguridad

1. **Token único de 64 caracteres** - Prácticamente imposible de adivinar
2. **Expiración configurable** - Por defecto 30 días
3. **No requiere autenticación** - El token es la credencial
4. **Trazabilidad completa** - Se guarda IP, user-agent, fecha/hora
5. **Validaciones**:
   - Token debe existir y no estar expirado
   - Cotización no debe estar ya confirmada
   - Cotización no debe estar descartada
   - No se puede confirmar si ya tiene factura

## Testing con Postman

### 1. Enviar Link (requiere autenticación)
```
POST http://localhost:8000/api/ap/postVenta/orderQuotations/1/send-virtual-confirmation
Headers:
  Authorization: Bearer YOUR_TOKEN
  Accept: application/json
```

### 2. Ver Cotización (público)
```
GET http://localhost:8000/api/public/quotation-confirmation/ABC123...
Headers:
  Accept: application/json
```

### 3. Confirmar (público)
```
POST http://localhost:8000/api/public/quotation-confirmation/ABC123...
Headers:
  Content-Type: application/json
  Accept: application/json
Body:
{
  "notes": "Acepto la cotización",
  "confirmed_by_name": "Juan Pérez"
}
```

## Notas Importantes

1. El servicio de email debe estar configurado correctamente en el `.env`
2. La URL del frontend debe estar configurada en `FRONTEND_URL`
3. Los campos `notes` y `confirmed_by_name` son opcionales al confirmar
4. Al confirmar, el estado de la cotización cambia automáticamente a "por_facturar"
5. Una vez confirmada, no se puede volver a confirmar
6. Si el token expira, se puede regenerar desde el backend

## Ejemplo de Flujo Completo

1. **Backend**: Asesor crea cotización #123
2. **Backend**: Asesor hace clic en "Enviar Link de Confirmación Virtual"
3. **Sistema**: Genera token `abc123xyz...`
4. **Sistema**: Envía email a `cliente@email.com` con link
5. **Email**: Cliente recibe email y hace clic en el link
6. **Frontend**: Se abre página `/confirmacion-cotizacion/abc123xyz...`
7. **Frontend**: Carga datos de la cotización (GET público)
8. **Frontend**: Muestra detalles de la cotización
9. **Cliente**: Revisa y hace clic en "Confirmar"
10. **Frontend**: Envía POST con notas opcionales
11. **Sistema**: Guarda confirmación con metadata
12. **Sistema**: Cambia estado a "por_facturar"
13. **Frontend**: Muestra mensaje de éxito
14. **Backend**: Asesor ve que la cotización fue confirmada virtualmente

---

¿Necesitas ayuda con la implementación del frontend o tienes alguna pregunta?