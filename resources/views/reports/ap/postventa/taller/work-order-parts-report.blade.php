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
  <title>Reporte de Repuestos OT {{ $workOrder['work_order_number'] }}</title>
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
    }

    .header {
      margin-bottom: 2px;
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
      max-width: 200px;
      height: auto;
    }

    .center-title {
      text-align: center;
      font-size: 16px;
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
      font-size: 11px;
    }

    .company-left {
      width: 50%;
      text-align: left;
    }

    .company-right {
      width: 50%;
      text-align: left;
    }

    .work-order-info {
      margin-bottom: 10px;
      text-align: left;
      font-size: 12px;
    }

    .work-order-info strong {
      font-weight: bold;
      font-size: 12px;
    }

    table.data-section {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
      border: 1px solid #000;
    }

    table.data-section td {
      padding: 3px 5px;
      font-size: 11px;
      vertical-align: top;
      border: none;
    }

    .section-header {
      background-color: #8b8b8b;
      color: black;
      font-weight: bold;
      font-size: 11px;
      padding: 4px 5px;
      text-align: left;
      border: 1px solid #000;
    }

    .label-cell {
      font-weight: bold;
      width: 12%;
    }

    .data-cell {
      width: 38%;
    }

    table.parts-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
      border: 1px solid #000;
    }

    table.parts-table th {
      background-color: #8b8b8b;
      color: black;
      font-weight: bold;
      font-size: 10px;
      padding: 4px 3px;
      text-align: center;
      border: 1px solid #666;
    }

    table.parts-table td {
      padding: 4px 5px;
      font-size: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }

    table.parts-table td.number {
      text-align: right;
    }

    table.parts-table td.center {
      text-align: center;
    }

    table.delivery-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
      margin-bottom: 10px;
      font-size: 9px;
    }

    table.delivery-table th {
      background-color: #8b8b8b;
      color: black;
      padding: 4px 3px;
      text-align: center;
      font-weight: bold;
      border: 1px solid #666;
    }

    table.delivery-table td {
      padding: 3px 5px;
      border: 1px solid #ccc;
      text-align: left;
      font-size: 9px;
    }

    table.delivery-table td.number {
      text-align: right;
    }

    table.delivery-table td.center {
      text-align: center;
    }

    .delivery-subsection {
      margin-left: 20px;
      margin-top: 8px;
      margin-bottom: 10px;
      padding: 8px;
      background-color: #fafafa;
      border: 1px solid #ddd;
    }

    .delivery-subsection-title {
      font-weight: bold;
      font-size: 10px;
      margin-bottom: 5px;
    }

    .footer {
      margin-top: 30px;
      text-align: center;
      font-size: 9px;
      color: #666;
    }

    .page-break {
      page-break-after: always;
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

<!-- Información de la empresa -->
@if($workOrder['sede'])
<div class="company-info">
  <table>
    <tr>
      <td class="company-left" style="text-align: left">
        <div>{{ $workOrder['sede']->direccion ?? 'N/A' }}</div>
        <div>{{ $workOrder['sede']->province->name ?? '' }} - {{ $workOrder['sede']->district->name ?? '' }} {{ $workOrder['sede']->district->ubigeo ?? '' }}</div>
        <div>RUC: {{ $workOrder['sede']->company->num_doc ?? 'N/A' }}</div>
      </td>
      <td class="company-right" style="text-align: right;">
        <div>Tel.:</div>
        <div>Email: info@automotorespakatnamu.com</div>
        <div>Web: www.automotorespakatnamu.com</div>
      </td>
    </tr>
  </table>
</div>
@endif

<!-- Número de OT y fecha -->
<div class="work-order-info">
  <strong>Orden de Trabajo N° : </strong> {{ $workOrder['work_order_number'] }} &nbsp;&nbsp;&nbsp;
  <strong>Fecha : </strong> {{ \Carbon\Carbon::parse($workOrder['work_order_date'])->format('d/m/Y') }} &nbsp;&nbsp;&nbsp;
  <strong>Estado : </strong> {{ $workOrder['status'] }}
</div>

<!-- Sección: Datos del Vehículo -->
<table class="data-section">
  <tr>
    <td colspan="4" class="section-header">DATOS DEL VEHÍCULO</td>
  </tr>
  <tr>
    <td class="label-cell">Placa:</td>
    <td class="data-cell">{{ $workOrder['vehicle_plate'] }}</td>
    <td class="label-cell">Modelo:</td>
    <td class="data-cell">{{ $workOrder['vehicle_brand'] }} {{ $workOrder['vehicle_model'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">VIN:</td>
    <td class="data-cell">{{ $workOrder['vehicle_vin'] }}</td>
    <td class="label-cell">Color:</td>
    <td class="data-cell">{{ $workOrder['vehicle_color'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Kilometraje:</td>
    <td colspan="3">{{ $workOrder['vehicle_km'] }}</td>
  </tr>
</table>

<!-- Sección: Repuestos Asignados -->
<table class="parts-table">
  <thead>
  <tr>
    <th style="width: 5%;">Ítem</th>
    <th style="width: 12%;">Código</th>
    <th style="width: 35%;">Descripción</th>
    <th style="width: 15%;">Almacén</th>
    <th style="width: 11%;">Cant. Usada</th>
    <th style="width: 11%;">Cant. Asignada</th>
    <th style="width: 11%;">Cant. Pendiente</th>
  </tr>
  </thead>
  <tbody>
  @foreach($workOrder['parts'] as $index => $part)
    <tr>
      <td class="center">{{ $index + 1 }}</td>
      <td class="center">{{ $part['code'] }}</td>
      <td class="text-left">{{ $part['description'] }}</td>
      <td class="text-left">{{ $part['warehouse'] }}</td>
      <td class="number">{{ number_format($part['quantity_used'], 2) }}</td>
      <td class="number">{{ number_format($part['assigned_quantity'], 2) }}</td>
      <td class="number">{{ number_format($part['pending_quantity'], 2) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<!-- Detalle de Asignaciones a Técnicos por Repuesto -->
@foreach($workOrder['parts'] as $index => $part)
  @if($part['has_deliveries'])
    <div class="delivery-subsection">
      <div class="delivery-subsection-title">
        ASIGNACIONES DEL REPUESTO: {{ $part['code'] }} - {{ $part['description'] }}
      </div>
      <table class="delivery-table">
        <thead>
        <tr>
          <th style="width: 30%">Técnico</th>
          <th style="width: 12%">Cantidad</th>
          <th style="width: 18%">Fecha Entrega</th>
          <th style="width: 15%">Entregado Por</th>
          <th style="width: 10%">Recibido</th>
          <th style="width: 15%">Fecha Recepción</th>
        </tr>
        </thead>
        <tbody>
        @foreach($part['deliveries'] as $delivery)
          <tr>
            <td>{{ $delivery['technician_name'] }}</td>
            <td class="number">{{ number_format($delivery['delivered_quantity'], 2) }}</td>
            <td class="center">{{ $delivery['delivered_date'] }}</td>
            <td>{{ $delivery['delivered_by'] }}</td>
            <td class="center">{{ $delivery['is_received'] }}</td>
            <td class="center">{{ $delivery['received_date'] }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif
@endforeach

<!-- FOOTER -->
<div class="footer">
  <p>Reporte generado el {{ date('d/m/Y H:i:s') }}</p>
  @if($workOrder['sede'])
    <p>{{ $workOrder['sede']->name ?? 'N/A' }}</p>
  @endif
</div>

</body>
</html>
