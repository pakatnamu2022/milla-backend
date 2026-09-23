<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gantt - {{ $project['name'] }}</title>
  <style>
    @page {
      margin: 8mm 6mm;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 8px;
      color: #1f2937;
      margin: 0;
      padding: 0;
    }

    .header {
      text-align: center;
      margin-bottom: 10px;
      border-bottom: 2px solid #6366f1;
      padding-bottom: 6px;
    }

    .header h1 {
      margin: 0 0 3px 0;
      font-size: 16px;
      color: #1f2937;
    }

    .header p {
      margin: 0;
      font-size: 9px;
      color: #6b7280;
    }

    .legend {
      margin-bottom: 10px;
      font-size: 8px;
      color: #374151;
    }

    .legend span {
      display: inline-block;
      margin-right: 12px;
    }

    .legend i {
      display: inline-block;
      width: 8px;
      height: 8px;
      margin-right: 3px;
      vertical-align: middle;
    }

    .sprint-block {
      margin-bottom: 14px;
      page-break-inside: avoid;
    }

    .sprint-title {
      font-size: 10px;
      font-weight: bold;
      padding: 4px 6px;
      color: #ffffff;
      border-radius: 2px 2px 0 0;
    }

    .sprint-title.dev { background-color: #6366f1; }
    .sprint-title.test { background-color: #f59e0b; }

    .sprint-title small {
      font-weight: normal;
      font-size: 8px;
      opacity: .9;
    }

    table.gantt-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .gantt-table th, .gantt-table td {
      border: 1px solid #e5e7eb;
      padding: 1px;
      text-align: center;
      font-size: 6.5px;
    }

    .col-title {
      width: 150px;
      text-align: left !important;
      padding: 2px 4px !important;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .col-title.historia {
      font-weight: bold;
      background-color: #f8fafc;
    }

    .col-title.tarea {
      padding-left: 12px !important;
      color: #4b5563;
    }

    .day-cell {
      height: 12px;
    }

    .bar {
      height: 8px;
      border-radius: 2px;
    }

    .footer {
      margin-top: 8px;
      padding-top: 4px;
      border-top: 1px solid #e5e7eb;
      text-align: center;
      color: #9ca3af;
      font-size: 7px;
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

@foreach ($sprints as $sprint)
  <div class="sprint-block">
    <div class="sprint-title {{ $sprint['kind'] }}">
      {{ $sprint['name'] }}
      <small>({{ $sprint['start_date'] }} a {{ $sprint['end_date'] }} · {{ ucfirst($sprint['status']) }})</small>
    </div>

    @if (count($sprint['items']) === 0)
      <table class="gantt-table">
        <tr><td class="col-title" style="width:auto;">Sin items con fecha en este sprint</td></tr>
      </table>
    @else
      <table class="gantt-table">
        <thead>
        <tr>
          <th class="col-title">Item</th>
          @foreach ($sprint['days'] as $day)
            <th>{{ $day->format('d') }}<br>{{ $day->translatedFormat('D') }}</th>
          @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($sprint['items'] as $item)
          <tr>
            <td class="col-title {{ $item['type'] }}">
              {{ $item['type'] === 'tarea' ? '↳ ' : '' }}{{ $item['title'] }}
            </td>
            @foreach ($sprint['days'] as $index => $day)
              <td class="day-cell">
                @if ($index >= $item['offset'] && $index < $item['offset'] + $item['span'])
                  <div class="bar" style="background-color: {{ $statusColors[$item['status']] ?? '#94a3b8' }};"></div>
                @endif
              </td>
            @endforeach
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endforeach

<div class="footer">
  <p>Mejora de Procesos AP &middot; Diagrama de Gantt generado desde el sistema</p>
</div>
</body>
</html>
