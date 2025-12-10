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
  <title>Cotización {{ $quotation['quotation_number'] }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 20px;
    }

    .header {
      margin-bottom: 15px;
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
      font-size: 14px;
      font-weight: bold;
      padding: 5px;
    }

    .company-info {
      margin-bottom: 15px;
    }

    .company-info table {
      width: 100%;
      border: none;
    }

    .company-info td {
      border: none;
      vertical-align: top;
      padding: 5px;
      font-size: 9px;
    }

    .company-left {
      width: 50%;
      text-align: left;
    }

    .customer-right {
      width: 50%;
      text-align: left;
    }

    .company-name {
      font-weight: bold;
      font-size: 11px;
      margin-bottom: 3px;
    }

    .quotation-info {
      margin-bottom: 10px;
      text-align: left;
      font-size: 10px;
    }

    .quotation-info strong {
      font-weight: bold;
    }

    table.data-section {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      border: 1px solid #000;
    }

    table.data-section td {
      padding: 5px;
      font-size: 9px;
      vertical-align: top;
      border: 1px solid #000;
    }

    .section-header {
      background-color: #d9d9d9;
      font-weight: bold;
      font-size: 10px;
      padding: 5px;
      text-align: left;
      border: 1px solid #000;
    }

    .label-cell {
      font-weight: bold;
      width: 25%;
    }

    table.details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    table.details-table th {
      background-color: #d9d9d9;
      font-weight: bold;
      font-size: 9px;
      padding: 5px 3px;
      text-align: center;
      border: 1px solid #000;
    }

    table.details-table td {
      padding: 4px 3px;
      font-size: 8px;
      border: 1px solid #000;
      vertical-align: middle;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .text-left {
      text-align: left;
    }

    .totals-section {
      margin-top: 10px;
      margin-bottom: 10px;
      text-align: right;
    }

    .totals-section table {
      width: 50%;
      margin-left: auto;
      border-collapse: collapse;
      font-size: 9px;
    }

    .totals-section td {
      padding: 3px 8px;
      border: none;
    }

    .totals-section .label-total {
      font-weight: bold;
      text-align: left;
    }

    .totals-section .value-total {
      text-align: right;
    }

    .important-section {
      margin-top: 15px;
      border: 1px solid #000;
      padding: 8px;
    }

    .important-title {
      font-weight: bold;
      font-size: 10px;
      margin-bottom: 5px;
      text-decoration: underline;
    }

    .important-content {
      font-size: 8px;
      line-height: 1.4;
    }

    .important-content ol {
      margin-left: 15px;
      margin-top: 5px;
    }

    .important-content li {
      margin-bottom: 3px;
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
        AUTOMOTORES PAKATNAMU S.A.C.
      </td>
      <td class="logo" style="width: 20%;">
        <img src="{{ getBase64Image('images/ap/derco.jpg') }}" alt="Derco Logo">
      </td>
    </tr>
  </table>
</div>

<!-- Información de la empresa y cliente -->
<div class="company-info">
  <table>
    <tr>
      <td class="company-left">
        <div>Car. Panamericana Norte Nro. 1006</div>
        <div>Chiclayo - Lambayeque</div>
        <div>Tel.:</div>
        <div>Email: info@automotorespakatnamu.com</div>
        <div>Web: www.automotorespakatnamu.com</div>
      </td>
      <td class="customer-right">
        <div><strong>{{ $quotation['customer_name'] }}</strong></div>
        <div>{{ $quotation['customer_address'] }}</div>
        <div>{{ $quotation['customer_document'] }} {{ $quotation['customer_district'] }}</div>
      </td>
    </tr>
  </table>
</div>

<!-- Número de propuesta y fecha -->
<div class="quotation-info">
  <strong>Nº Propuesta:</strong> {{ $quotation['quotation_number'] }} &nbsp;&nbsp;&nbsp;
  <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($quotation['quotation_date'])->format('d/m/Y') }}
</div>

<!-- Sección 1: Datos de la Propuesta y Asesor -->
<table class="data-section">
  <tr>
    <td colspan="4" class="section-header">DATOS DE LA PROPUESTA</td>
  </tr>
  <tr>
    <td class="label-cell">Descripción:</td>
    <td style="width: 40%;">{{ $quotation['observations'] }}</td>
    <td class="label-cell">Asesor:</td>
    <td style="width: 25%;">{{ $quotation['advisor_name'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Observaciones:</td>
    <td></td>
    <td class="label-cell">Celular:</td>
    <td>{{ $quotation['advisor_phone'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Estado:</td>
    <td>Pendiente de validación por parte del cliente</td>
    <td class="label-cell">Correo:</td>
    <td>{{ $quotation['advisor_email'] }}</td>
  </tr>
</table>

<!-- Sección 2: Datos del Vehículo -->
<table class="data-section">
  <tr>
    <td colspan="4" class="section-header">DATOS DEL VEHÍCULO</td>
  </tr>
  <tr>
    <td class="label-cell">Placa:</td>
    <td>{{ $quotation['vehicle_plate'] }}</td>
    <td class="label-cell">Nº Chasis:</td>
    <td>{{ $quotation['vehicle_vin'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Modelo:</td>
    <td colspan="3">{{ $quotation['vehicle_brand'] }} {{ $quotation['vehicle_model'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Color:</td>
    <td>{{ $quotation['vehicle_color'] }}</td>
    <td class="label-cell">Nº Motor:</td>
    <td>{{ $quotation['vehicle_engine'] }}</td>
  </tr>
</table>

<!-- Sección 3: Detalle de la Cotización -->
<table class="details-table">
  <thead>
    <tr>
      <th style="width: 10%;">Cód./Ref.</th>
      <th style="width: 35%;">Descripción</th>
      <th style="width: 15%;">Observ.</th>
      <th style="width: 10%;">Tpo./Cant.</th>
      <th style="width: 12%;">P.Hora/PVP</th>
      <th style="width: 8%;">Dto.</th>
      <th style="width: 10%;">Imp.Neto</th>
    </tr>
  </thead>
  <tbody>
    @foreach($quotation['details'] as $detail)
    <tr>
      <td class="text-center">{{ $detail['code'] }}</td>
      <td class="text-left">{{ $detail['description'] }}</td>
      <td class="text-left">{{ $detail['observations'] }}</td>
      <td class="text-center">{{ number_format($detail['quantity'], 2) }}</td>
      <td class="text-right">{{ number_format($detail['unit_price'], 2) }}</td>
      <td class="text-right">{{ number_format($detail['discount'], 2) }}</td>
      <td class="text-right">{{ number_format($detail['total_amount'], 2) }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<!-- Totales -->
<div class="totals-section">
  <table>
    <tr>
      <td class="label-total">Total M.O.:</td>
      <td class="value-total">S/ {{ number_format($quotation['total_labor'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">Total Recambios:</td>
      <td class="value-total">S/ {{ number_format($quotation['total_parts'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">Total Dtos.:</td>
      <td class="value-total">S/ {{ number_format($quotation['total_discounts'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">Base Propuesta:</td>
      <td class="value-total">S/ {{ number_format($quotation['subtotal'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">IGV 18.00%:</td>
      <td class="value-total">S/ {{ number_format($quotation['subtotal'] * 0.18, 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">Total Propuesta:</td>
      <td class="value-total">PEN {{ number_format($quotation['total_amount'], 2) }}</td>
    </tr>
  </table>
</div>

<!-- Sección IMPORTANTE -->
<div class="important-section">
  <div class="important-title">IMPORTANTE</div>
  <div class="important-content">
    <ol>
      <li>Los precios mostrados son en soles e incluyen IGV.</li>
      <li>Aquellos repuestos que sean necesarios cambiar y no estén detallados en la presente propuesta serán cotizados e informados previamente al cliente antes de su cambio.</li>
      <li>La presente propuesta tiene una validez de {{ $quotation['validity_days'] ?? 'N/A' }} días desde la fecha de emisión.</li>
      <li>Los trabajos adicionales que se requieran realizar serán informados y presupuestados antes de su ejecución.</li>
      <li>El plazo de entrega está sujeto a la disponibilidad de repuestos en stock.</li>
    </ol>
  </div>
</div>

</body>
</html>