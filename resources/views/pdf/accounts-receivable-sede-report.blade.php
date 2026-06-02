<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cuentas por Cobrar &mdash; {{ $sede_name }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    @page {
      size: A4 landscape;
      margin: 14mm 14mm 18mm 14mm;
    }

    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 9pt;
      color: #111111;
      background: #ffffff;
    }

    /* ── Header ── */
    .header {
      width: 100%;
      border-bottom: 1.5pt solid #111111;
      padding-bottom: 8pt;
      margin-bottom: 10pt;
    }

    .header table {
      width: 100%;
    }

    .header .title {
      font-size: 16pt;
      font-weight: bold;
      color: #111111;
      line-height: 1.2;
    }

    .header .meta {
      font-size: 8pt;
      color: #6b7280;
      margin-top: 3pt;
    }

    .header .badge {
      font-size: 8pt;
      color: #6b7280;
      text-align: right;
    }

    /* ── KPI row ── */
    .kpis {
      width: 100%;
      margin-bottom: 12pt;
      border-collapse: separate;
      border-spacing: 6pt 0;
    }

    .kpi-cell {
      background-color: #f9fafb;
      border: 0.5pt solid #e5e7eb;
      padding: 7pt 10pt;
      width: 25%;
      vertical-align: top;
    }

    .kpi-value {
      font-size: 13pt;
      font-weight: bold;
      color: #111111;
      line-height: 1.2;
    }

    .kpi-value-overdue {
      font-size: 13pt;
      font-weight: bold;
      color: #dc2626;
      line-height: 1.2;
    }

    .kpi-value-current {
      font-size: 13pt;
      font-weight: bold;
      color: #16a34a;
      line-height: 1.2;
    }

    .kpi-label {
      font-size: 7.5pt;
      color: #6b7280;
      margin-top: 2pt;
    }

    /* ── Records table ── */
    .section-title {
      font-size: 9pt;
      font-weight: bold;
      color: #111111;
      margin-bottom: 6pt;
    }

    table.records {
      width: 100%;
      border-collapse: collapse;
    }

    table.records thead tr {
      background-color: #f3f4f6;
    }

    table.records thead th {
      padding: 5pt 6pt;
      text-align: left;
      font-size: 7.5pt;
      font-weight: bold;
      color: #374151;
      text-transform: uppercase;
      letter-spacing: 0.2pt;
      border-bottom: 1pt solid #d1d5db;
    }

    table.records thead th.right {
      text-align: right;
    }

    table.records tbody td {
      padding: 4pt 6pt;
      font-size: 8.5pt;
      color: #111111;
      border-bottom: 0.5pt solid #f3f4f6;
      vertical-align: top;
    }

    table.records tbody td.right {
      text-align: right;
    }

    table.records tbody td.muted {
      color: #6b7280;
    }

    table.records tbody td.overdue {
      color: #dc2626;
      font-weight: bold;
    }

    table.records tbody td.current {
      color: #16a34a;
      font-weight: bold;
    }

    table.records tbody tr.row-alt {
      background-color: #fafafa;
    }

    table.records tfoot td {
      padding: 6pt 6pt;
      font-size: 9pt;
      font-weight: bold;
      color: #111111;
      border-top: 1pt solid #d1d5db;
      background-color: #f9fafb;
    }

    table.records tfoot td.right {
      text-align: right;
    }

    /* ── Footer (fixed) ── */
    .page-footer {
      position: fixed;
      bottom: -14mm;
      left: 0;
      right: 0;
      height: 14mm;
      border-top: 0.5pt solid #e5e7eb;
      padding-top: 4pt;
      font-size: 7.5pt;
      color: #9ca3af;
    }

    .page-footer table {
      width: 100%;
    }
  </style>
</head>
<body>

{{-- Page footer (fixed) --}}
<div class="page-footer">
  <table cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="text-align:left;color:#9ca3af;font-size:7.5pt;">
        Sian &nbsp;&middot;&nbsp; Reporte generado automáticamente &nbsp;&middot;&nbsp; {{ $report_date }}
      </td>
      <td style="text-align:right;color:#9ca3af;font-size:7.5pt;">
        Este documento es de uso interno
      </td>
    </tr>
  </table>
</div>

{{-- Header --}}
<div class="header">
  <table cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="vertical-align:bottom;width:70%;">
        <div class="title">Cuentas por Cobrar &mdash; {{ $sede_name }}</div>
        <div class="meta">{{ strtoupper($company) }} &nbsp;&middot;&nbsp; Generado el {{ $report_date }}</div>
      </td>
      <td style="vertical-align:bottom;width:30%;text-align:right;">
        <div class="badge">Documentos pendientes de cobro</div>
      </td>
    </tr>
  </table>
</div>

{{-- KPIs --}}
<table class="kpis" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td class="kpi-cell">
      <div class="kpi-value">{{ number_format($summary['total_documents']) }}</div>
      <div class="kpi-label">Documentos pendientes</div>
    </td>
    <td style="width:5pt;"></td>
    <td class="kpi-cell">
      <div class="kpi-value">S/ {{ number_format($summary['total_balance_pen'], 2) }}</div>
      <div class="kpi-label">Saldo total</div>
    </td>
    <td style="width:5pt;"></td>
    <td class="kpi-cell">
      <div class="kpi-value-overdue">S/ {{ number_format($summary['overdue_balance_pen'], 2) }}</div>
      <div class="kpi-label">Saldo vencido</div>
    </td>
    <td style="width:5pt;"></td>
    <td class="kpi-cell">
      <div class="kpi-value-current">S/ {{ number_format($summary['current_balance_pen'], 2) }}</div>
      <div class="kpi-label">Saldo por vencer</div>
    </td>
  </tr>
</table>

{{-- Section title --}}
<p class="section-title">Detalle de documentos ({{ count($records) }})</p>

{{-- Records --}}
<table class="records">
  <thead>
  <tr>
    <th style="width:20pt;">#</th>
    <th style="width:175pt;">Cliente</th>
    <th style="width:80pt;">RUC / ID</th>
    <th style="width:105pt;">N&deg; Documento</th>
    <th style="width:65pt;" class="right">F. Emisi&oacute;n</th>
    <th style="width:65pt;" class="right">F. Vencim.</th>
    <th style="width:52pt;" class="right">D&iacute;as Venc.</th>
    <th style="width:65pt;">Estado</th>
    <th style="width:32pt;" class="right">Mon.</th>
    <th style="width:70pt;" class="right">Saldo Orig.</th>
    <th style="width:78pt;" class="right">Saldo S/</th>
    <th style="width:100pt;">Vendedor</th>
  </tr>
  </thead>
  <tbody>
  @foreach($records as $i => $record)
    @php $isOverdue = ($record['overdue_days'] ?? 0) > 0; @endphp
    <tr class="{{ $i % 2 !== 0 ? 'row-alt' : '' }}">
      <td class="muted">{{ $i + 1 }}</td>
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
    <td colspan="9" class="right">Total Saldo (S/)</td>
    <td class="right">S/ {{ number_format($summary['total_balance_pen'], 2) }}</td>
    <td></td>
    <td></td>
  </tr>
  </tfoot>
</table>

</body>
</html>
