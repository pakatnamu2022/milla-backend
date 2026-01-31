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
  <title>Solicitud de Compra {{ $purchaseRequest['request_number'] }}</title>
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
      max-width: 120px;
      height: auto;
    }

    .center-title {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      padding: 5px;
    }

    .document-title {
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      margin: 15px 0;
      border-bottom: 2px solid #172e66;
      padding-bottom: 5px;
    }

    .company-info {
      margin-bottom: 10px;
    }

    .company-info table {
      width: 100%;
      border: none;
    }

    .company-info td {
      border: none;
      vertical-align: top;
      padding: 2px 5px;
      font-size: 9px;
    }

    .info-section {
      margin-bottom: 10px;
    }

    .info-section table {
      width: 100%;
      border-collapse: collapse;
    }

    .info-section td {
      padding: 3px 5px;
      font-size: 9px;
      vertical-align: top;
    }

    .info-left {
      width: 50%;
    }

    .info-right {
      width: 50%;
      text-align: right;
    }

    .section-box {
      border: 1px solid #000;
      margin-bottom: 10px;
    }

    .section-header {
      background-color: #f5f5f5;
      font-weight: bold;
      font-size: 9px;
      padding: 5px;
      border-bottom: 1px solid #000;
    }

    .section-content {
      padding: 5px;
    }

    .section-content table {
      width: 100%;
      border: none;
    }

    .section-content td {
      padding: 2px 5px;
      font-size: 9px;
      border: none;
      vertical-align: top;
    }

    .label {
      font-weight: bold;
      width: 20%;
    }

    .value {
      width: 30%;
    }

    table.details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    table.details-table th {
      background-color: #172e66;
      color: white;
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

    .total-section {
      margin-top: 10px;
    }

    .total-section table {
      width: 100%;
      border-collapse: collapse;
    }

    .total-row td {
      padding: 5px;
      font-size: 10px;
      border: 1px solid #000;
    }

    .total-label {
      font-weight: bold;
      text-align: center;
      background-color: #f5f5f5;
    }

    .total-value {
      text-align: right;
      font-weight: bold;
    }

    .comments-section {
      border: 1px solid #000;
      margin-top: 10px;
    }

    .comments-header {
      background-color: #f5f5f5;
      font-weight: bold;
      font-size: 9px;
      padding: 5px;
      border-bottom: 1px solid #000;
    }

    .comments-content {
      padding: 10px;
      min-height: 40px;
      font-size: 9px;
    }

    .page-number {
      text-align: right;
      font-size: 9px;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>

<div class="page-number">Pag. 1</div>

<!-- Encabezado -->
<div class="header">
  <table>
    <tr>
      <td class="logo" style="width: 20%;">
        <img src="{{ getBase64Image('images/ap/derco.jpg') }}" alt="Derco Logo">
      </td>
      <td style="width: 60%;"></td>
      <td class="logo" style="width: 20%;">
        <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="Automotores Logo">
      </td>
    </tr>
  </table>
</div>

<!-- Titulo del documento -->
<div class="document-title">
  SOLICITUD DE COMPRA N° {{ $purchaseRequest['request_number'] }}
</div>

<!-- Informacion de la empresa -->
<div class="company-info">
  <table>
    <tr>
      <td style="width: 60%;">
        <div style="font-weight: bold; font-size: 12px;">AUTOMOTORES PAKATNAMU S.A.C.</div>
        <div>Car. Panamericana Norte Nro. 1006</div>
        <div>Chiclayo - Lambayeque</div>
        <div>RUC: 20538993400</div>
        <div>Telefono:</div>
      </td>
      <td style="width: 40%; text-align: right;">
        <div><strong>Fecha
            Documento:</strong> {{ \Carbon\Carbon::parse($purchaseRequest['requested_date'])->format('d/m/Y') }}</div>
        <div><strong>Fecha Entrega:</strong> {{ $purchaseRequest['delivery_date'] }}</div>
        <div><strong>N° de OT:</strong> {{ $purchaseRequest['work_order_number'] }}</div>
      </td>
    </tr>
  </table>
</div>

<!-- Seccion: Pedido de repuestos -->
<div class="section-box">
  <div class="section-header">Pedido de repuestos</div>
  <div class="section-content">
    <table>
      <tr>
        <td class="label">Senor(es)</td>
        <td class="value">: {{ $purchaseRequest['supplier_name'] }}</td>
        <td class="label">Forma de pago</td>
        <td class="value">: {{ $purchaseRequest['payment_method'] }}</td>
      </tr>
      <tr>
        <td class="label">RUC</td>
        <td class="value">: {{ $purchaseRequest['supplier_ruc'] }}</td>
        <td class="label">Vendedor</td>
        <td class="value">: {{ $purchaseRequest['advisor_name'] }}</td>
      </tr>
      <tr>
        <td class="label">Direccion</td>
        <td class="value">: {{ $purchaseRequest['supplier_address'] }}</td>
        <td class="label">Almacen</td>
        <td class="value">: {{ $purchaseRequest['warehouse_name'] }}</td>
      </tr>
      <tr>
        <td class="label">Ubigeo</td>
        <td class="value">: {{ $purchaseRequest['supplier_ubigeo'] }}</td>
        <td class="label">Placa</td>
        <td class="value">: {{ $purchaseRequest['vehicle_plate'] }}</td>
      </tr>
      <tr>
        <td class="label">Ciudad</td>
        <td class="value">: {{ $purchaseRequest['supplier_city'] }}</td>
        <td class="label">VIN</td>
        <td class="value">: {{ $purchaseRequest['vehicle_vin'] }}</td>
      </tr>
      <tr>
        <td class="label">Telefono</td>
        <td class="value">: {{ $purchaseRequest['supplier_phone'] }}</td>
        <td class="label">Modelo</td>
        <td class="value">: {{ $purchaseRequest['vehicle_model'] }}</td>
      </tr>
      <tr>
        <td class="label">E-mail</td>
        <td class="value">: {{ $purchaseRequest['supplier_email'] }}</td>
        <td class="label">Solicitado por</td>
        <td class="value">:</td>
      </tr>
    </table>
  </div>
</div>

<!-- Tabla de detalles -->
<table class="details-table">
  <thead>
  <tr>
    <th style="width: 15%;">Codigo</th>
    <th style="width: 35%;">Descripcion</th>
    <th style="width: 10%;">Proced.</th>
    <th style="width: 10%;">Cantidad</th>
    <th style="width: 10%;">Precio</th>
    <th style="width: 10%;">% Dscto</th>
    <th style="width: 10%;">Total</th>
  </tr>
  </thead>
  <tbody>
  @foreach($purchaseRequest['details'] as $detail)
    <tr>
      <td class="text-left">{{ $detail['code'] }}</td>
      <td class="text-left">{{ $detail['description'] }}</td>
      <td class="text-center">{{ $detail['procedure'] }}</td>
      <td class="text-center">{{ $detail['quantity'] }}</td>
      <td class="text-right">{{ $detail['price'] }}</td>
      <td class="text-right">{{ $detail['discount'] }}</td>
      <td class="text-right">{{ $detail['total'] }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<!-- Seccion de Comentarios -->
<div class="comments-section">
  <div class="comments-header">Comentarios</div>
  <div class="comments-content">
    {{ $purchaseRequest['observations'] }}
  </div>
</div>

<!-- Seccion de Totales -->
<div style="width: 100%; display: table; margin-top: 10px;">
  <!-- Espacio vacío (Izquierda) -->
  <div style="display: table-cell; width: 50%; vertical-align: top;">
    &nbsp;
  </div>
  <!-- Totales (Derecha) -->
  <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 10px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">Subtotal:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000;">
          {{ $purchaseRequest['currency_symbol'] }} {{ $purchaseRequest['subtotal'] }}
        </td>
      </tr>
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">IGV 18%:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000;">
          {{ $purchaseRequest['currency_symbol'] }} {{ $purchaseRequest['igv'] }}
        </td>
      </tr>
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000; background-color: #f0f0f0;">Total:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000; background-color: #f0f0f0; font-weight: bold;">
          {{ $purchaseRequest['currency_symbol'] }} {{ $purchaseRequest['total'] }}
        </td>
      </tr>
    </table>
  </div>
</div>

</body>
</html>
