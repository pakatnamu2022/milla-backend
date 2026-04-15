@php
  function getBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType  = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }
@endphp
  <!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>{{ $quote['document_title'] ?? 'Solicitud de Compra' }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      color: #22293a;
      background: #fff;
      padding: 0 0 140px;
    }

    /* ─── WATERMARK ──────────────────────────────── */
    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 120px;
      color: rgba(200, 200, 200, 0.12);
      font-weight: bold;
      z-index: -1;
      white-space: nowrap;
    }

    /* ─── HEADER ─────────────────────────────────── */
    .page-header {
      padding: 5px 10px 5px;
      margin-bottom: 6px;
    }

    .header-inner {
      display: table;
      width: 100%;
      border-radius: 6px;
      overflow: hidden;
    }

    .h-logo {
      display: table-cell;
      width: 140px;
      text-align: center;
      vertical-align: middle;
      padding: 4px 8px;
    }

    .h-logo img {
      max-height: 28px;
      width: auto;
      display: block;
      margin: 0 auto;
    }

    .h-title {
      display: table-cell;
      vertical-align: middle;
      text-align: center;
      padding: 4px 10px;
    }

    .h-title-main {
      font-size: 12px;
      font-weight: bold;
      color: #22293a;
      letter-spacing: 0.3px;
      line-height: 1.3;
    }

    .h-title-sub {
      font-size: 8px;
      color: #888888;
      margin-top: 1px;
    }

    .h-meta {
      display: table-cell;
      width: 130px;
      vertical-align: middle;
      background: #e0e0e0;
      text-align: center;
      padding: 4px 8px;
      border-radius: 6px;
    }

    .h-meta-lbl {
      font-size: 7px;
      font-weight: bold;
      letter-spacing: 0.6px;
      color: #22293a;
      text-transform: uppercase;
    }

    .h-meta-val {
      font-size: 15px;
      font-weight: bold;
      color: #22293a;
      white-space: nowrap;
      margin-top: 1px;
    }

    .h-meta-date {
      font-size: 7px;
      color: #555555;
      margin-top: 2px;
    }

    /* Content wrapper */
    .content {
      padding: 0 10px;
    }

    /* ─── CARD ───────────────────────────────────── */
    .card {
      border: 1px solid #d2d2d2;
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 6px;
    }

    .card-title {
      background-color: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      padding: 3px 10px;
      letter-spacing: 0.3px;
    }

    /* ─── DATA TABLE ─────────────────────────────── */
    table.dt {
      width: 100%;
      border-collapse: collapse;
    }

    table.dt td {
      padding: 3px 8px;
      border-bottom: 1px solid #ebebeb;
      border-right: 1px solid #ebebeb;
      font-size: 11px;
      vertical-align: top;
    }

    table.dt td:last-child {
      border-right: none;
    }

    table.dt tr:last-child td {
      border-bottom: none;
    }

    .lbl {
      font-weight: bold;
      color: #22293a;
      background: #f5f5f5;
      white-space: nowrap;
    }

    /* ─── PRICES TABLE ───────────────────────────── */
    table.pt {
      width: 100%;
      border-collapse: collapse;
    }

    table.pt td {
      padding: 3px 8px;
      border-bottom: 1px solid #ebebeb;
      border-right: 1px solid #ebebeb;
      font-size: 11px;
      vertical-align: middle;
    }

    table.pt td:last-child {
      border-right: none;
    }

    table.pt tr:last-child td {
      border-bottom: none;
    }

    .pt-section {
      background: #e0e0e0;
      color: #fff;
      font-weight: bold;
      font-size: 9px;
      writing-mode: vertical-lr;
      text-align: center;
      width: 26px;
      padding: 8px 4px;
    }

    .pt-sub {
      background: #f5f5f5;
      color: #22293a;
      font-weight: bold;
      font-size: 10.5px;
      padding: 5px 10px;
      text-align: center;
      border-bottom: 1px solid #ebebeb;
    }

    /* ─── CHECKBOX ───────────────────────────────── */
    .chk {
      display: inline-block;
      width: 13px;
      height: 13px;
      border: 1.5px solid #aaaaaa;
      border-radius: 2px;
      vertical-align: middle;
      margin-left: 4px;
      background: #fff;
    }

    .chk.on {
      background: #e0e0e0;
      border-color: #e0e0e0;
    }

    /* ─── SIGNATURES ─────────────────────────────── */
    .sig-wrap {
      border: 1px solid #d2d2d2;
      border-radius: 5px;
      overflow: hidden;
      display: table;
      width: 100%;
      margin-bottom: 6px;
    }

    .sig-col {
      display: table-cell;
      width: 50%;
      vertical-align: top;
      border-right: 1px solid #d2d2d2;
    }

    .sig-col:last-child {
      border-right: none;
    }

    .sig-hdr {
      background: #e0e0e0;
      color: #000000;
      font-weight: bold;
      font-size: 10px;
      text-align: center;
      padding: 3px 8px;
    }

    .sig-body {
      padding: 6px 12px;
    }

    .sig-line {
      height: 63px;
    }

    .sig-sub {
      font-size: 9px;
      color: #777777;
      margin-top: 3px;
      text-align: center;
    }

    /* ─── NOTES ──────────────────────────────────── */
    .notes {
      position: fixed;
      bottom: 22px;
      left: 10px;
      right: 10px;
      border: 1px solid #d2d2d2;
      border-radius: 5px;
      padding: 5px 10px;
      font-size: 8px;
      color: #4a5568;
      background: #fafafa;
    }

    .notes ol {
      margin-left: 14px;
      line-height: 1.5;
    }

    /* ─── FOOTER BRANDS ──────────────────────────── */
    .foot {
      position: fixed;
      bottom: 0;
      left: 10px;
      right: 10px;
      border-top: 1px solid #d2d2d2;
      padding: 4px 0 2px;
      text-align: center;
      background: #fff;
    }

    .foot img {
      height: 11px;
      width: auto;
      margin: 0 4px;
    }

    /* ─── BANKS TABLE ────────────────────────────── */
    table.bt {
      width: 100%;
      border-collapse: collapse;
    }

    table.bt th {
      background: #f5f5f5;
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      padding: 4px 8px;
      text-align: center;
      border-right: 1px solid #d2d2d2;
      border-bottom: 1px solid #d2d2d2;
    }

    table.bt th:last-child {
      border-right: none;
    }

    table.bt td {
      padding: 3px 8px;
      font-size: 10px;
      border-bottom: 1px solid #ebebeb;
      border-right: 1px solid #ebebeb;
    }

    table.bt td:last-child {
      border-right: none;
    }

    table.bt tr:last-child td {
      border-bottom: none;
    }
  </style>
</head>
<body>

<div class="watermark">PAKATNAMU</div>

{{-- ── ENCABEZADO ──────────────────────────────── --}}
<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="AP Logo">
    </div>
    <div class="h-title">
      <div class="h-title-main">{{ strtoupper($quote['document_title'] ?? 'SOLICITUD DE COMPRA') }}</div>
      <div class="h-title-sub">Solicitud formal de adquisición de vehículo</div>
    </div>
    <div class="h-meta">
      <div class="h-meta-lbl">N° DOCUMENTO</div>
      <div class="h-meta-val">{{ $quote['correlative'] }}</div>
      <div class="h-meta-date">{{ \Carbon\Carbon::parse($quote['created_at'])->format('d/m/Y') }}</div>
    </div>
  </div>
</div>

<div class="content">

  {{-- ── DATOS DEL COMPRADOR ──────────────────────── --}}
  <div class="card">
    <div class="card-title">DATOS DEL COMPRADOR</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:16%;">Comprador</td>
        <td colspan="3">{{ $quote['client_name'] ?? '' }}</td>
        <td class="lbl" style="width:10%;">Fecha</td>
        <td style="width:14%;">{{ \Carbon\Carbon::parse($quote['created_at'])->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <td class="lbl">Vendedor</td>
        <td colspan="5">{{ $quote['advisor_name'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="lbl">DNI / RUC</td>
        <td style="width:18%;">{{ $quote['num_doc_client'] ?? '' }}</td>
        <td class="lbl" style="width:12%;">Fecha Nac.</td>
        <td>{{ $quote['birth_date'] ?? '' }}</td>
        <td class="lbl">Estado Civil</td>
        <td>{{ $quote['marital_status'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="lbl">Cónyuge / Copropietario</td>
        <td colspan="3">{{ $quote['spouse_full_name'] ?? '' }}</td>
        <td class="lbl">DNI Cónyuge</td>
        <td>{{ $quote['spouse_num_doc'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="lbl">Dirección</td>
        <td colspan="5">{{ $quote['address'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="lbl">Email</td>
        <td colspan="3">{{ $quote['email'] ?? '' }}</td>
        <td class="lbl">Teléfono</td>
        <td>{{ $quote['phone'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="lbl">Tarjeta de propiedad</td>
        <td colspan="5">
          @php
            $numDoc    = $quote['num_doc_client'] ?? '';
            $docLength = strlen($numDoc);
            $docType   = 'natural';
            if ($docLength == 11) {
              $firstTwo = substr($numDoc, 0, 2);
              if ($firstTwo == '10')     $docType = 'natural_ruc';
              elseif ($firstTwo == '20') $docType = 'juridica';
            }
          @endphp
          Persona Natural <span class="chk {{ $docType == 'natural' ? 'on' : '' }}"></span>
          &nbsp;&nbsp;
          P. Natural con RUC <span class="chk {{ $docType == 'natural_ruc' ? 'on' : '' }}"></span>
          &nbsp;&nbsp;
          Persona Jurídica <span class="chk {{ $docType == 'juridica' ? 'on' : '' }}"></span>
        </td>
      </tr>
      <tr>
        <td class="lbl">Razón Social</td>
        <td colspan="3">{{ $quote['holder'] ?? '' }}</td>
        <td class="lbl">RUC</td>
        <td>{{ $docType !== 'natural' ? ($quote['num_doc_client'] ?? '') : '' }}</td>
      </tr>
      <tr>
        <td class="lbl">Repres. Legal</td>
        <td colspan="3">{{ $quote['legal_representative'] ?? '' }}</td>
        <td class="lbl">DNI</td>
        <td>{{ $quote['dni_legal_representative'] ?? '' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── DATOS DEL VEHÍCULO ──────────────────────── --}}
  <div class="card">
    <div class="card-title">DATOS DEL VEHÍCULO</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:10%;">Marca</td>
        <td style="width:18%;">{{ $quote['brand'] ?? '' }}</td>
        <td class="lbl" style="width:10%;">Modelo</td>
        <td>{{ $quote['ap_model_vn'] ?? '' }}</td>
        <td class="lbl" style="width:8%;">Clase</td>
        <td>{{ $quote['class'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="lbl">Color</td>
        <td>{{ $quote['vehicle_color'] ?? '' }}</td>
        <td class="lbl">Fab. / Año</td>
        <td>{{ $quote['model_year'] ?? '' }}</td>
        <td class="lbl">Garantía</td>
        <td>
          @if(!empty($quote['warranty_years']) || !empty($quote['warranty_km']))
            {{ $quote['warranty_years'] ?? '-' }} años / {{ number_format($quote['warranty_km'] ?? 0) }} km
          @endif
        </td>
      </tr>
      <tr>
        <td class="lbl">VIN / Chasis</td>
        <td>{{ $quote['vin'] ?? '' }}</td>
        <td class="lbl">N° Motor</td>
        <td>{{ $quote['engine_number'] ?? '' }}</td>
        <td class="lbl">Tipo uso</td>
        <td></td>
      </tr>
    </table>
  </div>

  {{-- ── VALOR DE LA COMPRA + FORMA DE PAGO ─────── --}}
  @php
    $addedAccessories = collect($quote['accessories'] ?? [])->filter(fn($i) => $i['type'] === 'ACCESORIO_ADICIONAL')->values();
    $gifts            = collect($quote['accessories'] ?? [])->filter(fn($i) => $i['type'] === 'OBSEQUIO')->values();
  @endphp
  <div class="card">
    <div class="card-title">VALOR DE LA COMPRA Y FORMA DE PAGO</div>
    <table class="pt">
      {{-- Precio base --}}
      <tr>
        <td class="lbl" style="width:22%;">Precio de venta</td>
        <td style="width:26%;">
          {{ $quote['type_currency_symbol'] }} {{ number_format($quote['base_selling_price'], 2) }}
        </td>
        <td style="width:4%; text-align:center; color:#000000; font-weight:bold;">1</td>
        <td class="lbl" style="width:16%;">A cuenta</td>
        <td>{{ $quote['type_currency_symbol'] }} {{ !empty($quote['down_payment']) ? number_format($quote['down_payment'], 2) : '' }}</td>
        <td style="width:4%; text-align:center; color:#000000; font-weight:bold;">4</td>
      </tr>
      {{-- Accesorios adicionales --}}
      @if($addedAccessories->count() > 0)
        @foreach($addedAccessories as $idx => $acc)
          <tr>
            <td class="lbl">{{ $idx === 0 ? 'Equipamiento adicional' : '' }}</td>
            <td>{{ $acc['description'] ?? '' }} (x{{ $acc['quantity'] ?? 1 }})
              {{ $quote['type_currency_symbol'] }} {{ number_format(($acc['total'] ?? 0) / ($quote['exchange_rate'] ?? 1), 2) }}</td>
            <td></td>
            @if($idx == 0)
              <td class="lbl">Nº de OP.</td>
              <td colspan="2"></td>
            @elseif($idx == 1)
              <td class="lbl">Banco</td>
              <td colspan="2"></td>
            @elseif($idx == 2)
              <td class="lbl">Saldo (3-4)</td>
              <td>{{ $quote['type_currency_symbol'] }}</td>
              <td style="text-align:center;color:#000000;font-weight:bold;">5</td>
            @elseif($idx == 3)
              <td class="lbl">Forma de pago</td>
              <td colspan="2"></td>
            @else
              <td colspan="3"></td>
            @endif
          </tr>
        @endforeach
        @for($i = 0; $i < max(0, 4 - $addedAccessories->count()); $i++)
          @php $ri = $addedAccessories->count() + $i; @endphp
          <tr>
            <td></td>
            <td>{{ $quote['type_currency_symbol'] }}</td>
            <td></td>
            @if($ri == 1)
              <td class="lbl">Banco</td>
              <td colspan="2"></td>
            @elseif($ri == 2)
              <td class="lbl">Saldo (3-4)</td>
              <td>{{ $quote['type_currency_symbol'] }}</td>
              <td style="text-align:center;color:#000000;font-weight:bold;">5</td>
            @elseif($ri == 3)
              <td class="lbl">Forma de pago</td>
              <td colspan="2"></td>
            @else
              <td colspan="3"></td>
            @endif
          </tr>
        @endfor
      @else
        <tr>
          <td class="lbl">Equipamiento adicional</td>
          <td>{{ $quote['type_currency_symbol'] }}</td>
          <td></td>
          <td class="lbl">Nº de OP.</td>
          <td colspan="2"></td>
        </tr>
        @for($i = 0; $i < 4; $i++)
          <tr>
            <td></td>
            <td>{{ $quote['type_currency_symbol'] }}</td>
            <td></td>
            @if($i == 0)
              <td class="lbl">Banco</td>
              <td colspan="2"></td>
            @elseif($i == 1)
              <td class="lbl">Saldo (3-4)</td>
              <td>{{ $quote['type_currency_symbol'] }}</td>
              <td style="text-align:center;color:#000000;font-weight:bold;">5</td>
            @elseif($i == 2)
              <td class="lbl">Forma de pago</td>
              <td colspan="2"></td>
            @else
              <td colspan="3"></td>
            @endif
          </tr>
        @endfor
      @endif
      {{-- Totales --}}
      <tr>
        <td class="lbl">Total equipamiento</td>
        <td>{{ $quote['type_currency_symbol'] }}</td>
        <td style="text-align:center;color:#000000;font-weight:bold;">2</td>
        <td class="lbl">Banco</td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td class="lbl">Precio total (1+2)</td>
        <td
          style="font-weight:bold;">{{ $quote['type_currency_symbol'] }} {{ number_format($quote['doc_sale_price'], 2) }}</td>
        <td style="text-align:center;color:#000000;font-weight:bold;">3</td>
        <td class="lbl">Sectorista</td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td class="lbl">T.C. referencial S/</td>
        <td>S/ {{ number_format($quote['selling_price_soles'], 2) }}</td>
        <td></td>
        <td class="lbl">Oficina</td>
        <td colspan="2"></td>
      </tr>
      {{-- Obsequios --}}
      <tr>
        <td colspan="6"
            style="background:#f5f5f5; font-weight:bold; color:#22293a; font-size:10.5px; padding:5px 10px;">
          OBSEQUIOS / CORTESÍA
        </td>
      </tr>
      @if($gifts->count() > 0)
        @foreach($gifts as $gift)
          <tr>
            <td colspan="6">• {{ $gift['description'] ?? '' }} (x{{ $gift['quantity'] ?? 1 }})</td>
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="6">• {{ $quote['comment'] ?? 'TARJETA Y PLACA' }}</td>
        </tr>
      @endif
    </table>
  </div>

  {{-- ── FIRMAS ───────────────────────────────────── --}}
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig-hdr">APROBADO</div>
      <div class="sig-body">
        <div class="sig-line"></div>
        <div class="sig-sub">Automotores Pakatnamu S.A.C.</div>
      </div>
    </div>
    <div class="sig-col">
      <div class="sig-hdr">FIRMA DEL COMPRADOR</div>
      <div class="sig-body">
        <div class="sig-line"></div>
        <div class="sig-sub">{{ $quote['client_name'] ?? '' }} &nbsp;·&nbsp; Huella digital</div>
      </div>
    </div>
  </div>

  {{-- ── NOTAS IMPORTANTES ────────────────────────── --}}
  <div class="notes">
    <strong style="color:#000000;">IMPORTANTE:</strong>
    <ol>
      <li>Cualquier Devolución estará afecta a la penalidad del 7%.</li>
      <li>Esta solicitud está sujeta a la aprobación de Automotores Pakatnamu SAC.</li>
      <li>Cualquier pedido de equipamiento adicional a las características de la presente solicitud será por cuenta y
        costo del cliente.
      </li>
      <li>El trámite de placas de rodaje y tarjeta de propiedad es una cortesía. Dicho trámite está sujeto a los
        criterios de calificación autónomos de cada registrador; nuestra empresa no se hace responsable por demoras
        ocasionadas por SUNARP.
      </li>
      <li>El solicitante acepta formalmente todas las características del vehículo descrito en el presente documento.
      </li>
      <li>El cliente declara conocer que, en caso el vehículo no se encuentre en stock, libera a la empresa de cualquier
        responsabilidad relacionada a los plazos de entrega.
      </li>
      <li>Manifiesto que los datos consignados son exactos y se ajustan fielmente a la realidad.</li>
    </ol>
  </div>

  {{-- ── FOOTER MARCAS ────────────────────────────── --}}
  <div class="foot">
    <img src="{{ getBase64Image('images/ap/brands/suzuki.png') }}" alt="Suzuki">
    <img src="{{ getBase64Image('images/ap/brands/subaru.png') }}" alt="Subaru">
    <img src="{{ getBase64Image('images/ap/brands/dfsk.png') }}" alt="DFSK">
    <img src="{{ getBase64Image('images/ap/brands/mazda.png') }}" alt="Mazda">
    <img src="{{ getBase64Image('images/ap/brands/citroen.jpg') }}" alt="Citroën">
    <img src="{{ getBase64Image('images/ap/brands/renault.png') }}" alt="Renault">
    <img src="{{ getBase64Image('images/ap/brands/haval.png') }}" alt="Haval">
    <img src="{{ getBase64Image('images/ap/brands/great-wall.png') }}" alt="Great Wall">
    <img src="{{ getBase64Image('images/ap/brands/changan.png') }}" alt="Changan">
    <img src="{{ getBase64Image('images/ap/brands/jac.png') }}" alt="JAC">
  </div>

</div>{{-- /content --}}

{{-- ── PÁGINA 2: CUENTAS BANCARIAS (opcional) ─── --}}
@if(isset($banks) && $banks->count() > 0)
  <div style="page-break-before: always;"></div>
  <div class="watermark">PAKATNAMU</div>

  <div class="page-header">
    <div class="header-inner">
      <div class="h-logo">
        <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="AP Logo">
      </div>
      <div class="h-title">
        <div class="h-title-main">CUENTAS BANCARIAS PARA DEPÓSITOS</div>
        <div class="h-title-sub">Datos para transferencia o depósito bancario</div>
      </div>
      <div class="h-meta">
        <div class="h-meta-lbl">N° DOCUMENTO</div>
        <div class="h-meta-val">{{ $quote['correlative'] }}</div>
        <div class="h-meta-date">{{ \Carbon\Carbon::parse($quote['created_at'])->format('d/m/Y') }}</div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="card">
      <div class="card-title">CUENTAS DISPONIBLES</div>
      <table class="bt">
        <thead>
        <tr>
          <th style="text-align:left;">Banco</th>
          <th>Número de Cuenta</th>
          <th>Moneda</th>
        </tr>
        </thead>
        <tbody>
        @foreach($banks as $bank)
          <tr>
            <td>{{ $bank->bank->description ?? '-' }}</td>
            <td style="text-align:center; font-family:'Courier New',monospace; font-weight:bold;">
              {{ $bank->account_number }}
            </td>
            <td style="text-align:center;">{{ $bank->currency->name ?? '-' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="border:1px solid #d2d2d2; border-left:3px solid #e0e0e0; border-radius:5px;
              padding:9px 13px; font-size:10px; color:#000; background:#fafafa;">
      <strong style="color:#000;">Importante:</strong> Realizar depósitos únicamente en las cuentas indicadas.
      Una vez realizado el depósito, enviar el comprobante de pago para procesar su solicitud.
    </div>
  </div>
@endif

</body>
</html>
