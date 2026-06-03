@php
  function arBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
  }
  $logoUrl = arBase64Image(config('companies.logos.dp.large', 'companies/dplargo.png'));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cuentas por Cobrar &mdash; {{ $sede_name }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    @page {
      size: A4 landscape;
      margin: 12mm 12mm 16mm 12mm;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      color: #22293a;
      background: #ffffff;
      padding: 0 0 20px;
    }

    /* ── HEADER ── */
    .page-header { margin-bottom: 8px; }

    .header-inner {
      display: table;
      width: 100%;
      border: 1.5px solid #e0e0e0;
      border-radius: 5px;
      overflow: hidden;
    }

    .h-logo {
      display: table-cell;
      width: 130px;
      text-align: center;
      vertical-align: middle;
      padding: 6px 10px;
      border-right: 1px solid #e0e0e0;
    }

    .h-logo img { max-height: 28px; width: auto; display: block; margin: 0 auto; }

    .h-title {
      display: table-cell;
      vertical-align: middle;
      text-align: center;
      padding: 6px 14px;
      border-right: 1px solid #e0e0e0;
    }

    .h-title-main {
      font-size: 12px;
      font-weight: bold;
      color: #22293a;
      letter-spacing: 0.3px;
      line-height: 1.3;
    }

    .h-title-sub { font-size: 8px; color: #888888; margin-top: 2px; }

    .h-meta {
      display: table-cell;
      width: 170px;
      vertical-align: middle;
      background: #e8e8e8;
      text-align: center;
      padding: 5px 10px;
    }

    .h-meta-lbl {
      font-size: 7.5px;
      font-weight: bold;
      letter-spacing: 0.6px;
      color: #22293a;
      text-transform: uppercase;
    }

    .h-meta-val {
      font-size: 11px;
      font-weight: bold;
      color: #22293a;
      margin-top: 2px;
      white-space: nowrap;
    }

    .h-meta-date { font-size: 7.5px; color: #555555; margin-top: 2px; }

    /* ── CARDS ── */
    .card {
      border: 1px solid #cccccc;
      border-radius: 4px;
      overflow: hidden;
      margin-bottom: 7px;
      background: #ffffff;
    }

    .card-title {
      background: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      letter-spacing: 0.3px;
      padding: 4px 10px;
      border-bottom: 1px solid #d2d2d2;
    }

    /* ── KPI GRID ── */
    .kpi-table {
      width: 100%;
      border-collapse: collapse;
    }

    .kpi-cell {
      padding: 7px 14px;
      vertical-align: top;
      border-right: 1px solid #e8e8e8;
      width: 25%;
    }

    .kpi-cell:last-child { border-right: none; }

    .kpi-value {
      font-size: 13px;
      font-weight: bold;
      color: #22293a;
      line-height: 1.2;
    }

    .kpi-value-overdue { font-size: 13px; font-weight: bold; color: #c0392b; line-height: 1.2; }
    .kpi-value-current { font-size: 13px; font-weight: bold; color: #27ae60; line-height: 1.2; }

    .kpi-label { font-size: 7.5px; color: #777777; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.4px; }

    /* ── RECORDS TABLE ── */
    table.records {
      width: 100%;
      border-collapse: collapse;
      font-size: 9px;
    }

    table.records thead tr { background: #f5f5f5; }

    table.records thead th {
      padding: 5px 7px;
      text-align: left;
      font-size: 8px;
      font-weight: bold;
      color: #333333;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      border-right: 1px solid #d5d5d5;
      border-bottom: 2px solid #d5d5d5;
    }

    table.records thead th:last-child { border-right: none; }
    table.records thead th.right { text-align: right; }

    table.records tbody td {
      padding: 4px 7px;
      color: #1a1a1a;
      border-right: 1px solid #ebebeb;
      border-bottom: 1px solid #ebebeb;
      vertical-align: top;
    }

    table.records tbody td:last-child { border-right: none; }
    table.records tbody tr:last-child td { border-bottom: none; }
    table.records tbody tr:nth-child(even) { background: #fcfcfc; }
    table.records tbody td.right { text-align: right; }
    table.records tbody td.muted { color: #777777; }
    table.records tbody td.overdue { color: #c0392b; font-weight: bold; }
    table.records tbody td.current { color: #27ae60; font-weight: bold; }
    table.records tbody td.num { text-align: center; }

    table.records tfoot td {
      padding: 5px 7px;
      font-size: 9px;
      font-weight: bold;
      color: #22293a;
      border-top: 2px solid #d5d5d5;
      background: #f5f5f5;
      border-right: 1px solid #d5d5d5;
    }

    table.records tfoot td:last-child { border-right: none; }
    table.records tfoot td.right { text-align: right; }

    /* ── FOOTER FIJO ── */
    .page-footer {
      position: fixed;
      bottom: -12mm;
      left: 0;
      right: 0;
      height: 12mm;
      border-top: 1px solid #cccccc;
      padding-top: 4px;
      background: #ffffff;
    }

    .page-footer table { width: 100%; border-collapse: collapse; }

    .footer-left {
      font-size: 7.5px;
      color: #777777;
      letter-spacing: 0.4px;
      text-transform: uppercase;
    }

    .footer-right { font-size: 7.5px; color: #777777; text-align: right; }
  </style>
</head>
<body>

{{-- Footer fijo --}}
<div class="page-footer">
  <table cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td class="footer-left">
        {{ strtoupper($company) }} &nbsp;&middot;&nbsp; Documento de uso interno &nbsp;&middot;&nbsp; {{ $report_date }}
      </td>
      <td class="footer-right">Reporte generado automáticamente</td>
    </tr>
  </table>
</div>

{{-- Header --}}
<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo">
      @endif
    </div>
    <div class="h-title">
      <div class="h-title-main">CUENTAS POR COBRAR</div>
      <div class="h-title-sub">Documentos pendientes de cobro &nbsp;&middot;&nbsp; {{ strtoupper($company) }}</div>
    </div>
    <div class="h-meta">
      <div class="h-meta-lbl">Sede</div>
      <div class="h-meta-val">{{ strtoupper($sede_abrev ?? $sede_name) }}</div>
      <div class="h-meta-date">{{ $report_date }}</div>
    </div>
  </div>
</div>

{{-- KPIs --}}
<div class="card">
  <div class="card-title">Resumen</div>
  <table class="kpi-table" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td class="kpi-cell">
        <div class="kpi-value">{{ number_format($summary['total_documents']) }}</div>
        <div class="kpi-label">Documentos pendientes</div>
      </td>
      <td class="kpi-cell">
        <div class="kpi-value">S/ {{ number_format($summary['total_balance_pen'], 2) }}</div>
        <div class="kpi-label">Saldo total</div>
      </td>
      <td class="kpi-cell">
        <div class="kpi-value-overdue">S/ {{ number_format($summary['overdue_balance_pen'], 2) }}</div>
        <div class="kpi-label">Saldo vencido</div>
      </td>
      <td class="kpi-cell">
        <div class="kpi-value-current">S/ {{ number_format($summary['current_balance_pen'], 2) }}</div>
        <div class="kpi-label">Saldo por vencer</div>
      </td>
    </tr>
  </table>
</div>

{{-- Detalle --}}
<div class="card">
  <div class="card-title">Detalle de documentos ({{ count($records) }})</div>
  <table class="records">
    <thead>
    <tr>
      <th style="width:18px;" class="num">#</th>
      <th style="width:165px;">Cliente</th>
      <th style="width:75px;">RUC / ID</th>
      <th style="width:100px;">N&deg; Documento</th>
      <th style="width:60px;" class="right">F. Emisión</th>
      <th style="width:60px;" class="right">F. Vencim.</th>
      <th style="width:48px;" class="right">Días Venc.</th>
      <th style="width:62px;">Estado</th>
      <th style="width:28px;" class="right">Mon.</th>
      <th style="width:68px;" class="right">Saldo Orig.</th>
      <th style="width:76px;" class="right">Saldo S/</th>
      <th>Vendedor</th>
    </tr>
    </thead>
    <tbody>
    @foreach($records as $i => $record)
      @php $isOverdue = ($record['overdue_days'] ?? 0) > 0; @endphp
      <tr>
        <td class="num muted">{{ $i + 1 }}</td>
        <td>{{ $record['client_name'] }}</td>
        <td class="muted">{{ $record['client_id'] }}</td>
        <td class="muted">{{ $record['document_number'] }}</td>
        <td class="right muted">{{ $record['document_date'] ?? '&mdash;' }}</td>
        <td class="right muted">{{ $record['document_due_date'] ?? '&mdash;' }}</td>
        <td class="right {{ $isOverdue ? 'overdue' : 'current' }}">{{ $record['overdue_days'] ?? 0 }}</td>
        <td class="{{ $isOverdue ? 'overdue' : 'current' }}">{{ $record['overdue_status'] ?? '&mdash;' }}</td>
        <td class="right muted">{{ $record['currency'] }}</td>
        <td class="right">{{ number_format($record['balance'], 2) }}</td>
        <td class="right" style="font-weight:bold;">{{ number_format($record['balance_pen'], 2) }}</td>
        <td class="muted">{{ $record['seller'] ?? '&mdash;' }}</td>
      </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
      <td colspan="10" class="right">Total Saldo (S/)</td>
      <td class="right">S/ {{ number_format($summary['total_balance_pen'], 2) }}</td>
      <td></td>
    </tr>
    </tfoot>
  </table>
</div>

</body>
</html>
