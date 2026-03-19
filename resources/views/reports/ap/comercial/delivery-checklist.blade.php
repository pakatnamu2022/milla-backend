@php
  function base64Img($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
  }

  $vehicle   = $vehicle ?? null;
  $delivery  = $delivery ?? null;
  $checklist = $checklist ?? null;

  $modelCode    = optional($vehicle->model)->code ?? '';
  $modelVersion = optional($vehicle->model)->version ?? '';
  $modelName    = trim($modelCode . ' ' . $modelVersion) ?: 'N/A';
  $color        = optional($vehicle->color)->description ?? 'N/A';
  $year         = $vehicle->year ?? 'N/A';
  $vin          = $vehicle->vin ?? 'N/A';
  $engineNum    = $vehicle->engine_number ?? 'N/A';

  $clientName   = optional($delivery->client)->full_name ?? 'N/A';
  $clientDoc    = optional($delivery->client)->num_doc ?? '';
  $advisorName  = optional($delivery->advisor)->nombre_completo ?? 'N/A';
  $sedeName     = optional($delivery->sede)->abreviatura ?? 'N/A';
  $deliveryDate = $delivery->scheduled_delivery_date
    ? \Carbon\Carbon::parse($delivery->scheduled_delivery_date)->format('d/m/Y')
    : 'N/A';

  $items          = $checklist->items ?? collect();
  $receptionItems = $items->where('source', 'reception');
  $poItems        = $items->where('source', 'purchase_order');
  $manualItems    = $items->where('source', 'manual');
@endphp
  <!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Checklist de Entrega de Vehículo</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #22293a;
      background: #fff;
      padding: 0 0 300px;
    }

    /* ─── HEADER ─────────────────────────────────── */
    .page-header {
      padding: 8px 10px 8px;
      margin-bottom: 8px;
    }

    .header-inner {
      display: table;
      width: 100%;
    }

    .h-logo {
      display: table-cell;
      width: 190px;
      vertical-align: middle;
      padding-right: 14px;
    }

    .h-logo img {
      max-width: 180px;
      height: auto;
      display: block;
    }

    .h-right {
      display: table-cell;
      vertical-align: middle;
    }

    .h-box {
      display: table;
      width: 100%;
      border: 1.5px solid #e0e0e0;
      border-radius: 5px;
      overflow: hidden;
    }

    .h-box-title {
      display: table-cell;
      vertical-align: middle;
      padding: 6px 14px;
      background: #fff;
    }

    .h-box-title-main {
      font-size: 13px;
      font-weight: bold;
      color: #22293a;
      letter-spacing: 0.3px;
    }

    .h-box-title-sub {
      font-size: 9px;
      color: #777777;
      margin-top: 2px;
    }

    .h-box-num {
      display: table-cell;
      vertical-align: middle;
      width: 145px;
      background: #e0e0e0;
      color: #22293a;
      text-align: center;
      padding: 6px 14px;
    }

    .h-box-num-lbl {
      font-size: 8px;
      font-weight: bold;
      letter-spacing: 0.6px;
    }

    .h-box-num-val {
      font-size: 18px;
      font-weight: bold;
      white-space: nowrap;
      margin-top: 4px;
    }

    /* Content wrapper */
    .content {
      padding: 0 22px;
    }

    /* ─── CARD ───────────────────────────────────── */
    .card {
      border: 1px solid #d2d2d2;
      border-radius: 7px;
      overflow: hidden;
      margin-bottom: 11px;
    }

    .card-title {
      background-color: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10.5px;
      padding: 4px 12px;
      letter-spacing: 0.3px;
    }

    /* ─── DATA TABLE ─────────────────────────────── */
    table.dt {
      width: 100%;
      border-collapse: collapse;
    }

    table.dt td {
      padding: 5px 10px;
      border-bottom: 1px solid #ebebeb;
      font-size: 11px;
      vertical-align: top;
    }

    table.dt tr:last-child td {
      border-bottom: none;
    }

    .lbl {
      font-weight: bold;
      color: #22293a;
      background: #f5f5f5;
      white-space: nowrap;
      width: 15%;
    }

    /* ─── CHECKLIST TABLE ────────────────────────── */
    table.cl {
      width: 100%;
      border-collapse: collapse;
    }

    table.cl th {
      background-color: #f5f5f5;
      color: #22293a;
      font-size: 10.5px;
      font-weight: bold;
      padding: 6px 8px;
      text-align: center;
      border-right: 1px solid #d2d2d2;
      border-bottom: 1px solid #d2d2d2;
    }

    table.cl th:last-child {
      border-right: none;
    }

    table.cl td {
      padding: 5px 8px;
      border-bottom: 1px solid #ebebeb;
      border-right: 1px solid #ebebeb;
      font-size: 11px;
      vertical-align: middle;
    }

    table.cl td:last-child {
      border-right: none;
    }

    table.cl tr:last-child td {
      border-bottom: none;
    }

    table.cl tr:nth-child(even) td {
      background: #f9f9f9;
    }

    table.cl tr:nth-child(odd) td {
      background: #fff;
    }

    .col-num {
      width: 28px;
      text-align: center;
    }

    .col-qty {
      width: 52px;
      text-align: center;
    }

    .col-unit {
      width: 50px;
      text-align: center;
    }

    .col-check {
      width: 60px;
      text-align: center;
    }

    .col-obs {
      width: 120px;
    }

    /* Checkbox */
    .chk {
      display: inline-block;
      padding: 2px 6px;
      border: 1.5px solid #aaaaaa;
      border-radius: 4px;
      vertical-align: middle;
      text-align: center;
      font-size: 9px;
      font-weight: bold;
      color: #aaaaaa;
      background: #fff;
    }

    .chk.on {
      background: #e0e0e0;
      border-color: #e0e0e0;
      color: #000000;
    }

    /* ─── DECLARATION ────────────────────────────── */
    .decl {
      border: 1px solid #d2d2d2;
      border-left: 3px solid #e0e0e0;
      border-radius: 5px;
      padding: 9px 13px;
      font-size: 10px;
      color: #000000;
      margin-bottom: 11px;
      background: #fafafa;
    }

    /* ─── FIRMAS fijas ───────────────────────────── */
    .sig-fixed {
      position: fixed;
      bottom: 44px;
      left: 22px;
      right: 22px;
    }

    /* padding-bottom del body compensa la altura de firmas+footer */
    .sig-wrap {
      border: 1px solid #d2d2d2;
      border-radius: 6px;
      overflow: hidden;
      margin-bottom: 4px;
      display: table;
      width: 100%;
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
      font-size: 10.5px;
      text-align: center;
      padding: 5px 8px;
    }

    .sig-body {
      padding: 10px 14px;
    }

    .sig-line {
      height: 90px;
    }

    .sig-name {
      font-size: 10px;
      font-weight: bold;
    }

    .sig-sub {
      font-size: 9px;
      color: #777777;
      margin-top: 1px;
    }

    .sig-foot {
      text-align: center;
      font-size: 8px;
      color: #aaaaaa;
    }

    /* ─── FOOTER marcas ──────────────────────────── */
    .foot-fixed {
      position: fixed;
      bottom: 0;
      left: 22px;
      right: 22px;
      border-top: 1px solid #d2d2d2;
      padding: 5px 0 3px;
      text-align: center;
      background: #fff;
    }

    .foot-fixed img {
      height: 13px;
      width: auto;
      margin: 0 5px;
    }
  </style>
</head>
<body>

{{-- ── ENCABEZADO ──────────────────────────────── --}}
<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      <img src="{{ base64Img('images/ap/logo-ap.png') }}" alt="AP Logo">
    </div>
    <div class="h-right">
      <div class="h-box">
        <div class="h-box-title">
          <div class="h-box-title-main">CHECKLIST DE ENTREGA DE VEHÍCULO</div>
          <div class="h-box-title-sub">Conformidad de entrega al cliente</div>
        </div>
        <div class="h-box-num">
          <div class="h-box-num-lbl">N° CHECKLIST</div>
          <div class="h-box-num-val">CK-{{ str_pad($checklist->id, 8, '0', STR_PAD_LEFT) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="content">

  {{-- ── DATOS DEL VEHÍCULO ──────────────────────── --}}
  <div class="card">
    <div class="card-title">DATOS DEL VEHÍCULO</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:16%;">Modelo / Versión</td>
        <td colspan="5">{{ $modelName }}</td>
      </tr>
      <tr>
        <td class="lbl">Año</td>
        <td style="width:15%;">{{ $year }}</td>
        <td class="lbl" style="width:10%;">Color</td>
        <td>{{ $color }}</td>
        <td class="lbl" style="width:12%;">N° Motor</td>
        <td>{{ $engineNum }}</td>
      </tr>
      <tr>
        <td class="lbl">VIN / N° Chasis</td>
        <td colspan="5">{{ $vin }}</td>
      </tr>
    </table>
  </div>

  {{-- ── DATOS DE LA ENTREGA ─────────────────────── --}}
  <div class="card">
    <div class="card-title">DATOS DE LA ENTREGA</div>
    <table class="dt">
      <tr>
        <td class="lbl">Cliente</td>
        <td colspan="3">{{ $clientName }}&nbsp;<span style="color:#8b8b8b;">({{ $clientDoc }})</span></td>
        <td class="lbl">Sede</td>
        <td>{{ $sedeName }}</td>
      </tr>
      <tr>
        <td class="lbl">Asesor</td>
        <td colspan="3">{{ $advisorName }}</td>
        <td class="lbl">F. Entrega</td>
        <td>{{ $deliveryDate }}</td>
      </tr>
    </table>
  </div>

  {{-- ── ÍTEMS RECEPCIÓN ─────────────────────────── --}}
  @if($receptionItems->count() > 0)
    <div class="card">
      <div class="card-title">ÍTEMS VERIFICADOS EN RECEPCIÓN DEL VEHÍCULO</div>
      <table class="cl">
        <thead>
        <tr>
          <th class="col-num">#</th>
          <th style="text-align:left;">Descripción</th>
          <th class="col-qty">Cantidad</th>
          <th class="col-unit">Unidad</th>
          <th class="col-check">Conforme</th>
          <th class="col-obs" style="text-align:left;">Observaciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($receptionItems->values() as $i => $item)
          <tr>
            <td class="col-num">{{ $i + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td class="col-qty">{{ number_format($item->quantity, 0) }}</td>
            <td class="col-unit">{{ $item->unit ?? '—' }}</td>
            <td class="col-check"><span
                class="chk {{ $item->is_confirmed ? 'on' : '' }}">{{ $item->is_confirmed ? 'Si' : 'No' }}</span></td>
            <td class="col-obs">{{ $item->observations ?? '' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif

  {{-- ── ACCESORIOS OC ───────────────────────────── --}}
  @if($poItems->count() > 0)
    <div class="card">
      <div class="card-title">ACCESORIOS INCLUIDOS EN LA COMPRA</div>
      <table class="cl">
        <thead>
        <tr>
          <th class="col-num">#</th>
          <th style="text-align:left;">Accesorio</th>
          <th class="col-qty">Cantidad</th>
          <th class="col-unit">Unidad</th>
          <th class="col-check">Conforme</th>
          <th class="col-obs" style="text-align:left;">Observaciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($poItems->values() as $i => $item)
          <tr>
            <td class="col-num">{{ $i + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td class="col-qty">{{ number_format($item->quantity, 0) }}</td>
            <td class="col-unit">{{ $item->unit ?? '—' }}</td>
            <td class="col-check"><span
                class="chk {{ $item->is_confirmed ? 'on' : '' }}">{{ $item->is_confirmed ? 'Si' : 'No' }}</span></td>
            <td class="col-obs">{{ $item->observations ?? '' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif

  {{-- ── ÍTEMS ADICIONALES ──────────────────────── --}}
  @if($manualItems->count() > 0)
    <div class="card">
      <div class="card-title">ÍTEMS ADICIONALES</div>
      <table class="cl">
        <thead>
        <tr>
          <th class="col-num">#</th>
          <th style="text-align:left;">Descripción</th>
          <th class="col-qty">Cantidad</th>
          <th class="col-unit">Unidad</th>
          <th class="col-check">Conforme</th>
          <th class="col-obs" style="text-align:left;">Observaciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($manualItems->values() as $i => $item)
          <tr>
            <td class="col-num">{{ $i + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td class="col-qty">{{ number_format($item->quantity, 0) }}</td>
            <td class="col-unit">{{ $item->unit ?? '—' }}</td>
            <td class="col-check"><span
                class="chk {{ $item->is_confirmed ? 'on' : '' }}">{{ $item->is_confirmed ? 'Si' : 'No' }}</span></td>
            <td class="col-obs">{{ $item->observations ?? '' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif

  @if($items->count() === 0)
    <div style="border:1px solid #d2d2d2; border-radius:6px; padding:10px 12px;
            font-style:italic; color:#aaaaaa; font-size:11px; margin-bottom:11px;">
      No se registraron ítems en este checklist.
    </div>
  @endif

  {{-- ── OBSERVACIONES GENERALES ─────────────────── --}}
  <div class="card">
    <div class="card-title">OBSERVACIONES GENERALES</div>
    <div style="padding:8px 12px; font-size:11px; min-height:36px; background:#fff;">
      {{ $checklist->observations ?? 'Sin observaciones.' }}
    </div>
  </div>

  {{-- ── DECLARACIÓN ──────────────────────────────── --}}
  <div class="decl">
    El cliente declara haber recibido el vehículo descrito en el presente documento en las condiciones
    indicadas, con todos los ítems verificados marcados como conformes, y sin perjuicio de las
    observaciones anotadas. La firma del presente documento implica la aceptación y conformidad
    con la entrega realizada.
  </div>

</div>{{-- /content --}}

{{-- ── FIRMAS fijas encima del footer ─────────── --}}
<div class="sig-fixed">
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig-hdr">FIRMA DEL CLIENTE</div>
      <div class="sig-body">
        <div class="sig-line"></div>
        <div class="sig-name">{{ $clientName }}</div>
        <div class="sig-sub">DNI / RUC: {{ $clientDoc }}</div>
      </div>
    </div>
    <div class="sig-col">
      <div class="sig-hdr">ASESOR DE VENTAS</div>
      <div class="sig-body">
        <div class="sig-line"></div>
        <div class="sig-name">{{ $advisorName }}</div>
        <div class="sig-sub">Sede: {{ $sedeName }}</div>
      </div>
    </div>
  </div>
  <div class="sig-foot">
    Automotores Pakatnamu S.A.C. &nbsp;·&nbsp; Documento de conformidad de entrega &nbsp;·&nbsp;
    CK-{{ str_pad($checklist->id, 8, '0', STR_PAD_LEFT) }} &nbsp;·&nbsp;
    {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
  </div>
</div>

{{-- ── FOOTER marcas fijo al fondo ─────────────── --}}
<div class="foot-fixed">
  <img src="{{ base64Img('images/ap/brands/suzuki.png') }}" alt="Suzuki">
  <img src="{{ base64Img('images/ap/brands/subaru.png') }}" alt="Subaru">
  <img src="{{ base64Img('images/ap/brands/dfsk.png') }}" alt="DFSK">
  <img src="{{ base64Img('images/ap/brands/mazda.png') }}" alt="Mazda">
  <img src="{{ base64Img('images/ap/brands/citroen.jpg') }}" alt="Citroën">
  <img src="{{ base64Img('images/ap/brands/renault.png') }}" alt="Renault">
  <img src="{{ base64Img('images/ap/brands/haval.png') }}" alt="Haval">
  <img src="{{ base64Img('images/ap/brands/great-wall.png') }}" alt="Great Wall">
  <img src="{{ base64Img('images/ap/brands/changan.png') }}" alt="Changan">
  <img src="{{ base64Img('images/ap/brands/jac.png') }}" alt="JAC">
</div>

</body>
</html>
