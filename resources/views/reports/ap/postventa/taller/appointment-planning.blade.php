@php
  function getBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) {
      return '';
    }
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }
@endphp
  <!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agenda Reserva</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      padding: 20px;
      position: relative;
    }

    .header {
      margin-bottom: 20px;
    }

    .header table {
      width: 100%;
      border: none;
    }

    .header td {
      border: none;
      vertical-align: middle;
    }

    .logo {
      text-align: center;
    }

    .logo img {
      max-width: 100px;
      height: auto;
    }

    .center-title {
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      padding: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    table.bordered, table.bordered th, table.bordered td {
      border: 1px solid #000;
    }

    td, th {
      padding: 6px 8px;
      vertical-align: top;
    }

    .label {
      font-weight: bold;
      font-size: 10px;
      background-color: #f0f0f0;
    }

    .section-title {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      padding: 8px;
      text-align: center;
      font-size: 12px;
    }

    .value {
      font-size: 10px;
    }

    .footer {
      position: fixed;
      bottom: 20px;
      left: 20px;
      right: 20px;
      text-align: center;
      font-size: 9px;
      border-top: 1px solid #000;
      padding-top: 10px;
    }

    .comments {
      min-height: 60px;
      border: 1px solid #000;
      padding: 8px;
      margin-top: 10px;
    }

    .comments-title {
      font-weight: bold;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>

<!-- Encabezado -->
<div class="header">
  <table>
    <tr>
      <td class="logo" style="width: 20%;">
        <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="Automotores Logo">
      </td>
      <td class="center-title" style="width: 60%;">
        AGENDA RESERVA
      </td>
      <td class="logo" style="width: 20%;">
        <img src="{{ getBase64Image('images/ap/derco.jpg') }}" alt="Derco Logo">
      </td>
    </tr>
  </table>
</div>

<!-- Sección 1: Reserva de Hora -->
<div class="section-title">RESERVA DE HORA</div>
<table class="bordered">
  <tr>
    <td class="label" style="width: 25%;">Sucursal:</td>
    <td class="value" colspan="3">AUTOMOTORES PAKATNAMU S.A.C</td>
  </tr>
  <tr>
    <td class="label">Nro Cita:</td>
    <td class="value" style="width: 25%;">{{ str_pad($appointment['id'], 6, '0', STR_PAD_LEFT) }}</td>
    <td class="label" style="width: 25%;">Estado Reserva:</td>
    <td class="value" style="width: 25%;">Agendado(Cita)</td>
  </tr>
  <tr>
    <td class="label">Recepcionista:</td>
    <td class="value" colspan="3">{{ $appointment['advisor_name'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Fecha de Reserva:</td>
    <td class="value">{{ \Carbon\Carbon::parse($appointment['date_appointment'])->format('d/m/Y') }}</td>
    <td class="label">Hora de Reserva:</td>
    <td class="value">{{ substr($appointment['time_appointment'], 0, 5) }}</td>
  </tr>
  <tr>
    <td class="label">Fecha de Entrega:</td>
    <td class="value">{{ \Carbon\Carbon::parse($appointment['delivery_date'])->format('d/m/Y') }}</td>
    <td class="label">Hora de Entrega:</td>
    <td class="value">{{ substr($appointment['delivery_time'], 0, 5) }}</td>
  </tr>
  <tr>
    <td class="label">Fecha de Generación:</td>
    <td class="value" colspan="3">{{ \Carbon\Carbon::parse($appointment['created_at'])->format('d/m/Y H:i:s') }}</td>
  </tr>
</table>

<!-- Sección 2: Cliente -->
<div class="section-title">CLIENTE</div>
<table class="bordered">
  <tr>
    <td class="label" style="width: 25%;">RUC/DNI:</td>
    <td class="value" colspan="3">{{ $appointment['client_document'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Nombre Completo / Razón Social:</td>
    <td class="value" colspan="3">{{ $appointment['full_name_client'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Dirección:</td>
    <td class="value" colspan="3">{{ $appointment['client_address'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Ubigeo:</td>
    <td class="value">{{ $appointment['client_ubigeo'] ?? 'N/A' }}</td>
    <td class="label" style="width: 25%;">Ciudad:</td>
    <td class="value" style="width: 25%;">{{ $appointment['client_city'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Teléfono Móvil:</td>
    <td class="value">{{ $appointment['phone_client'] ?? 'N/A' }}</td>
    <td class="label">E-mail:</td>
    <td class="value">{{ $appointment['email_client'] ?? 'N/A' }}</td>
  </tr>
</table>

<!-- Sección 3: Vehículo Servicio -->
<div class="section-title">VEHÍCULO SERVICIO</div>
<table class="bordered">
  <tr>
    <td class="label" style="width: 25%;">Marca:</td>
    <td class="value" style="width: 25%;">{{ $appointment['vehicle_brand'] ?? 'N/A' }}</td>
    <td class="label" style="width: 25%;">Placa:</td>
    <td class="value" style="width: 25%;">{{ $appointment['plate'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Versión:</td>
    <td class="value">{{ $appointment['vehicle_version'] ?? 'N/A' }}</td>
    <td class="label">Año:</td>
    <td class="value">{{ $appointment['vehicle_year'] ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label">Chasis (VIN):</td>
    <td class="value">{{ $appointment['vehicle_vin'] ?? 'N/A' }}</td>
    <td class="label">Color:</td>
    <td class="value">{{ $appointment['vehicle_color'] ?? 'N/A' }}</td>
  </tr>
</table>

<!-- Comentarios -->
<div class="comments">
  <div class="comments-title">Comentarios / Descripción del Servicio:</div>
  <div>{{ $appointment['description'] ?? 'Sin comentarios' }}</div>
</div>

<!-- Pie de página -->
<div class="footer">
  AUTOMOTORES PAKATNAMU S.A.C. - Car. Panamericana Norte Nro. 1006 Chiclayo - Lambayeque
</div>

</body>
</html>
