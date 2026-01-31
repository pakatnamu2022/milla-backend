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
      background-color: #172e66;
      color: white;
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


    .signature-section {
      margin-top: 40px;
      margin-bottom: 25px;
    }

    .signature-box {
      display: inline-block;
      width: 250px;
      text-align: center;
      font-size: 9px;
      font-weight: bold;
    }

    .signature-img {
      max-width: 200px;
      max-height: 80px;
      display: block;
      margin: 0 auto 10px auto;
    }

    .signature-line {
      width: 200px;
      border-top: 2px solid #000;
      margin: 0 auto 5px auto;
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
        <div>{{ $quotation['customer_document'] }}</div>
        <div>{{ $quotation['customer_email'] }}</div>
        <div>{{ $quotation['customer_phone'] }}</div>
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
    <td class="label-cell">Observaciones:</td>
    <td style="width: 40%;">{{ $quotation['observations'] }}</td>
    <td class="label-cell">Celular:</td>
    <td>{{ $quotation['advisor_phone'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Asesor:</td>
    <td>{{ $quotation['advisor_name'] }}</td>
    <td class="label-cell">Sucursal Venta:</td>
    <td>{{ $quotation['sede_name'] }}</td>
  </tr>
  <tr>
    <td class="label-cell">Estado:</td>
    <td>Pendiente de validación por parte del cliente</td>
    <td class="label-cell">Correo:</td>
    <td>{{ $quotation['advisor_email'] }}</td>
  </tr>
</table>


<!-- Sección 3: Detalle de la Cotización -->
<table class="details-table">
  <thead>
  <tr>
    @if($quotation['show_codes'])
      <th style="width: 10%;">Cód./Ref.</th>
      <th style="width: 35%;">Descripción</th>
    @else
      <th style="width: 45%;">Descripción</th>
    @endif
    <th style="width: 15%;">Observ.</th>
    <th style="width: 10%;">Tpo./Cant.</th>
    <th style="width: 12%;">P.Hora/PVP</th>
    <th style="width: 8%;">% Dto.</th>
    <th style="width: 10%;">Imp.Neto</th>
  </tr>
  </thead>
  <tbody>
  @foreach($quotation['details'] as $detail)
    <tr>
      @if($quotation['show_codes'])
        <td class="text-center">{{ $detail['code'] }}</td>
      @endif
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

<!-- Pie de página fijo -->
<div style="position: fixed; bottom: 20px; left: 20px; right: 20px; background-color: #fff;">

<!-- Sección de Vehículo y Totales en la misma fila -->
<div style="width: 100%; display: table; margin-bottom: 10px;">
  @if(isset($quotation['vehicle_plate']) && $quotation['vehicle_plate'] && $quotation['vehicle_plate'] !== 'N/A')
    <!-- Datos del Vehículo (Izquierda) -->
    <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 10px;">
      <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
        <tr>
          <td colspan="4"
              style="font-weight: bold; padding: 5px; border: 1px solid #000;">
            DATOS DEL VEHÍCULO
          </td>
        </tr>
        <tr>
          <td colspan="1" style="font-weight: bold; width: 30%; padding: 5px; border: 1px solid #000;">Placa:</td>
          <td colspan="1" style="padding: 5px; border: 1px solid #000;">{{ $quotation['vehicle_plate'] }}</td>
          <td colspan="1" style="font-weight: bold; padding: 5px; border: 1px solid #000;">Nº Chasis:</td>
          <td colspan="1" style="padding: 5px; border: 1px solid #000;">{{ $quotation['vehicle_vin'] }}</td>
        </tr>
        <tr>
          <td colspan="1" style="font-weight: bold; padding: 5px; border: 1px solid #000;">Nº Motor:</td>
          <td colspan="3" style="padding: 5px; border: 1px solid #000;">{{ $quotation['vehicle_engine'] }}</td>
        </tr>
        <tr>
          <td colspan="1" style="font-weight: bold; padding: 5px; border: 1px solid #000;">Modelo:</td>
          <td colspan="3"
              style="padding: 5px; border: 1px solid #000;">{{ $quotation['vehicle_brand'] }} {{ $quotation['vehicle_model'] }}</td>
        </tr>
        <tr>
          <td colspan="1" style="font-weight: bold; padding: 5px; border: 1px solid #000;">Color:</td>
          <td colspan="3" style="padding: 5px; border: 1px solid #000;">{{ $quotation['vehicle_color'] }}</td>
        </tr>
      </table>
    </div>
  @else
    <!-- Espacio vacío (Izquierda) cuando no hay vehículo -->
    <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 10px;">
      &nbsp;
    </div>
  @endif

  <!-- Totales (Derecha) -->
  <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 10px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">Subtotal:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000;">
          {{$quotation['type_currency']->symbol}} {{ number_format($quotation['subtotal'], 2) }}
        </td>
      </tr>
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">Total Dtos.:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000;">
          {{$quotation['type_currency']->symbol}} {{ number_format($quotation['total_discounts'], 2) }}
        </td>
      </tr>
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">OP. Gravadas:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000;">
          {{$quotation['type_currency']->symbol}} {{ number_format($quotation['op_gravada'], 2) }}
        </td>
      </tr>
      <tr>
        <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">IGV 18%:</td>
        <td style="text-align: right; padding: 5px; border: 1px solid #000;">
          {{$quotation['type_currency']->symbol}} {{ number_format($quotation['tax_amount'], 2) }}
        </td>
      </tr>
      <tr>
        <td
          style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000; background-color: #f0f0f0;">
          Total:
        </td>
        <td
          style="text-align: right; padding: 5px; border: 1px solid #000; background-color: #f0f0f0; font-weight: bold;">
          {{$quotation['type_currency']->code}} {{ number_format($quotation['total_amount'], 2) }}
        </td>
      </tr>
      @if($quotation['total_pagado'] > 0)
        <tr>
          <td style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000;">
            A Cuenta:
          </td>
          <td style="text-align: right; padding: 5px; border: 1px solid #000;">
            {{$quotation['type_currency']->code}} {{ number_format($quotation['total_pagado'], 2) }}
          </td>
        </tr>
        <tr>
          <td
            style="font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000; background-color: #f0f0f0;">
            Saldo Pendiente:
          </td>
          <td
            style="text-align: right; padding: 5px; border: 1px solid #000; background-color: #f0f0f0; font-weight: bold;">
            {{$quotation['type_currency']->code}} {{ number_format($quotation['saldo_pendiente'], 2) }}
          </td>
        </tr>
      @endif
    </table>
  </div>
</div>

<!-- Sección de Firma del Cliente -->
@if(isset($quotation['customer_signature']) && $quotation['customer_signature'])
  <div class="signature-section" style="text-align: center; margin-top: 15px;">
    <div class="signature-box">
      <img src="{{ $quotation['customer_signature'] }}" alt="Firma Cliente" class="signature-img">
      <div class="signature-line"></div>
      FIRMA DEL CLIENTE<br>
      {{ $quotation['customer_name'] }}
    </div>
  </div>
@endif

<!-- Sección IMPORTANTE -->
<div class="important-section" style="margin-top: 15px;">
  <div class="important-title">IMPORTANTE</div>
  <div class="important-content">
    STOCK SUJETO A VARIACIÓN SIN PREVIO AVISO. LA IMPORTACIÓN Y EL TIEMPO DE ATENCIÓN DEPENDE DEL STOCK EN
    FÁBRICA. TIEMPO DE IMPORTACIÓN 30 DÍAS ÚTILES.
  </div>
</div>

</div><!-- Fin pie de página fijo -->
</body>
</html>
