@php
  use App\Models\ap\marketing\MktSupport;

  function base64Img($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
  }

  $activity = $activity ?? null;
  $supports = $supports ?? collect();
  $totalsByCurrency = $totalsByCurrency ?? collect();
  $annexes = $annexes ?? [];

  $budgetTypeLabels = ['regular' => 'Regular', 'additional' => 'Adicional'];
  $planName   = optional(optional($activity->budget)->plan)->name ?? 'N/A';
  $budgetType = $budgetTypeLabels[optional($activity->budget)->type] ?? (optional($activity->budget)->type ?? 'N/A');
  $responsible = $activity->responsible ?: 'N/A';
  $dateRange = $activity->start_date
    ? \Carbon\Carbon::parse($activity->start_date)->format('d/m/Y') . ' - ' .
      ($activity->end_date ? \Carbon\Carbon::parse($activity->end_date)->format('d/m/Y') : 'N/A')
    : 'N/A';
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sustentos de Actividad</title>
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
    }

    .page-header {
      padding: 8px 22px;
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
      font-size: 16px;
      font-weight: bold;
      white-space: nowrap;
      margin-top: 4px;
    }

    .content {
      padding: 0 22px;
    }

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
      font-size: 10.5px;
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
      width: 24px;
      text-align: center;
    }

    .col-amount {
      width: 80px;
      text-align: right;
    }

    .col-date {
      width: 62px;
      text-align: center;
    }

    .totals {
      margin-top: 6px;
      text-align: right;
    }

    .totals .row {
      font-size: 12px;
      font-weight: bold;
      padding: 2px 0;
    }

    .empty {
      border: 1px solid #d2d2d2;
      border-radius: 6px;
      padding: 10px 12px;
      font-style: italic;
      color: #aaaaaa;
      font-size: 11px;
      margin-bottom: 11px;
    }

    .annex-title {
      margin-top: 14px;
      background-color: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10.5px;
      padding: 5px 12px;
      letter-spacing: 0.3px;
      border-radius: 4px;
    }

    .annex-item {
      page-break-inside: avoid;
      border: 1px solid #d2d2d2;
      border-radius: 7px;
      padding: 8px 10px;
      margin-top: 10px;
    }

    .annex-caption {
      font-size: 10px;
      font-weight: bold;
      color: #22293a;
      margin-bottom: 6px;
    }

    .annex-image {
      display: block;
      max-width: 100%;
      max-height: 320px;
      margin: 0 auto;
    }

    .annex-pdf {
      font-size: 10px;
      color: #777777;
      font-style: italic;
    }

    .footer {
      margin-top: 16px;
      text-align: center;
      font-size: 8px;
      color: #aaaaaa;
      border-top: 1px solid #d2d2d2;
      padding-top: 5px;
    }
  </style>
</head>
<body>

<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      <img src="{{ base64Img('images/ap/logo-ap.png') }}" alt="AP Logo">
    </div>
    <div class="h-right">
      <div class="h-box">
        <div class="h-box-title">
          <div class="h-box-title-main">SUSTENTOS DE ACTIVIDAD DE MARKETING</div>
          <div class="h-box-title-sub">Detalle de comprobantes registrados</div>
        </div>
        <div class="h-box-num">
          <div class="h-box-num-lbl">N° ACTIVIDAD</div>
          <div class="h-box-num-val">ACT-{{ str_pad($activity->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="content">

  <div class="card">
    <div class="card-title">DATOS DE LA ACTIVIDAD</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:16%;">Nombre</td>
        <td colspan="3">{{ $activity->name }}</td>
      </tr>
      <tr>
        <td class="lbl">Plan / Presupuesto</td>
        <td>{{ $planName }} · {{ $budgetType }}</td>
        <td class="lbl" style="width:14%;">Tipo</td>
        <td>{{ $activity->activity_type ?: 'N/A' }}</td>
      </tr>
      <tr>
        <td class="lbl">Responsable</td>
        <td>{{ $responsible }}</td>
        <td class="lbl">Fechas</td>
        <td>{{ $dateRange }}</td>
      </tr>
    </table>
  </div>

  @if($supports->count() > 0)
    <div class="card">
      <div class="card-title">SUSTENTOS REGISTRADOS ({{ $supports->count() }})</div>
      <table class="cl">
        <thead>
        <tr>
          <th class="col-num">#</th>
          <th style="text-align:left;">Tipo</th>
          <th style="text-align:left;">Documento</th>
          <th style="text-align:left;">Proveedor</th>
          <th class="col-date">Fecha</th>
          <th class="col-amount">Monto</th>
          <th style="text-align:center; width:60px;">Adjuntos</th>
        </tr>
        </thead>
        <tbody>
        @foreach($supports->values() as $i => $support)
          @php
            $fileCount = $support->digitalFiles->count() > 0
              ? $support->digitalFiles->count()
              : ($support->file_path ? 1 : 0);
          @endphp
          <tr>
            <td class="col-num">{{ $i + 1 }}</td>
            <td>{{ MktSupport::TYPE_LABELS[$support->type] ?? $support->type }}</td>
            <td>{{ trim(($support->document_series ?? '') . '-' . ($support->document_number ?? ''), '-') ?: '—' }}</td>
            <td>{{ optional($support->supplier)->full_name ?? '—' }}</td>
            <td class="col-date">{{ $support->issue_date ? $support->issue_date->format('d/m/Y') : '—' }}</td>
            <td class="col-amount">{{ $support->currency->symbol ?? 'S/' }} {{ number_format($support->amount ?? 0, 2) }}</td>
            <td style="text-align:center;">{{ $fileCount > 0 ? "Ver anexos" : '—' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div class="totals">
      @foreach($totalsByCurrency as $symbol => $total)
        <div class="row">Total {{ $symbol }}: {{ $symbol }} {{ number_format($total, 2) }}</div>
      @endforeach
    </div>
  @else
    <div class="empty">Esta actividad aún no tiene sustentos registrados.</div>
  @endif

  @if(count($annexes) > 0)
    <div class="annex-title">ANEXOS · COMPROBANTES ADJUNTOS</div>
    @foreach($annexes as $annex)
      <div class="annex-item">
        <div class="annex-caption">Sustento N° {{ $annex['support_number'] }} — {{ $annex['support_label'] }}</div>
        @if($annex['is_image'])
          <img class="annex-image" src="{{ $annex['src'] }}" alt="Anexo">
        @elseif($annex['is_pdf'])
          <div class="annex-pdf">Documento PDF adjunto{{ $annex['name'] ? ': ' . $annex['name'] : '' }} (no se muestra en este reporte)</div>
        @else
          <div class="annex-pdf">Archivo adjunto no disponible para previsualizar{{ $annex['name'] ? ': ' . $annex['name'] : '' }}</div>
        @endif
      </div>
    @endforeach
  @endif

</div>

<div class="footer">
  Automotores Pakatnamu S.A.C. &nbsp;·&nbsp; Reporte de sustentos de actividad &nbsp;·&nbsp;
  ACT-{{ str_pad($activity->id, 6, '0', STR_PAD_LEFT) }} &nbsp;·&nbsp;
  {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
