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

  $documentTypeMap = [
    29 => 'FACTURA ELECTRÓNICA',
    30 => 'BOLETA DE VENTA ELECTRÓNICA',
    31 => 'NOTA DE CRÉDITO ELECTRÓNICA',
    32 => 'NOTA DE DÉBITO ELECTRÓNICA',
  ];

  $documentTypeName = $documentTypeMap[$document['sunat_concept_document_type_id']] ?? $document['document_type_name'];
  $numeroCompleto = $document['numero_completo'] ?? ($document['serie'] . '-' . $document['numero']);
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $documentTypeName }} - {{ $numeroCompleto }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 15px;
      position: relative;
      line-height: 1.3;
    }

    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 100px;
      color: rgba(200, 200, 200, 0.1);
      font-weight: bold;
      z-index: -1;
      white-space: nowrap;
    }

    .header {
      border: 2px solid #000;
      padding: 10px;
      margin-bottom: 10px;
    }

    .header-row {
      display: table;
      width: 100%;
      margin-bottom: 5px;
    }

    .header-left {
      display: table-cell;
      width: 60%;
      vertical-align: top;
    }

    .header-right {
      display: table-cell;
      width: 40%;
      vertical-align: top;
      text-align: center;
      border: 2px solid #000;
      padding: 8px;
    }

    .company-name {
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .company-info {
      font-size: 9px;
      line-height: 1.4;
    }

    .logo {
      max-width: 100px;
      height: auto;
      margin-bottom: 5px;
    }

    .document-type {
      font-size: 12px;
      font-weight: bold;
      margin-bottom: 5px;
      text-transform: uppercase;
    }

    .document-number {
      font-size: 16px;
      font-weight: bold;
      color: #c00;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
    }

    table, th, td {
      border: 1px solid #000;
    }

    th {
      background-color: #e0e0e0;
      padding: 5px;
      font-weight: bold;
      text-align: left;
      font-size: 9px;
    }

    td {
      padding: 4px;
      vertical-align: top;
      font-size: 9px;
    }

    .label {
      font-weight: bold;
    }

    .text-right {
      text-align: right;
    }

    .text-center {
      text-align: center;
    }

    .totals-table {
      width: 40%;
      float: right;
      margin-top: 10px;
    }

    .total-row {
      background-color: #f5f5f5;
      font-weight: bold;
    }

    .observations {
      clear: both;
      margin-top: 20px;
      padding: 8px;
      border: 1px solid #000;
      min-height: 40px;
    }

    .footer {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid #000;
      font-size: 8px;
      text-align: center;
    }

    .qr-code {
      width: 120px;
      height: 120px;
      margin: 10px auto;
      display: block;
    }

    .section-title {
      background-color: #e0e0e0;
      padding: 5px;
      font-weight: bold;
      text-align: center;
      margin-top: 5px;
      border: 1px solid #000;
    }

    .status-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 3px;
      font-weight: bold;
      font-size: 9px;
      margin-left: 5px;
    }

    .status-accepted {
      background-color: #28a745;
      color: white;
    }

    .status-rejected {
      background-color: #dc3545;
      color: white;
    }

    .status-pending {
      background-color: #ffc107;
      color: black;
    }

    .status-cancelled {
      background-color: #6c757d;
      color: white;
    }
  </style>
</head>
<body>
@if($document['anulado'] || $document['is_cancelled'])
  <div class="watermark">ANULADO</div>
@elseif(!$document['aceptada_por_sunat'])
  <div class="watermark">BORRADOR</div>
@endif

<!-- Encabezado -->
<div class="header">
  <div class="header-row">
    <div class="header-left">
      <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="AP Logo" class="logo">
      <div class="company-name">AP AUTOMOTORES PAKATNAMU SAC</div>
      <div class="company-info">
        RUC: 20487506522<br>
        Dirección: CAR. A PIMENTEL KM 5 - PIMENTEL - CHICLAYO - LAMBAYEQUE<br>
        Teléfono: (074) 606-060 | Email: ventas@automotorespakatnamu.com<br>
        www.automotorespakatnamu.com
      </div>
    </div>
    <div class="header-right">
      <div class="document-type">{{ $documentTypeName }}</div>
      <div class="document-number">{{ $numeroCompleto }}</div>
      @if($document['aceptada_por_sunat'])
        <span class="status-badge status-accepted">ACEPTADO POR SUNAT</span>
      @elseif($document['is_rejected'])
        <span class="status-badge status-rejected">RECHAZADO</span>
      @elseif($document['anulado'] || $document['is_cancelled'])
        <span class="status-badge status-cancelled">ANULADO</span>
      @else
        <span class="status-badge status-pending">PENDIENTE</span>
      @endif
    </div>
  </div>
</div>

<!-- Información del Cliente -->
<table>
  <tr>
    <th colspan="4" class="section-title">DATOS DEL CLIENTE</th>
  </tr>
  <tr>
    <td class="label" style="width: 20%;">{{ $document['identity_document_type_name'] }}:</td>
    <td style="width: 30%;">{{ $document['cliente_numero_de_documento'] }}</td>
    <td class="label" style="width: 20%;">Fecha de Emisión:</td>
    <td style="width: 30%;">{{ \Carbon\Carbon::parse($document['fecha_de_emision'])->format('d/m/Y') }}</td>
  </tr>
  <tr>
    <td class="label">Cliente:</td>
    <td colspan="3">{{ $document['cliente_denominacion'] }}</td>
  </tr>
  @if($document['cliente_direccion'])
  <tr>
    <td class="label">Dirección:</td>
    <td colspan="3">{{ $document['cliente_direccion'] }}</td>
  </tr>
  @endif
  @if($document['fecha_de_vencimiento'])
  <tr>
    <td class="label">Fecha de Vencimiento:</td>
    <td>{{ \Carbon\Carbon::parse($document['fecha_de_vencimiento'])->format('d/m/Y') }}</td>
    <td class="label">Moneda:</td>
    <td>{{ $document['currency_symbol'] ?? '' }} - {{ $document['currency']['description'] ?? '' }}</td>
  </tr>
  @else
  <tr>
    <td class="label">Moneda:</td>
    <td colspan="3">{{ $document['currency_symbol'] ?? '' }} - {{ $document['currency']['description'] ?? '' }}</td>
  </tr>
  @endif
</table>

<!-- Documento que se modifica (para NC/ND) -->
@if(in_array($document['sunat_concept_document_type_id'], [31, 32]))
<table>
  <tr>
    <th colspan="4" class="section-title">DOCUMENTO QUE SE MODIFICA</th>
  </tr>
  <tr>
    <td class="label" style="width: 25%;">Tipo de Documento:</td>
    <td style="width: 25%;">{{ $document['documento_que_se_modifica_tipo'] == 1 ? 'FACTURA' : 'BOLETA' }}</td>
    <td class="label" style="width: 25%;">Número:</td>
    <td style="width: 25%;">{{ $document['documento_que_se_modifica_serie'] }}-{{ $document['documento_que_se_modifica_numero'] }}</td>
  </tr>
  @if($document['sunat_concept_document_type_id'] == 31 && $document['credit_note_type'])
  <tr>
    <td class="label">Motivo:</td>
    <td colspan="3">{{ $document['credit_note_type']['description'] ?? '' }}</td>
  </tr>
  @endif
  @if($document['sunat_concept_document_type_id'] == 32 && $document['debit_note_type'])
  <tr>
    <td class="label">Motivo:</td>
    <td colspan="3">{{ $document['debit_note_type']['description'] ?? '' }}</td>
  </tr>
  @endif
</table>
@endif

<!-- Items/Detalle -->
<table>
  <thead>
    <tr>
      <th style="width: 8%;">Código</th>
      <th style="width: 30%;">Descripción</th>
      <th style="width: 8%;">UM</th>
      <th style="width: 8%;">Cantidad</th>
      <th style="width: 12%;">Valor Unit.</th>
      <th style="width: 10%;">Descuento</th>
      <th style="width: 12%;">IGV</th>
      <th style="width: 12%;">Total</th>
    </tr>
  </thead>
  <tbody>
    @foreach($document['items_collection'] as $item)
    <tr>
      <td>{{ $item['codigo'] ?? '-' }}</td>
      <td>{{ $item['descripcion'] }}</td>
      <td class="text-center">{{ $item['unidad_de_medida'] }}</td>
      <td class="text-right">{{ number_format($item['cantidad'], 2) }}</td>
      <td class="text-right">{{ number_format($item['valor_unitario'], 2) }}</td>
      <td class="text-right">{{ number_format($item['descuento'] ?? 0, 2) }}</td>
      <td class="text-right">{{ number_format($item['igv'], 2) }}</td>
      <td class="text-right">{{ number_format($item['total'], 2) }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<!-- Totales -->
<table class="totals-table">
  @if($document['total_gravada'] > 0)
  <tr>
    <td class="label">Op. Gravada:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_gravada'], 2) }}</td>
  </tr>
  @endif
  @if($document['total_exonerada'] > 0)
  <tr>
    <td class="label">Op. Exonerada:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_exonerada'], 2) }}</td>
  </tr>
  @endif
  @if($document['total_inafecta'] > 0)
  <tr>
    <td class="label">Op. Inafecta:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_inafecta'], 2) }}</td>
  </tr>
  @endif
  @if($document['total_gratuita'] > 0)
  <tr>
    <td class="label">Op. Gratuita:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_gratuita'], 2) }}</td>
  </tr>
  @endif
  @if($document['total_descuento'] > 0)
  <tr>
    <td class="label">Descuentos:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_descuento'], 2) }}</td>
  </tr>
  @endif
  @if($document['total_anticipo'] > 0)
  <tr>
    <td class="label">Anticipos:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_anticipo'], 2) }}</td>
  </tr>
  @endif
  <tr>
    <td class="label">IGV ({{ $document['porcentaje_de_igv'] }}%):</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total_igv'], 2) }}</td>
  </tr>
  <tr class="total-row">
    <td class="label">TOTAL:</td>
    <td class="text-right">{{ $document['currency_symbol'] }} {{ number_format($document['total'], 2) }}</td>
  </tr>
</table>

<div style="clear: both;"></div>

<!-- Importe en letras -->
<table>
  <tr>
    <td class="label" style="width: 20%;">Importe en letras:</td>
    <td style="width: 80%;">{{ $document['total_en_letras'] ?? '' }} {{ $document['currency']['description'] ?? '' }}</td>
  </tr>
</table>

<!-- Observaciones -->
@if($document['observaciones'] || $document['condiciones_de_pago'])
<div class="observations">
  <strong>Observaciones:</strong><br>
  {{ $document['observaciones'] ?? '' }}
  @if($document['condiciones_de_pago'])
    <br><strong>Condiciones de Pago:</strong> {{ $document['condiciones_de_pago'] }}
  @endif
</div>
@endif

<!-- Información SUNAT -->
@if($document['cadena_para_codigo_qr'])
<div style="margin-top: 15px; text-align: center;">
  <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($document['cadena_para_codigo_qr']) }}" alt="QR Code" class="qr-code">
  <div style="font-size: 8px; margin-top: 5px;">
    @if($document['codigo_hash'])
    Hash: {{ $document['codigo_hash'] }}<br>
    @endif
    @if($document['enlace_del_pdf'])
    Puede verificar este documento en: <a href="{{ $document['enlace_del_pdf'] }}">{{ $document['enlace_del_pdf'] }}</a>
    @endif
  </div>
</div>
@endif

<!-- Footer -->
<div class="footer">
  <strong>Representación impresa de {{ $documentTypeName }}</strong><br>
  Este documento ha sido generado electrónicamente y tiene validez legal según la Ley N° 27269<br>
  <div style="margin-top: 10px;">
    <img src="{{ getBase64Image('images/ap/brands/suzuki.png') }}" alt="Suzuki" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/subaru.png') }}" alt="Subaru" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/dfsk.png') }}" alt="DFSK" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/mazda.png') }}" alt="Mazda" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/citroen.jpg') }}" alt="Citroën" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/renault.png') }}" alt="Renault" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/haval.png') }}" alt="Haval" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/great-wall.png') }}" alt="Great Wall" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/changan.png') }}" alt="Changan" style="height: 12px; margin: 0 5px;">
    <img src="{{ getBase64Image('images/ap/brands/jac.png') }}" alt="JAC" style="height: 12px; margin: 0 5px;">
  </div>
</div>

</body>
</html>
