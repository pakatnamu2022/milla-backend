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
      border-collapse: collapse;
    }

    .company-info td {
      border: none;
      vertical-align: top;
      padding: 8px;
      font-size: 9px;
      line-height: 1.6;
    }

    .company-left {
      width: 50%;
      text-align: left;
      padding-right: 20px;
      border-right: 2px solid #ddd;
    }

    .customer-right {
      width: 50%;
      text-align: right;
      padding-left: 20px;
    }

    .order-info {
      margin-bottom: 15px;
      border: 1px solid #000;
      padding: 10px;
    }

    .order-info table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .order-info td {
      padding: 6px 0;
      vertical-align: top;
      font-size: 10px;
    }

    .order-left {
      width: 40%;
    }

    .order-right {
      width: 60%;
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
      table-layout: fixed;
    }

    .supplier-info td {
      padding: 6px 0;
      vertical-align: top;
      font-size: 10px;
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

    .vertical-text {
      position: fixed;
      left: 5px;
      top: 50%;
      transform: rotate(-90deg) translateX(-50%);
      transform-origin: left top;
      writing-mode: vertical-rl;
      text-orientation: mixed;
      font-size: 8px;
      font-weight: bold;
      color: #333;
      white-space: nowrap;
      z-index: 10;
    }
  </style>
</head>
<body>
<!-- Código de Actividad Vertical -->
<div class="vertical-text">
  50102 - VENTA DE VEHICULOS AUTOMOTORES | 50203 - MANTENIMIENTO Y REPARAC. VEHICULOS | 50304 - VENTA PARTES, PIEZAS,
  ACCESORIOS
</div>
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
      <td class="company-left">
        <div><strong>Dirección:</strong> {{$order['sede']->direccion}}</div>
        <div><strong>Provincia:</strong> {{$order['sede']->province->name}}
          - {{$order['sede']->district->name}} {{$order['sede']->district->ubigeo}}</div>
        <div><strong>RUC:</strong> {{$order['sede']->company->num_doc}}</div>
      </td>
      <td class="customer-right">
        <div><strong>Tel.:</strong></div>
        <div><strong>Email:</strong> info@automotorespakatnamu.com</div>
        <div><strong>Web:</strong> www.automotorespakatnamu.com</div>
      </td>
    </tr>
  </table>
</div>

<!-- Sección 1: Información de la Orden -->
<div class="order-info">
  <table>
    <tr>
      <td class="order-left"><strong>Nº Orden:</strong> {{ $order['order_number'] }}</td>
      <td class="order-right"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($order['order_date'])->format('d/m/Y') }}
      </td>
    </tr>
    <tr>
      <td class="order-left"><strong>Local:</strong> {{ $order['sede']->company->businessName }}</td>
      <td class="order-right"><strong>Aprobado por:</strong> {{ $order['approved_by_name'] }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>Moneda:</strong> {{ $order['currency'] }}</td>
      <td class="order-right"><strong>Cond. Pago:</strong> {{ $order['payment_condition'] }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>Fecha Entrega:</strong> {{ $order['delivery_date'] }}</td>
      <td class="order-right"><strong>Lugar Entrega:</strong> {{ $order['sede']->direccion }}</td>
    </tr>
  </table>
</div>

<!-- Sección 2: Datos del Proveedor -->
<div class="supplier-info">
  <table>
    <tr>
      <td class="order-left"><strong>Proveedor:</strong> {{ $order['supplier_name'] }}</td>
      <td class="order-right"><strong>Cod. Proveedor:</strong> {{ $order['supplier_id'] }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>RUC:</strong> {{ $order['supplier_document'] }}</td>
      <td class="order-right"><strong>Dirección:</strong> {{ $order['supplier_address'] }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>Nº Solicitud de Compra:</strong> {{ $order['purchase_request_numbers'] }}</td>
      <td class="order-right"><strong>Forma de Pago:</strong> {{ $order['payment_method'] }}</td>
    </tr>
  </table>
</div>

<!-- Sección 3: Datos del Comprador -->
<div class="supplier-info">
  <table>
    <tr>
      <td class="order-left"><strong>Contacto:</strong> {{ $order['created_by_name'] }}</td>
      <td class="order-right"><strong>Cargo:</strong> {{ $order['created_by_position'] }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>Fono:</strong> {{ $order['created_by_phone'] }}</td>
      <td class="order-right"><strong>Email:</strong> {{ $order['created_by_email'] }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>Facturar a:</strong> {{ $order['sede']->company->businessName }}</td>
      <td class="order-right"><strong>RUC:</strong> {{ $order['sede']->company->num_doc }}</td>
    </tr>
    <tr>
      <td class="order-left"><strong>Dirección Despacho:</strong> {{ $order['sede']->direccion }}</td>
      <td class="order-right"></td>
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

<!-- Conditions and Notes -->
<div style="margin-top: 25px; padding: 15px; border: 1px solid #ccc; background-color: #fafafa;">
  <ol style="font-size: 9px; line-height: 1.6; margin-left: 20px; color: #333;">
    <li style="margin-bottom: 5px;">Los precios aquí reflejados son sin IGV</li>
    <li style="margin-bottom: 5px;">La factura debe venir con la copia de la Guía de Despacho, copia de la Orden de
      Compra; Indicar número de la O/C en la factura.
    </li>
    <li style="margin-bottom: 5px;">La Guía de Despacho debe mencionar el número de la O/C.</li>
    <li style="margin-bottom: 5px;">Si la mercadería no fuere recibida dentro de 5 días, nos reservamos el derecho de
      revocarla.
    </li>
    <li style="margin-bottom: 5px;">La Guía de Despacho debe contener el costo unitario por artículo y el total neto
      correspondiente al precio por cantidad.
    </li>
    <li style="margin-bottom: 5px;">El despacho de la mercadería solicitada significa la aceptación de las condiciones
      de esta orden.
    </li>
    <li style="margin-bottom: 5px;">El pago se hará al emisor de la factura.</li>
    <li style="margin-bottom: 5px;">El pago se realizará de acuerdo a las condiciones previamente establecidas con el
      proveedor.
    </li>
    <li style="margin-bottom: 5px;">Será condición para el pago la recepción conforme de la Guía de Despacho,
      reservándose siempre el comprador el derecho que le confiere el inciso segundo artículo 160 del código de
      Comercio.
    </li>
    <li style="margin-bottom: 0px;">Sr. Proveedor en nuestros registros no disponemos de una dirección de correo, para
      enviarle el detalle de sus pagos. Favor solicite actualizarla en su próxima cotización.
    </li>
  </ol>
</div>

<!-- Footer -->
<div class="footer">
  <p>Documento generado automáticamente el {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
</div>
</body>
</html>
