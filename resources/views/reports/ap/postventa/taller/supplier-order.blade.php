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
  <title>Orden de Compra {{ $order['order_number'] }}</title>
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
      max-width: 200px;
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

    .order-info {
      margin-bottom: 15px;
      border: 1px solid #000;
      padding: 10px;
    }

    .order-info table {
      width: 100%;
      border-collapse: collapse;
    }

    .order-info td {
      padding: 4px;
      vertical-align: top;
    }

    .order-info strong {
      font-weight: bold;
    }

    .supplier-info {
      margin-bottom: 15px;
      border: 1px solid #000;
      padding: 10px;
    }

    .supplier-info h3 {
      font-size: 13px;
      font-weight: bold;
      margin-bottom: 8px;
      border-bottom: 1px solid #ccc;
      padding-bottom: 4px;
    }

    .supplier-info table {
      width: 100%;
      border-collapse: collapse;
    }

    .supplier-info td {
      padding: 3px;
      vertical-align: top;
    }

    table.details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    table.details-table th {
      background-color: #f0f0f0;
      border: 1px solid #000;
      padding: 6px;
      text-align: left;
      font-weight: bold;
      font-size: 10px;
    }

    table.details-table td {
      border: 1px solid #000;
      padding: 6px;
      font-size: 10px;
    }

    table.details-table td.text-right {
      text-align: right;
    }

    table.details-table td.text-center {
      text-align: center;
    }

    .totals {
      width: 40%;
      float: right;
      margin-bottom: 20px;
    }

    .totals table {
      width: 100%;
      border-collapse: collapse;
    }

    .totals td {
      padding: 5px;
      border: 1px solid #000;
    }

    .totals td:first-child {
      font-weight: bold;
      text-align: right;
      width: 60%;
    }

    .totals td:last-child {
      text-align: right;
      width: 40%;
    }

    .footer {
      clear: both;
      margin-top: 30px;
      padding-top: 10px;
      border-top: 1px solid #ccc;
      text-align: center;
      font-size: 10px;
    }

    .clearfix::after {
      content: "";
      display: table;
      clear: both;
    }
  </style>
</head>
<body>
<!-- Header -->
<div class="header">
  <table>
    <tr>
      <td class="logo" style="width: 20%;">
        <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="Automotores Logo">
      </td>
      <td class="center-title" style="width: 60%;">
        {{ $order['sede']->company->businessName }}
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
      <td class="company-left" style="text-align: left">
        <div>{{$order['sede']->direccion}}</div>
        <div>{{$order['sede']->province->name}}
          - {{$order['sede']->district->name}} {{$order['sede']->district->ubigeo}}</div>
        <div>RUC: {{$order['sede']->company->num_doc}}</div>
      </td>
      <td class="customer-right" style="text-align: right;">
        <div>Tel.:</div>
        <div>Email: info@automotorespakatnamu.com</div>
        <div>Web: www.automotorespakatnamu.com</div>
      </td>
    </tr>
  </table>
</div>

<!-- Sección 1: Información de la Orden -->
<div class="order-info">
  <table>
    <tr>
      <td width="25%"><strong>Nº Orden:</strong> {{ $order['order_number'] }}</td>
      <td width="25%"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($order['order_date'])->format('d/m/Y') }}</td>
      <td width="50%"></td>
    </tr>
    <tr>
      <td colspan="2"><strong>Local:</strong> {{ $order['sede']->company->businessName }}</td>
      <td><strong>Aprobado por:</strong> {{ $order['approved_by_name'] }}</td>
    </tr>
    <tr>
      <td><strong>Moneda:</strong> {{ $order['currency'] }}</td>
      <td colspan="2"><strong>Cond. Pago:</strong> {{ $order['payment_condition'] }}</td>
    </tr>
    <tr>
      <td><strong>Fecha Entrega:</strong> {{ $order['delivery_date'] }}</td>
      <td colspan="2"><strong>Lugar Entrega:</strong> {{ $order['sede']->direccion }}</td>
    </tr>
  </table>
</div>

<!-- Sección 2: Datos del Proveedor -->
<div class="supplier-info">
  <table>
    <tr>
      <td colspan="3"><strong>Proveedor:</strong> {{ $order['supplier_name'] }}</td>
    </tr>
    <tr>
      <td width="33%"><strong>RUC:</strong> {{ $order['supplier_document'] }}</td>
      <td width="67%" colspan="2"><strong>Cod. Proveedor:</strong> {{ $order['supplier_id'] }}</td>
    </tr>
    <tr>
      <td colspan="3"><strong>Dirección:</strong> {{ $order['supplier_address'] }}</td>
    </tr>
    <tr>
      <td colspan="3"><strong>Nº Solicitud de Compra:</strong> {{ $order['purchase_request_numbers'] }}</td>
    </tr>
    <tr>
      <td colspan="3"><strong>Forma de Pago:</strong> {{ $order['payment_method'] }}</td>
    </tr>
  </table>
</div>

<!-- Sección 3: Datos del Comprador -->
<div class="supplier-info">
  <table>
    <tr>
      <td width="50%"><strong>Contacto:</strong> {{ $order['created_by_name'] }}</td>
      <td width="50%"><strong>Cargo:</strong> {{ $order['created_by_position'] }}</td>
    </tr>
    <tr>
      <td><strong>Fono:</strong> {{ $order['created_by_phone'] }}</td>
      <td><strong>Email:</strong> {{ $order['created_by_email'] }}</td>
    </tr>
    <tr>
      <td><strong>Facturar a:</strong> {{ $order['sede']->company->businessName }}</td>
      <td><strong>RUC:</strong> {{ $order['sede']->company->num_doc }}</td>
    </tr>
    <tr>
      <td colspan="2"><strong>Dirección Despacho:</strong> {{ $order['sede']->direccion }}</td>
    </tr>
  </table>
</div>

<!-- Details Table -->
<table class="details-table">
  <thead>
  <tr>
    <th width="5%">N°</th>
    <th width="15%">CÓDIGO</th>
    <th width="35%">DESCRIPCIÓN</th>
    <th width="10%">UND</th>
    <th width="8%">CANT.</th>
    <th width="12%">P. UNIT.</th>
    <th width="15%">TOTAL</th>
  </tr>
  </thead>
  <tbody>
  @foreach($order['details'] as $index => $detail)
    <tr>
      <td class="text-center">{{ $index + 1 }}</td>
      <td>{{ $detail['code'] }}</td>
      <td>
        {{ $detail['description'] }}
        @if($detail['note'])
          <br><small><em>Nota: {{ $detail['note'] }}</em></small>
        @endif
      </td>
      <td class="text-center">{{ $detail['unit_measure'] }}</td>
      <td class="text-center">{{ number_format($detail['quantity'], 2) }}</td>
      <td class="text-right">{{ $order['currency_symbol'] }} {{ number_format($detail['unit_price'], 2) }}</td>
      <td class="text-right">{{ $order['currency_symbol'] }} {{ number_format($detail['total'], 2) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<!-- Totals -->
<div class="clearfix">
  <div class="totals">
    <table>
      <tr>
        <td>SUB TOTAL:</td>
        <td>{{ $order['currency_symbol'] }} {{ number_format($order['net_amount'], 2) }}</td>
      </tr>
      <tr>
        <td>IGV (18%):</td>
        <td>{{ $order['currency_symbol'] }} {{ number_format($order['tax_amount'], 2) }}</td>
      </tr>
      <tr style="background-color: #f0f0f0;">
        <td><strong>TOTAL:</strong></td>
        <td><strong>{{ $order['currency_symbol'] }} {{ number_format($order['total_amount'], 2) }}</strong></td>
      </tr>
    </table>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  <p>Documento generado automáticamente el {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
</div>
</body>
</html>
