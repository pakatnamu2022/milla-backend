@php
  $getBase64ImagePurchaseReception = function($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) {
      return '';
    }
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  };
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recepción de Orden de Compra {{ $purchase_order_number }}</title>
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

    .document-title {
      margin-bottom: 15px;
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      background-color: #8b8b8b;
      padding: 8px;
      border: 1px solid #000;
    }

    table.data-section {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      border: 1px solid #000;
    }

    table.data-section td {
      padding: 4px 8px;
      font-size: 11px;
      vertical-align: top;
      border: none;
    }

    .section-header {
      background-color: #8b8b8b;
      color: black;
      font-weight: bold;
      font-size: 11px;
      padding: 4px 8px;
      text-align: left;
      border: 1px solid #000;
    }

    .label-cell {
      font-weight: bold;
      width: 20%;
    }

    .data-cell {
      width: 30%;
    }

    table.details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
      border: 1px solid #000;
    }

    table.details-table th {
      background-color: #8b8b8b;
      color: black;
      font-weight: bold;
      font-size: 9px;
      padding: 4px 3px;
      text-align: center;
      border: 1px solid #666;
    }

    table.details-table td {
      padding: 3px 3px;
      font-size: 9px;
      border: 1px solid #ddd;
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

    .requests-section {
      margin-top: 10px;
      margin-bottom: 10px;
    }

    .requests-section ul {
      margin: 5px 0 0 20px;
      padding: 0;
    }

    .requests-section li {
      margin-bottom: 3px;
      font-size: 10px;
    }
  </style>
</head>
<body>

<!-- Encabezado -->
<div class="header">
  <table>
    <tr>
      <td class="logo" style="width: 20%;">
        <img src="{{ $getBase64ImagePurchaseReception('images/ap/logo-ap.png') }}" alt="Automotores Logo">
      </td>
      <td class="center-title" style="width: 60%;">
        AUTOMOTORES PAKATNAMU S.A.C.
      </td>
      <td class="logo" style="width: 20%;">
        <img src="{{ $getBase64ImagePurchaseReception('images/ap/derco.jpg') }}" alt="Derco Logo">
      </td>
    </tr>
  </table>
</div>

<!-- Información de la empresa -->
<div class="company-info">
  <table>
    <tr>
      <td class="company-left" style="text-align: left">
        <div>{{ $sede->direccion }}</div>
        <div>{{ $sede->province->name }} - {{ $sede->district->name }} {{ $sede->district->ubigeo }}</div>
        <div>RUC: {{ $sede->company->num_doc }}</div>
      </td>
      <td class="company-right" style="text-align: right;">
        <div>Tel.:</div>
        <div>Email: info@automotorespakatnamu.com</div>
        <div>Web: www.automotorespakatnamu.com</div>
      </td>
    </tr>
  </table>
</div>

<!-- Título del documento -->
<div class="document-title">
  ORDEN DE COMPRA RECEPCIONADA
</div>

<!-- Datos de la orden de compra -->
<table class="data-section">
  <tr>
    <td colspan="4" class="section-header">DATOS DE LA ORDEN DE COMPRA</td>
  </tr>
  <tr>
    <td class="label-cell">PROVEEDOR:</td>
    <td class="data-cell">{{ $supplier_name }}</td>
    <td class="label-cell">SEDE:</td>
    <td class="data-cell">{{ $sede_abbreviation }}</td>
  </tr>
  <tr>
    <td class="label-cell">N° ORDEN DE COMPRA:</td>
    <td class="data-cell">{{ $purchase_order_number }}</td>
    <td class="label-cell">RESPONSABLE:</td>
    <td class="data-cell">{{ $responsible_name }}</td>
  </tr>
  <tr>
    <td class="label-cell">TOTAL SIN IGV:</td>
    <td class="data-cell">{{ $currency_symbol }} {{ $total_without_tax }}</td>
    <td class="label-cell">FECHA RECEPCIÓN:</td>
    <td class="data-cell">{{ $reception_date }}</td>
  </tr>
</table>

<!-- Solicitudes de compra asociadas -->
@if(isset($purchase_requests) && count($purchase_requests) > 0)
<div class="requests-section">
  <table class="data-section">
    <tr>
      <td class="section-header">SOLICITUDES DE COMPRA ASOCIADAS CON RESPONSABLE</td>
    </tr>
    <tr>
      <td style="padding: 8px;">
        <ul>
          @foreach($purchase_requests as $request)
            <li>{{ $request['request_number'] }} - {{ $request['responsible_name'] }}</li>
          @endforeach
        </ul>
      </td>
    </tr>
  </table>
</div>
@endif

<!-- Detalle de repuestos recepcionados -->
<table class="details-table">
  <thead>
  <tr>
    <th style="width: 8%;">CÓDIGO</th>
    <th style="width: 18%;">DESCRIPCIÓN</th>
    <th style="width: 10%;">MARCA</th>
    <th style="width: 10%;">MODELO</th>
    <th style="width: 6%;">CANT.</th>
    <th style="width: 8%;">P.UNIT.</th>
    <th style="width: 8%;">TOTAL</th>
    <th style="width: 8%;">PLACA</th>
    <th style="width: 12%;">CLIENTE</th>
    <th style="width: 12%;">ASESOR RESP.</th>
  </tr>
  </thead>
  <tbody>
  @foreach($items as $item)
    <tr>
      <td class="text-center">{{ $item['code'] }}</td>
      <td class="text-left">{{ $item['description'] }}</td>
      <td class="text-center">{{ $item['brand'] }}</td>
      <td class="text-center">{{ $item['model'] }}</td>
      <td class="text-center">{{ $item['quantity'] }}</td>
      <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
      <td class="text-right">{{ number_format($item['total'], 2) }}</td>
      <td class="text-center">{{ $item['plate'] }}</td>
      <td class="text-left">{{ $item['client'] }}</td>
      <td class="text-left">{{ $item['advisor'] }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

</body>
</html>