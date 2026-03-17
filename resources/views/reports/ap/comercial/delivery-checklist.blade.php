@php
  function base64Img($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
  }

  $vehicle  = $vehicle ?? null;
  $delivery = $delivery ?? null;
  $checklist = $checklist ?? null;

  $modelName  = optional($vehicle->model)->description ?? 'N/A';
  $color      = optional($vehicle->color)->description ?? 'N/A';
  $year       = $vehicle->year ?? 'N/A';
  $vin        = $vehicle->vin ?? 'N/A';
  $engineNum  = $vehicle->engine_number ?? 'N/A';

  $clientName = optional($delivery->client)->full_name ?? 'N/A';
  $clientDoc  = optional($delivery->client)->num_doc ?? '';
  $advisorName= optional($delivery->advisor)->nombre_completo ?? 'N/A';
  $sedeName   = optional($delivery->sede)->abreviatura ?? 'N/A';
  $deliveryDate = $delivery->scheduled_delivery_date ? \Carbon\Carbon::parse($delivery->scheduled_delivery_date)->format('d/m/Y') : 'N/A';
  $confirmedDate= $checklist->confirmed_at ? \Carbon\Carbon::parse($checklist->confirmed_at)->format('d/m/Y H:i') : '—';

  $items = $checklist->items ?? collect();
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
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: Arial, sans-serif;
      font-size: 9px;
      color: #1a1a1a;
      padding: 18px 20px;
    }

    /* ── HEADER ─────────────────────────────────── */
    .header-bar {
      width: 100%;
      height: 8px;
      background: #172e66;
      margin-bottom: 8px;
    }

    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .header-table td { vertical-align: middle; padding: 0; border: none; }
    .logo-cell { width: 100px; }
    .logo-cell img { max-width: 90px; max-height: 50px; }
    .title-cell { text-align: center; }
    .doc-cell { width: 140px; text-align: right; }

    .doc-box {
      border: 1.5px solid #172e66;
      border-radius: 4px;
      padding: 5px 8px;
      display: inline-block;
      min-width: 130px;
    }
    .doc-box .doc-label { font-size: 7px; color: #172e66; font-weight: bold; letter-spacing: 0.5px; }
    .doc-box .doc-number { font-size: 14px; font-weight: bold; color: #172e66; }

    .main-title { font-size: 13px; font-weight: bold; color: #172e66; letter-spacing: 0.5px; }
    .sub-title { font-size: 8px; color: #555; margin-top: 2px; }

    /* ── SECTION TITLE ──────────────────────────── */
    .section-title {
      background: #172e66;
      color: #fff;
      font-weight: bold;
      font-size: 8.5px;
      padding: 4px 8px;
      letter-spacing: 0.5px;
      margin-top: 10px;
      margin-bottom: 0;
    }

    /* ── INFO GRID ──────────────────────────────── */
    .info-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #c5cfe0;
    }
    .info-table td {
      padding: 4px 7px;
      border: 1px solid #c5cfe0;
      font-size: 8.5px;
      vertical-align: top;
    }
    .info-label {
      font-weight: bold;
      color: #172e66;
      width: 90px;
      background: #f0f4fa;
      white-space: nowrap;
    }
    .info-value { color: #1a1a1a; }

    /* ── CHECKLIST TABLE ────────────────────────── */
    .cl-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0;
    }
    .cl-table th {
      background: #172e66;
      color: #fff;
      font-size: 8px;
      font-weight: bold;
      padding: 4px 6px;
      text-align: left;
      border: 1px solid #172e66;
    }
    .cl-table td {
      padding: 4px 6px;
      border: 1px solid #c5cfe0;
      font-size: 8.5px;
      vertical-align: middle;
    }
    .cl-table tr:nth-child(even) td { background: #f7f9fc; }
    .cl-table tr:nth-child(odd) td  { background: #fff; }

    .col-num   { width: 22px;  text-align: center; }
    .col-desc  { width: auto; }
    .col-qty   { width: 45px;  text-align: center; }
    .col-unit  { width: 45px;  text-align: center; }
    .col-check { width: 50px;  text-align: center; }
    .col-obs   { width: 120px; }

    .checkbox-box {
      display: inline-block;
      width: 12px;
      height: 12px;
      border: 1.5px solid #172e66;
      background: #fff;
      vertical-align: middle;
      line-height: 12px;
      text-align: center;
    }
    .checkbox-box.checked { background: #172e66; color: #fff; font-size: 9px; font-weight: bold; }

    .badge-reception { color: #0a7; font-size: 7px; font-weight: bold; }
    .badge-po        { color: #c60; font-size: 7px; font-weight: bold; }
    .badge-manual    { color: #55a; font-size: 7px; font-weight: bold; }

    .group-header td {
      background: #e8edf7 !important;
      font-weight: bold;
      font-size: 8px;
      color: #172e66;
      padding: 3px 6px;
    }

    /* ── OBSERVATIONS BOX ───────────────────────── */
    .obs-box {
      border: 1px solid #c5cfe0;
      min-height: 40px;
      padding: 6px 8px;
      font-size: 8.5px;
      margin-top: 0;
    }

    /* ── SIGNATURE AREA ─────────────────────────── */
    .signature-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .signature-table td {
      width: 50%;
      padding: 8px 10px;
      vertical-align: bottom;
    }
    .sig-box {
      border: 1px solid #c5cfe0;
      border-radius: 3px;
      padding: 6px 10px 4px;
    }
    .sig-line {
      border-top: 1.5px solid #172e66;
      margin-top: 45px;
      margin-bottom: 4px;
    }
    .sig-label { font-size: 8px; font-weight: bold; color: #172e66; text-align: center; }
    .sig-sublabel { font-size: 7.5px; color: #555; text-align: center; }

    /* ── FOOTER ─────────────────────────────────── */
    .footer-bar {
      width: 100%;
      height: 5px;
      background: #172e66;
      margin-top: 18px;
    }
    .footer-text {
      text-align: center;
      font-size: 7px;
      color: #888;
      margin-top: 4px;
    }

    .no-items { font-style: italic; color: #888; font-size: 8px; padding: 5px 8px; }
  </style>
</head>
<body>

  <div class="header-bar"></div>

  <!-- CABECERA -->
  <table class="header-table">
    <tr>
      <td class="logo-cell">
        @php $logoSrc = base64Img('images/logo.png'); @endphp
        @if($logoSrc)
          <img src="{{ $logoSrc }}" alt="Logo">
        @else
          <strong style="font-size:11px; color:#172e66;">PAKATNAMU</strong>
        @endif
      </td>
      <td class="title-cell">
        <div class="main-title">CHECKLIST DE ENTREGA DE VEHÍCULO</div>
        <div class="sub-title">Automotores Pakatnamu S.A.C. &nbsp;|&nbsp; Conformidad de entrega al cliente</div>
      </td>
      <td class="doc-cell">
        <div class="doc-box">
          <div class="doc-label">N° CHECKLIST</div>
          <div class="doc-number">CK-{{ str_pad($checklist->id, 6, '0', STR_PAD_LEFT) }}</div>
          <div class="doc-label" style="margin-top:3px;">ESTADO</div>
          <div style="font-size:8px; color:{{ $checklist->status === 'confirmed' ? '#0a7' : '#c60' }}; font-weight:bold;">
            {{ $checklist->status === 'confirmed' ? 'CONFIRMADO' : 'BORRADOR' }}
          </div>
        </div>
      </td>
    </tr>
  </table>

  <!-- DATOS DEL VEHÍCULO -->
  <div class="section-title">DATOS DEL VEHÍCULO</div>
  <table class="info-table">
    <tr>
      <td class="info-label">Modelo</td>
      <td class="info-value">{{ $modelName }}</td>
      <td class="info-label">Año</td>
      <td class="info-value">{{ $year }}</td>
      <td class="info-label">Color</td>
      <td class="info-value">{{ $color }}</td>
    </tr>
    <tr>
      <td class="info-label">VIN / N° Chasis</td>
      <td class="info-value" colspan="3">{{ $vin }}</td>
      <td class="info-label">N° Motor</td>
      <td class="info-value">{{ $engineNum }}</td>
    </tr>
  </table>

  <!-- DATOS DE LA ENTREGA -->
  <div class="section-title" style="margin-top:8px;">DATOS DE LA ENTREGA</div>
  <table class="info-table">
    <tr>
      <td class="info-label">Cliente</td>
      <td class="info-value" colspan="3">{{ $clientName }} &nbsp;<span style="color:#555;">({{ $clientDoc }})</span></td>
      <td class="info-label">Sede</td>
      <td class="info-value">{{ $sedeName }}</td>
    </tr>
    <tr>
      <td class="info-label">Asesor</td>
      <td class="info-value" colspan="3">{{ $advisorName }}</td>
      <td class="info-label">F. Entrega</td>
      <td class="info-value">{{ $deliveryDate }}</td>
    </tr>
    @if($checklist->status === 'confirmed')
    <tr>
      <td class="info-label">Confirmado por</td>
      <td class="info-value" colspan="3">{{ optional($checklist->confirmedBy)->name ?? '—' }}</td>
      <td class="info-label">F. Confirmación</td>
      <td class="info-value">{{ $confirmedDate }}</td>
    </tr>
    @endif
  </table>

  <!-- CHECKLIST DE ÍTEMS RECEPCIONADOS -->
  @if($receptionItems->count() > 0)
    <div class="section-title" style="margin-top:10px;">ÍTEMS VERIFICADOS EN RECEPCIÓN DEL VEHÍCULO</div>
    <table class="cl-table">
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th class="col-desc">Descripción</th>
          <th class="col-qty">Cantidad</th>
          <th class="col-unit">Unidad</th>
          <th class="col-check">Conforme</th>
          <th class="col-obs">Observaciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($receptionItems->values() as $i => $item)
        <tr>
          <td class="col-num">{{ $i + 1 }}</td>
          <td class="col-desc">{{ $item->description }}</td>
          <td class="col-qty">{{ number_format($item->quantity, 0) }}</td>
          <td class="col-unit">{{ $item->unit ?? '—' }}</td>
          <td class="col-check">
            <span class="checkbox-box {{ $item->is_confirmed ? 'checked' : '' }}">{{ $item->is_confirmed ? '✓' : '' }}</span>
          </td>
          <td class="col-obs">{{ $item->observations ?? '' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <!-- ACCESORIOS DE LA ORDEN DE COMPRA -->
  @if($poItems->count() > 0)
    <div class="section-title" style="margin-top:10px;">ACCESORIOS INCLUIDOS EN LA COMPRA</div>
    <table class="cl-table">
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th class="col-desc">Accesorio</th>
          <th class="col-qty">Cantidad</th>
          <th class="col-unit">Unidad</th>
          <th class="col-check">Conforme</th>
          <th class="col-obs">Observaciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($poItems->values() as $i => $item)
        <tr>
          <td class="col-num">{{ $i + 1 }}</td>
          <td class="col-desc">{{ $item->description }}</td>
          <td class="col-qty">{{ number_format($item->quantity, 0) }}</td>
          <td class="col-unit">{{ $item->unit ?? '—' }}</td>
          <td class="col-check">
            <span class="checkbox-box {{ $item->is_confirmed ? 'checked' : '' }}">{{ $item->is_confirmed ? '✓' : '' }}</span>
          </td>
          <td class="col-obs">{{ $item->observations ?? '' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <!-- ÍTEMS ADICIONALES -->
  @if($manualItems->count() > 0)
    <div class="section-title" style="margin-top:10px;">ÍTEMS ADICIONALES</div>
    <table class="cl-table">
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th class="col-desc">Descripción</th>
          <th class="col-qty">Cantidad</th>
          <th class="col-unit">Unidad</th>
          <th class="col-check">Conforme</th>
          <th class="col-obs">Observaciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($manualItems->values() as $i => $item)
        <tr>
          <td class="col-num">{{ $i + 1 }}</td>
          <td class="col-desc">{{ $item->description }}</td>
          <td class="col-qty">{{ number_format($item->quantity, 0) }}</td>
          <td class="col-unit">{{ $item->unit ?? '—' }}</td>
          <td class="col-check">
            <span class="checkbox-box {{ $item->is_confirmed ? 'checked' : '' }}">{{ $item->is_confirmed ? '✓' : '' }}</span>
          </td>
          <td class="col-obs">{{ $item->observations ?? '' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if($items->count() === 0)
    <div style="margin-top:10px; padding:8px; border:1px solid #c5cfe0; color:#888; font-style:italic;">
      No se registraron ítems en este checklist.
    </div>
  @endif

  <!-- OBSERVACIONES GENERALES -->
  <div class="section-title" style="margin-top:12px;">OBSERVACIONES GENERALES</div>
  <div class="obs-box">{{ $checklist->observations ?? 'Sin observaciones.' }}</div>

  <!-- DECLARACIÓN -->
  <div style="margin-top:14px; border:1px solid #c5cfe0; border-left: 4px solid #172e66; padding: 8px 10px; font-size:8px; color:#333;">
    El cliente declara haber recibido el vehículo descrito en el presente documento en las condiciones
    indicadas, con todos los ítems verificados marcados como conformes, y sin perjuicio de las
    observaciones anotadas. La firma del presente documento implica la aceptación y conformidad
    con la entrega realizada.
  </div>

  <!-- FIRMAS -->
  <table class="signature-table">
    <tr>
      <td style="padding-right:15px;">
        <div class="sig-box">
          <div class="sig-line"></div>
          <div class="sig-label">FIRMA DEL CLIENTE</div>
          <div class="sig-sublabel">{{ $clientName }}</div>
          <div class="sig-sublabel">DNI / RUC: {{ $clientDoc }}</div>
        </div>
      </td>
      <td style="padding-left:15px;">
        <div class="sig-box">
          <div class="sig-line"></div>
          <div class="sig-label">ASESOR DE VENTAS</div>
          <div class="sig-sublabel">{{ $advisorName }}</div>
          <div class="sig-sublabel">Sede: {{ $sedeName }}</div>
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="text-align:center; padding-top:10px;">
        <div style="font-size:7.5px; color:#555;">
          Fecha y hora de impresión: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
        </div>
      </td>
    </tr>
  </table>

  <div class="footer-bar"></div>
  <div class="footer-text">
    Automotores Pakatnamu S.A.C. &nbsp;—&nbsp; Documento interno de conformidad de entrega &nbsp;—&nbsp;
    Checklist N° CK-{{ str_pad($checklist->id, 6, '0', STR_PAD_LEFT) }}
  </div>

</body>
</html>
