@php
  /**
   * Returns a subtle background color for an attendance code.
   * Used to visually differentiate shifts in the attendance grid.
   */
  function attendanceColor(string $code): string
  {
    if (str_starts_with($code, 'VC')) return '#d4f1e0'; // vacation → green
    if (str_starts_with($code, 'F'))  return '#fde8e8'; // fault/absence → red
    if ($code === 'N' || str_starts_with($code, 'DN')) return '#dbeafe'; // night → blue
    if (str_starts_with($code, 'DD')) return '#fef9c3'; // double day → yellow
    if ($code === 'D')                return '#f0fdf4'; // regular day → very light green
    return '#f9fafb'; // other → near-white
  }

  $hasVacation = isset($summaryData['rows'][0]['days_vacation']);
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Planilla {{ $period->code }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: Arial, sans-serif;
      font-size: 8px;
      color: #1e293b;
    }

    /* ── Page breaks ─────────────────────────────────────── */
    .sheet { page-break-before: always; }
    .sheet:first-child { page-break-before: avoid; }

    /* ── Shared header ────────────────────────────────────── */
    .sheet-header {
      border-bottom: 2px solid #1e40af;
      padding-bottom: 6px;
      margin-bottom: 8px;
    }
    .sheet-title {
      font-size: 11px;
      font-weight: bold;
      color: #1e40af;
    }
    .sheet-meta {
      font-size: 7.5px;
      color: #475569;
      margin-top: 2px;
    }
    .sheet-meta span {
      margin-right: 12px;
    }

    /* ── Generic table base ───────────────────────────────── */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 7.5px;
    }
    .data-table th {
      background: #1e40af;
      color: #ffffff;
      padding: 3px 4px;
      text-align: center;
      border: 1px solid #1e3a8a;
      font-weight: bold;
      white-space: nowrap;
    }
    .data-table td {
      padding: 2px 4px;
      border: 1px solid #cbd5e1;
      vertical-align: middle;
    }
    .data-table tbody tr:nth-child(even) td {
      background: #f8fafc;
    }
    .data-table tfoot td {
      background: #1e3a8a;
      color: #ffffff;
      font-weight: bold;
      border: 1px solid #1e40af;
      padding: 3px 4px;
    }

    /* ── Sheet 1 – Attendance ─────────────────────────────── */
    .att-table {
      table-layout: auto;
    }
    .att-col-num  { width: 16px; text-align: center; }
    .att-col-name { min-width: 80px; text-align: left; }
    .att-col-day  { width: 14px; text-align: center; white-space: nowrap; }
    .att-col-sum  { min-width: 45px; text-align: left; white-space: nowrap; }

    .att-name-main { font-weight: bold; font-size: 7.5px; }
    .att-name-doc  { color: #64748b; font-size: 6.5px; }

    .att-code {
      font-size: 6.5px;
      font-weight: bold;
      border-radius: 2px;
      display: block;
      text-align: center;
      padding: 1px 0;
    }

    .att-summary-line {
      font-size: 6.5px;
      line-height: 1.4;
    }
    .att-summary-code { font-weight: bold; }

    /* Month header row inside attendance table */
    .att-month-th {
      background: #1e3a8a;
      color: #bfdbfe;
      font-size: 7px;
      text-align: center;
      padding: 2px;
    }

    /* ── Sheet 2 – Calculation detail ──────────────────────── */
    .detail-table td.td-name { text-align: left; font-weight: bold; }
    .detail-table td.td-num  { text-align: right; }
    .detail-table td.td-unit { text-align: center; }

    /* ── Sheet 3 – Summary ────────────────────────────────── */
    .sum-table td.td-left  { text-align: left; }
    .sum-table td.td-right { text-align: right; }
    .sum-table td.td-center{ text-align: center; }

    /* totals highlight in summary */
    .sum-table tbody tr.totals-row td {
      background: #eff6ff;
      font-weight: bold;
      border-top: 2px solid #1e40af;
    }
  </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════
     HOJA 1 · ASISTENCIAS DEL PERÍODO
══════════════════════════════════════════════════════ --}}
<div class="sheet">
  <div class="sheet-header">
    <div class="sheet-title">Asistencias del Período</div>
    <div class="sheet-meta">
      <span><strong>Empresa:</strong> {{ $companyName }}</span>
      <span><strong>Período:</strong> {{ $periodLabel }}</span>
      @if($biweeklyLabel)
        <span><strong>Quincena:</strong> {{ $biweeklyLabel }}</span>
      @endif
      <span><strong>Trabajadores:</strong> {{ $attendanceData['total_workers'] ?? count($attendanceData['attendances']) }}</span>
    </div>
  </div>

  @if(empty($attendanceData['attendances']))
    <p style="color:#64748b;font-size:8px;margin-top:8px;">No hay registros de asistencia para este período.</p>
  @else
    @php
      // Build day-number labels from the date list
      $dayLabels = array_map(fn($d) => (int)\Carbon\Carbon::parse($d)->format('j'), $allDates);
    @endphp
    <table class="data-table att-table">
      <thead>
        <tr>
          <th class="att-col-num">#</th>
          <th class="att-col-name">Colaborador</th>
          @foreach($dayLabels as $label)
            <th class="att-col-day">{{ $label }}</th>
          @endforeach
          <th class="att-col-sum">Resumen</th>
        </tr>
      </thead>
      <tbody>
        @foreach($attendanceData['attendances'] as $i => $worker)
          @php
            $dailyMap = collect($worker['daily_attendances'])->keyBy('date');
          @endphp
          <tr>
            <td class="att-col-num" style="text-align:center;">{{ $i + 1 }}</td>
            <td class="att-col-name">
              <span class="att-name-main">{{ $worker['worker_name'] }}</span><br>
              <span class="att-name-doc">{{ $worker['document_number'] }}</span>
            </td>
            @foreach($allDates as $date)
              @php
                $att  = $dailyMap[$date] ?? null;
                $code = $att ? ($att['code'] ?? '') : '';
                $bg   = $code ? attendanceColor($code) : '#ffffff';
              @endphp
              <td class="att-col-day" style="background:{{ $bg }};padding:1px;">
                @if($code)
                  <span class="att-code" style="background:{{ $bg }};">{{ $code }}</span>
                @endif
              </td>
            @endforeach
            <td class="att-col-sum" style="padding:3px 4px;">
              @foreach($worker['summary']['codes'] as $code => $count)
                <span class="att-summary-line">
                  <span class="att-summary-code">{{ $code }}</span>: {{ $count }}
                </span><br>
              @endforeach
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>


{{-- ══════════════════════════════════════════════════════
     HOJA 2 · DETALLES DE CÁLCULO
══════════════════════════════════════════════════════ --}}
<div class="sheet">
  <div class="sheet-header">
    <div class="sheet-title">Detalles de Cálculo</div>
    <div class="sheet-meta">
      <span><strong>Empresa:</strong> {{ $companyName }}</span>
      <span><strong>Período:</strong> {{ $periodLabel }}</span>
      @if($biweeklyLabel)
        <span><strong>Quincena:</strong> {{ $biweeklyLabel }}</span>
      @endif
      <span><strong>Trabajadores:</strong> {{ $calcSheet2['total_workers'] }}</span>
    </div>
  </div>

  @if(empty($calcSheet2['rows']) || $calcSheet2['rows']->isEmpty())
    <p style="color:#64748b;font-size:8px;margin-top:8px;">No hay cálculos registrados para este período.</p>
  @else
    <table class="data-table detail-table" style="margin-top:4px;">
      <thead>
        <tr>
          <th style="text-align:left;width:35%;">Trabajador</th>
          <th style="text-align:right;width:18%;">Sueldo</th>
          <th style="text-align:center;width:10%;">Jornada</th>
          <th style="text-align:right;width:17%;">Valor / Hora</th>
          <th style="text-align:right;width:20%;">Total Neto</th>
        </tr>
      </thead>
      <tbody>
        @foreach($calcSheet2['rows'] as $row)
          <tr>
            <td class="td-name">{{ $row['nombre'] }}</td>
            <td class="td-num">S/ {{ number_format($row['salary'], 2) }}</td>
            <td class="td-unit">{{ number_format($row['shift_hours'], 0) }}h</td>
            <td class="td-num">S/ {{ number_format($row['base_hour_value'], 2) }}</td>
            <td class="td-num">S/ {{ number_format($row['net_salary'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align:right;">
            Total General ({{ $calcSheet2['total_workers'] }} trabajador{{ $calcSheet2['total_workers'] != 1 ? 'es' : '' }})
          </td>
          <td style="text-align:right;">S/ {{ number_format($calcSheet2['total_net'], 2) }}</td>
        </tr>
      </tfoot>
    </table>
  @endif
</div>


{{-- ══════════════════════════════════════════════════════
     HOJA 3 · RESUMEN DE NÓMINA
══════════════════════════════════════════════════════ --}}
<div class="sheet">
  <div class="sheet-header">
    <div class="sheet-title">Resumen de Nómina &mdash; {{ $periodLabel }}</div>
    <div class="sheet-meta">
      <span><strong>Empresa:</strong> {{ $companyName }}</span>
      @if($biweeklyLabel)
        <span><strong>Quincena:</strong> {{ $biweeklyLabel }}</span>
      @endif
    </div>
  </div>

  @if(empty($summaryData['rows']) || (is_object($summaryData['rows']) ? $summaryData['rows']->isEmpty() : empty($summaryData['rows'])))
    <p style="color:#64748b;font-size:8px;margin-top:8px;">No hay datos de nómina para este período.</p>
  @else
    @php $totals = $summaryData['totals']; @endphp
    <table class="data-table sum-table" style="margin-top:4px;">
      <thead>
        <tr>
          <th style="text-align:left;width:20%;">Nombre</th>
          <th style="width:7%;">DNI</th>
          <th style="width:4%;">Días T</th>
          <th style="text-align:right;width:8%;">Básico</th>
          <th style="text-align:right;width:8%;">Bono Noc.</th>
          <th style="text-align:right;width:8%;">Bruto</th>
          <th style="text-align:right;width:8%;">HH.EE 25%</th>
          <th style="text-align:right;width:8%;">HH.EE 35%</th>
          <th style="text-align:right;width:8%;">Feriados</th>
          <th style="text-align:right;width:8%;">Descansos</th>
          <th style="text-align:right;width:9%;">Neto</th>
          @if($hasVacation)
            <th style="width:4%;">Días V</th>
            <th style="text-align:right;width:7%;">Val. D.Vac.</th>
            <th style="text-align:right;width:8%;">Monto Vac.</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @foreach($summaryData['rows'] as $row)
          <tr>
            <td class="td-left" style="font-weight:bold;">{{ $row['nombre'] }}</td>
            <td class="td-center">{{ $row['dni'] }}</td>
            <td class="td-center">{{ $row['days_worked'] }}</td>
            <td class="td-right">S/ {{ number_format($row['basic_salary'], 2) }}</td>
            <td class="td-right">S/ {{ number_format($row['night_bonus'], 2) }}</td>
            <td class="td-right">S/ {{ number_format($row['gross_salary'], 2) }}</td>
            <td class="td-right">S/ {{ number_format($row['overtime_25'], 2) }}</td>
            <td class="td-right">S/ {{ number_format($row['overtime_35'], 2) }}</td>
            <td class="td-right">S/ {{ number_format($row['holiday_pay'], 2) }}</td>
            <td class="td-right">S/ {{ number_format($row['compensatory_pay'], 2) }}</td>
            <td class="td-right" style="font-weight:bold;">S/ {{ number_format($row['net_salary'], 2) }}</td>
            @if($hasVacation)
              <td class="td-center">{{ $row['days_vacation'] ?? 0 }}</td>
              <td class="td-right">S/ {{ number_format($row['vacation_hour_value'] ?? 0, 2) }}</td>
              <td class="td-right">S/ {{ number_format($row['vacation_amount'] ?? 0, 2) }}</td>
            @endif
          </tr>
        @endforeach

        {{-- Totals row (not in tfoot so it stays with the data) --}}
        <tr class="totals-row">
          <td class="td-left" colspan="2">Totales ({{ count($summaryData['rows']) }} trabajador{{ count($summaryData['rows']) != 1 ? 'es' : '' }})</td>
          <td class="td-center">{{ $totals['days_worked'] }}</td>
          <td class="td-right">S/ {{ number_format($totals['basic_salary'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['night_bonus'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['gross_salary'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['overtime_25'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['overtime_35'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['holiday_pay'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['compensatory_pay'], 2) }}</td>
          <td class="td-right">S/ {{ number_format($totals['net_salary'], 2) }}</td>
          @if($hasVacation)
            <td class="td-center">{{ $totals['days_vacation'] ?? 0 }}</td>
            <td class="td-right">S/ {{ number_format($totals['vacation_hour_value'] ?? 0, 2) }}</td>
            <td class="td-right">S/ {{ number_format($totals['vacation_amount'] ?? 0, 2) }}</td>
          @endif
        </tr>
      </tbody>
    </table>
  @endif
</div>

</body>
</html>
