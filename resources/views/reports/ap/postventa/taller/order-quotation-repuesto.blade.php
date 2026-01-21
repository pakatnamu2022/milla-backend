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

    .cards-section {
      margin-top: 15px;
      margin-bottom: 15px;
    }

    .section-title {
      font-weight: bold;
      font-size: 11px;
      margin-bottom: 10px;
      text-align: center;
      text-decoration: underline;
    }

    .cards-container {
      display: table;
      width: 100%;
      table-layout: fixed;
    }

    .card {
      display: table-cell;
      width: 25%;
      padding: 8px;
      border: 1px solid #000;
      vertical-align: top;
      font-size: 8px;
    }

    .card-header {
      font-weight: bold;
      font-size: 9px;
      margin-bottom: 5px;
      text-align: center;
      border-bottom: 1px solid #ccc;
      padding-bottom: 3px;
    }

    .card-content {
      line-height: 1.4;
    }

    .card-content div {
      margin-bottom: 3px;
    }

    .card-content-header {
      text-align: center;
      font-weight: bold
    }

    .card-label {
      font-weight: bold;
    }

    .signature-section {
      margin-top: 30px;
      margin-bottom: 20px;
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

<!-- Totales -->
<div class="totals-section">
  <table>
    <tr>
      <td class="label-total">Subtotal:</td>
      <td
        class="value-total">{{$quotation['type_currency']->symbol}} {{ number_format($quotation['subtotal'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">Total Dtos.:</td>
      <td
        class="value-total">{{$quotation['type_currency']->symbol}} {{ number_format($quotation['total_discounts'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">OP. Gravadas:</td>
      <td
        class="value-total">{{$quotation['type_currency']->symbol}} {{ number_format($quotation['op_gravada'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">IGV 18%:</td>
      <td
        class="value-total">{{$quotation['type_currency']->symbol}} {{ number_format($quotation['tax_amount'], 2) }}</td>
    </tr>
    <tr>
      <td class="label-total">Total:</td>
      <td
        class="value-total">{{$quotation['type_currency']->code}} {{ number_format($quotation['total_amount'], 2) }}</td>
    </tr>
  </table>
</div>

<!-- Sección de Firma del Cliente -->
@if(isset($quotation['customer_signature']) && $quotation['customer_signature'])
  <div class="signature-section" style="text-align: center;">
    <div class="signature-box">
      <img src="{{ $quotation['customer_signature'] }}" alt="Firma Cliente" class="signature-img">
      <div class="signature-line"></div>
      FIRMA DEL CLIENTE<br>
      {{ $quotation['customer_name'] }}
    </div>
  </div>
@endif

<!-- Sección IMPORTANTE -->
<div class="important-section">
  <div class="important-title">IMPORTANTE</div>
  <div class="important-content">
    <ol>
      <li>LOS PRECIOS MOSTRADOS SON EN SOLES E INCLUYEN IGV.</li>
      <li>AQUELLOS REPUESTOS QUE SEAN MATERIA DE IMPORTACIÓN, SERÁN ENTREGADOS EN PLAZO MÍNIMO DE 90 A 120 DÍAS
        NATURALES (SUJETO A STOCK DE FÁBRICA). EL CUAL SE EMPIEZA A COMPUTAR DESDE EL DÍA SIGUIENTE DE APROBADO Y
        ABONADO (100%) POR DEL CLIENTE.
      </li>
      <li>AQUELLOS REPUESTOS QUE SE ENCUENTREN EN STOCK Y SEA NECESARIO OBTENER DEL ALMACÉN LIMA, SERÁN ENTREGADOS EN
        PLAZO MÍNIMO DE 04 DÍAS NATURALES, EL CUAL SE EMPIEZA A COMPUTAR DESDE EL DÍA SIGUIENTE DE APROBADO Y PAGADO
        (50% O 100%) POR EL CLIENTE.
      </li>
      <li>
        STOCK DISPONIBLE DE REPUESTOS PUEDE VARIAR SEGÚN EL TIEMPO DE CONFIRMACIÓN DE COMPRA DE LOS MISMOS.
      </li>
      <li>UNA VEZ APROBADO Y GENERADO EL PEDIDO, NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES.</li>
      <li>EL CLIENTE FIRMA EN SEÑAL DE CONFORMIDAD CON LO COTIZADO Y ASUME LA ACEPTACIÓN DE LAS OBSERVACIONES ACERCA DE
        DISPONIBILIDAD Y PENALIDADES. SE PRECISA QUE EN EL SIGUIENTE CASO QUE EL RECIBA EL REPUESTO SOLICITADO EN
        ALMACENES DE API (STOCK) Y EN ALMACENES LIMA, E INCURRA EN ALGUNA DEVOLUCIÓN QUE PUEDA GENERAR TRÁMITES
        ADMINISTRATIVOS ADICIONALES, EL CLIENTE FIRMANTE ACEPTA LA CANCELACIÓN DE MÍNIMO S/25.00 O EL 15% DEL VALOR
        TOTAL.
      </li>
      <li>
        COTIZACIÓN VÁLIDA PARA 04 DÍAS NATURALES.
      </li>
    </ol>
  </div>
</div>

<!-- Sección CUENTA AP -->
<div class="cards-section">
  <div class="section-title">CUENTAS AP</div>
  <div class="cards-container">
    <div class="card">
      <div class="card-header">CHICLAYO</div>
      <div class="card-content">
        <div class="card-content-header">N° CUENTA BCO. BCP:</div>
        <div><span class="card-label">SOLES:</span> 305-2041106-0-39</div>
        <div><span class="card-label">CCI:</span> 002-305-002041106039-13</div>
        <div><span class="card-label">DÓLARES:</span> 305-2032097-1-49</div>
        <div><span class="card-label">CCI:</span> 002-305-002032097149-10</div>
        <div class="card-content-header">N° CUENTA BCO. BBVA:</div>
        <div><span class="card-label">SOLES:</span> 0011-0279-0100020589</div>
        <div><span class="card-label">CCI:</span> 011279000100020589­76</div>
        <div><span class="card-label">DÓLARES:</span> 0011-0279-0100020597</div>
        <div><span class="card-label">CCI:</span> 011279000100020597­79</div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">PIURA</div>
      <div class="card-content">
        <div class="card-content-header">N° CUENTA BCO. BCP:</div>
        <div><span class="card-label">SOLES:</span> 475-2660047-0-39</div>
        <div><span class="card-label">CCI:</span> 002-475-002660047039-22</div>
        <div><span class="card-label">DÓLARES:</span> 475-2573597-1-16</div>
        <div><span class="card-label">CCI:</span> 002-475-002573597116-27</div>
        <div class="card-content-header">N° CUENTA BCO. BBVA:</div>
        <div><span class="card-label">SOLES:</span> 0011-0267-0100130672</div>
        <div><span class="card-label">CCI:</span> 011267000100130672­27</div>
        <div><span class="card-label">DÓLARES:</span> 0011-0267-0100130680</div>
        <div><span class="card-label">CCI:</span> 011267000100130680­20</div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">CAJAMARCA</div>
      <div class="card-content">
        <div class="card-content-header">N° CUENTA BCO. BCP:</div>
        <div><span class="card-label">SOLES:</span> 245-2661107-0-14</div>
        <div><span class="card-label">CCI:</span> 002-245-002661107014-90</div>
        <div><span class="card-label">DÓLARES:</span> 245-2663485-1-44</div>
        <div><span class="card-label">CCI:</span> 002-245-002663485144-93</div>
        <div class="card-content-header">N° CUENTA BCO. BBVA:</div>
        <div><span class="card-label">SOLES:</span> 0011-0277-0100080793</div>
        <div><span class="card-label">CCI:</span> 011277000100080793­11</div>
        <div><span class="card-label">DÓLARES:</span> 0011-0277-0100080807</div>
        <div><span class="card-label">CCI:</span> 011277 000100080807­19</div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">JAÉN</div>
      <div class="card-content">
        <div class="card-content-header">N° CUENTA BCO. BCP:</div>
        <div><span class="card-label">SOLES:</span> 395-5394658-0-80</div>
        <div><span class="card-label">CCI:</span> 002-395-005394558080-20</div>
        <div><span class="card-label">DÓLARES:</span> 395-2415578-1-84</div>
        <div><span class="card-label">CCI:</span> 002-395-002415578184-22</div>
        <div class="card-content-header">N° CUENTA BCO. BBVA:</div>
        <div><span class="card-label">SOLES:</span> 0011-0409-0100005801</div>
        <div><span class="card-label">CCI:</span> 011409000100005801­04</div>
        <div><span class="card-label">DÓLARES:</span> 0011-0409-0100005828</div>
        <div><span class="card-label">CCI:</span> 011409000100005828­07</div>
      </div>
    </div>
  </div>
</div>

<!-- Sección UBÍCANOS EN -->
<div class="cards-section">
  <div class="section-title">UBÍCANOS EN</div>
  <div class="cards-container">
    <div class="card">
      <div class="card-header">CHICLAYO</div>
      <div class="card-content">
        <div><span class="card-label">Dirección:</span></div>
        <div>CAR. PANAMERICANA NORTE #1006 - CHICLAYO - LAMBAYEQUE (COSTADO DEL COLEGIO SANTO TORIBIO DE MOGROVEJO,
          CRUCE CON AV. LEGUÍA)
        </div>
        <div><span class="card-label">CITAS TALLER:</span> 944 296 593</div>
        <div><span class="card-label">REPUESTOS:</span> 943 856 726</div>
        <div><span class="card-label">Horario:</span></div>
        <div>LUNES A VIERNES: 8:00 AM A 6:00 PM
          SÁBADOS: 8:00 AM A 6:00 PM
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">PIURA</div>
      <div class="card-content">
        <div><span class="card-label">Dirección:</span></div>
        <div>AV. SÁNCHEZ CERRO MZA. 248 LOTE. 2 DPTO. B Z.I. INDUSTRIAL I – PIURA (COSTADO DE LA FERRETERÍA "MARTÍN")
        </div>
        <div><span class="card-label">CITAS TALLER:</span> 932 049 710</div>
        <div><span class="card-label">REPUESTOS:</span> 950 122 002</div>
        <div><span class="card-label">Horario:</span></div>
        <div>LUNES A VIERNES: 8:00 AM A 6:00 PM
          SÁBADOS: 8:00 AM A 6:00 PM
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">CAJAMARCA</div>
      <div class="card-content">
        <div><span class="card-label">Dirección:</span></div>
        <div>MZA. B LOTE. 19 OTR. EL BOSQUE III ETAPA – CAJAMARCA (FRENTE A LA EX UGEL)
        </div>
        <div><span class="card-label">CITAS TALLER:</span> 950 118 892</div>
        <div><span class="card-label">REPUESTOS:</span> 950 118 181</div>
        <div><span class="card-label">Horario:</span></div>
        <div>LUNES A VIERNES: 8:00 AM A 6:00 PM
          SÁBADOS: 8:00 AM A 6:00 PM
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">JAÉN</div>
      <div class="card-content">
        <div><span class="card-label">Dirección:</span></div>
        <div>AV. PAKAMUROS #2485 INT. B (CARRETERA SAN IGNACIO - LINDEROS) CAJAMARCA – JAÉN</div>
        <div><span class="card-label">CITAS TALLER:</span> 944 296 503</div>
        <div><span class="card-label">REPUESTOS:</span> 982 940 771</div>
        <div><span class="card-label">Horario:</span></div>
        <div>LUNES A VIERNES: 8:00 AM A 6:00 PM
          SÁBADOS: 8:00 AM A 6:00 PM
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
