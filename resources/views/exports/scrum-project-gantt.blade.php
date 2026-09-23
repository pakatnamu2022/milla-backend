<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gantt - {{ $project['name'] }}</title>
  <style>
    @page {
      margin: 6mm 5mm;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 7px;
      color: #1f2937;
      margin: 0;
      padding: 0;
    }

    .header {
      text-align: center;
      margin-bottom: 5px;
      border-bottom: 2px solid #6366f1;
      padding-bottom: 4px;
    }

    .header h1 {
      margin: 0 0 2px 0;
      font-size: 14px;
      color: #1f2937;
    }

    .header p {
      margin: 0;
      font-size: 8px;
      color: #6b7280;
    }

    .legend {
      margin-bottom: 5px;
      font-size: 7px;
      color: #374151;
    }

    .legend span {
      display: inline-block;
      margin-right: 10px;
    }

    .legend i {
      display: inline-block;
      width: 7px;
      height: 7px;
      margin-right: 3px;
      vertical-align: middle;
    }

    table.gantt-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .gantt-table th, .gantt-table td {
      border: 1px solid #e5e7eb;
      padding: 1px 3px;
      text-align: left;
      font-size: 6px;
      vertical-align: middle;
    }

    .col-wbs { width: 10mm; text-align: center !important; color: #6b7280; }
    .col-name { width: 45mm; white-space: nowrap; overflow: hidden; }
    .col-date { width: 13mm; text-align: center !important; }
    .col-duration { width: 9mm; text-align: center !important; }
    .col-status { width: 14mm; text-align: center !important; }
    .col-predecessor { width: 10mm; text-align: center !important; color: #6b7280; }
    /* table-layout:fixed en dompdf no reparte el ancho sobrante a una
       columna sin width explícito: se fija el resto del ancho utilizable
       de la página (A3 horizontal, 410mm - 5mm de margen a cada lado,
       menos la suma de las columnas de la izquierda). */
    /* OJO: dompdf no calcula bien los % de position:absolute cuando el
       contexto posicionado es la celda de tabla (th/td) misma — usa un
       ancho de referencia equivocado y el contenido termina fuera de la
       celda. Por eso el contexto posicionado (position:relative) va en un
       <div> normal DENTRO de la celda, nunca en el td/th directamente. */
    .col-timeline { padding: 0 !important; width: 296mm; }

    tr.level-0 .col-name { font-weight: bold; }
    tr.level-1 .col-name { font-weight: bold; padding-left: 5px !important; }
    tr.level-2 .col-name { padding-left: 10px !important; color: #374151; }
    tr.level-2 .col-name::before { content: "↳ "; color: #9ca3af; }

    tr.level-0 td { background-color: #eef2ff; }

    .timeline-row {
      position: relative;
      width: 296mm;
      height: 10px;
    }

    .bar {
      position: absolute;
      top: 1px;
      height: 8px;
      border-radius: 2px;
    }

    .bar.level-0 {
      top: 3px;
      height: 4px;
      background-color: #4338ca;
      border-radius: 1px;
    }

    .bar-label {
      position: absolute;
      top: -0.5px;
      left: 100%;
      margin-left: 3px;
      font-size: 5.5px;
      color: #374151;
      white-space: nowrap;
    }

    .header-timeline {
      position: relative;
      width: 296mm;
      height: 11px;
    }

    .header-cell {
      position: absolute;
      top: 0;
      height: 100%;
      border-left: 1px solid #d1d5db;
      font-size: 6px;
      font-weight: bold;
      color: #374151;
      padding-left: 2px;
      box-sizing: border-box;
      overflow: hidden;
      white-space: nowrap;
    }

    .header-cell.year-cell {
      background-color: #eef2ff;
      color: #4338ca;
    }

    .today-flag {
      position: absolute;
      top: 0;
      height: 100%;
      border-left: 1.5px solid #ef4444;
    }

    .today-flag .flag-label {
      position: absolute;
      top: -8px;
      left: 1px;
      font-size: 5.5px;
      font-weight: bold;
      color: #ef4444;
      white-space: nowrap;
    }

    .footer {
      margin-top: 5px;
      padding-top: 3px;
      border-top: 1px solid #e5e7eb;
      text-align: center;
      color: #9ca3af;
      font-size: 6.5px;
    }
  </style>
</head>
<body>
<div class="header">
  <h1>Diagrama de Gantt · {{ $project['name'] }}</h1>
  <p>
    Rango: {{ $range['start'] ?? '-' }} a {{ $range['end'] ?? '-' }}
    &nbsp;|&nbsp; Generado el {{ \Illuminate\Support\Carbon::parse($generatedAt)->format('d/m/Y H:i') }}
  </p>
</div>

<div class="legend">
  <span><i style="background-color:#4338ca;"></i>Sprint</span>
  <span><i style="background-color:#94a3b8;"></i>Backlog</span>
  <span><i style="background-color:#60a5fa;"></i>Por hacer</span>
  <span><i style="background-color:#fbbf24;"></i>En progreso</span>
  <span><i style="background-color:#c084fc;"></i>En revisión</span>
  <span><i style="background-color:#34d399;"></i>Hecho</span>
</div>

@php
  $statusColors = [
    'backlog' => '#94a3b8',
    'por_hacer' => '#60a5fa',
    'en_progreso' => '#fbbf24',
    'en_revision' => '#c084fc',
    'hecho' => '#34d399',
  ];
@endphp

<table class="gantt-table">
  <thead>
  <tr>
    <th class="col-wbs">#</th>
    <th class="col-name">Nombre de la tarea</th>
    <th class="col-date">Inicio</th>
    <th class="col-date">Fin</th>
    <th class="col-duration">Dur.</th>
    <th class="col-status">Estado</th>
    <th class="col-predecessor">Predec.</th>
    <th class="col-timeline">
      <div class="header-timeline">
        @foreach ($years as $year)
          <div class="header-cell year-cell" style="left: {{ $year['left'] }}mm; width: {{ $year['width'] }}mm;">{{ $year['year'] }}</div>
        @endforeach
        @if ($todayMm !== null)
          <div class="today-flag" style="left: {{ $todayMm }}mm;"><span class="flag-label">Hoy</span></div>
        @endif
      </div>
      <div class="header-timeline">
        @foreach ($months as $month)
          <div class="header-cell" style="left: {{ $month['left'] }}mm; width: {{ $month['width'] }}mm;">{{ $month['label'] }}</div>
        @endforeach
        @if ($todayMm !== null)
          <div class="today-flag" style="left: {{ $todayMm }}mm;"></div>
        @endif
      </div>
    </th>
  </tr>
  </thead>
  <tbody>
  @foreach ($rows as $row)
    <tr class="level-{{ $row['level'] }}">
      <td class="col-wbs">{{ $row['wbs'] }}</td>
      <td class="col-name">{{ $row['title'] }}</td>
      <td class="col-date">{{ $row['start']?->format('d/m/y') ?? '-' }}</td>
      <td class="col-date">{{ $row['end']?->format('d/m/y') ?? '-' }}</td>
      <td class="col-duration">
        @if ($row['start'] && $row['end'])
          {{ $row['start']->diffInDays($row['end']) + 1 }}d
        @else
          -
        @endif
      </td>
      <td class="col-status">{{ $row['status_label'] }}</td>
      <td class="col-predecessor">{{ $row['predecessor'] ?? '-' }}</td>
      <td class="col-timeline">
        <div class="timeline-row">
          @if ($row['start'] && $row['end'])
            <div
              class="bar level-{{ $row['level'] }}"
              style="left: {{ $row['left'] }}mm; width: {{ $row['width'] }}mm; {{ $row['level'] === 0 ? '' : 'background-color: ' . ($statusColors[$row['status'] ?? ''] ?? '#94a3b8') . ';' }}"
            ></div>
          @endif
        </div>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>

<div class="footer">
  <p>Mejora de Procesos AP &middot; Diagrama de Gantt generado desde el sistema</p>
</div>
</body>
</html>
