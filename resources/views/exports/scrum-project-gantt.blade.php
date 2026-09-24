<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gantt - {{ $project['name'] }}</title>
  <style>
    @page {
      margin: 3mm 5mm 6mm 5mm;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 7px;
      color: #262626;
      margin: 0;
      padding: 0;
    }

    .header {
      text-align: left;
      width: 410mm;
      margin-bottom: 5px;
      border-bottom: 1px solid #bfbfbf;
      padding-bottom: 4px;
    }

    .header h1 {
      margin: 0;
      font-size: 14px;
      color: #000000;
    }

    .legend {
      margin-bottom: 5px;
      font-size: 7px;
      color: #404040;
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

    /* width en mm exactos (nunca 100%): dompdf calculaba mal el 100% de la
       tabla contra el ancho de página real de A3 horizontal, lo que
       terminaba estirando todas las columnas (sobre todo Inicio/Fin) muy
       por encima de su ancho declarado y empujaba la línea de tiempo fuera
       de la página, generando una página 2 en blanco. */
    table.gantt-table {
      width: 410mm;
      border-collapse: collapse;
      table-layout: fixed;
      border-top: 1px solid #bfbfbf;
    }

    .gantt-table th, .gantt-table td {
      border: none;
      border-right: 1px solid #e4e4e4;
      padding: 3px 5px;
      text-align: left;
      font-size: 6px;
      vertical-align: middle;
    }

    .gantt-table thead th {
      border-bottom: 1px solid #bfbfbf;
      font-weight: normal;
    }

    .gantt-table thead .col-wbs,
    .gantt-table thead .col-name,
    .gantt-table thead .col-date,
    .gantt-table thead .col-duration,
    .gantt-table thead .col-status,
    .gantt-table thead .col-predecessor {
      background-color: #f7f7f7;
    }

    /* Horizontal solo en las columnas de datos de la tabla; las celdas del
       gráfico (col-month) nunca llevan border-bottom para que la línea
       vertical de cada mes baje sin cortes ni intersecciones con las
       horizontales. dompdf no soporta bien :not(), por eso se pone el
       border-bottom en todas las celdas del body y se anula explícitamente
       en .col-month más abajo (esa regla, al ser más específica, gana). */
    .gantt-table tbody td {
      border-bottom: 1px solid #e4e4e4;
    }

    /* % y no mm: dompdf con table-layout:fixed directamente IGNORA anchos
       absolutos (mm/px/colgroup) en th/td — su Cellmap.php fuerza ese valor
       a 0 para columnas fixed-layout y solo respeta porcentajes. Por eso
       aunque el mm sumara exacto 410mm, la tabla igual repartía el ancho
       según el contenido. Los % de abajo sí son leídos y respetados, y
       sumados dan 410mm dentro de la tabla (100%). */
    .col-wbs { width: 2.439%; text-align: left !important; padding-left: 4px !important; color: #808080; }
    /* Nunca nowrap aquí: dompdf no siempre recorta el contenido que se pasa
       del ancho declarado en table-layout:fixed, y eso terminaba empujando
       toda la columna (y la línea de tiempo) fuera de su lugar. Con
       word-break cualquier texto, incluso una palabra sin espacios, se
       parte dentro del ancho fijo en vez de expandirlo. Sin overflow:hidden
       a propósito: el nombre de la tarea nunca debe truncarse, la fila
       crece en alto lo que haga falta para mostrarlo completo. */
    .col-name { width: 10.976%; word-break: break-all; overflow-wrap: break-word; }
    .col-date { width: 3.171%; text-align: center !important; }
    .col-duration { width: 2.195%; text-align: center !important; }
    .col-status { width: 3.415%; text-align: center !important; word-break: break-all; }
    .col-predecessor { width: 2.439%; text-align: center !important; color: #808080; }
    /* Cabecera de años/meses: celdas <th> reales con el mismo % de ancho
       que las .col-month del cuerpo (ver más abajo), en vez de divs con
       left/width en mm superpuestos sobre una sola celda ancha. Antes el
       overlay en mm y las columnas de la tabla (en %) se calculaban por
       caminos separados dentro de dompdf y el redondeo de cada uno iba
       divergiendo mes a mes, así que las líneas verticales del gráfico ya
       no coincidían con los límites de mes/año de la cabecera. Compartir
       la misma grilla de columnas para cabecera y cuerpo garantiza que
       coincidan siempre, sin importar el redondeo. */
    /* .gantt-table th ya trae text-align:left (para los encabezados de
       datos) con especificidad (clase+elemento) más alta que una sola
       clase — por eso el centrado va calificado como ".gantt-table
       th.col-year" / "...th.col-month-header", si no el left de esa
       regla general siempre gana. */
    .gantt-table th.col-year {
      text-align: center;
      font-weight: normal;
      color: #000000;
      background-color: transparent;
    }

    .gantt-table th.col-month-header {
      padding: 0 !important;
      text-align: center;
      font-weight: normal;
      color: #404040;
      background-color: transparent;
      position: relative;
      white-space: nowrap;
    }

    /* Cuando el mes de "hoy" se parte en dos <th>, el nombre del mes se
       imprime en el primero pero centrado sobre el ancho completo del mes
       (mm), no solo sobre su porción recortada — igual técnica que la
       barra: un elemento position:absolute que se pinta encima del <th>
       siguiente sin problema. Si no, el texto quedaba pegado a la
       izquierda y el segundo <th> (vacío) se veía como un mes extra sin
       nombre en vez de la misma columna partida por la línea de hoy. */
    .month-label-overlay {
      position: absolute;
      left: 0;
      top: 0;
      text-align: center;
      white-space: nowrap;
    }

    .hoy-label {
      position: absolute;
      top: -8px;
      left: 1px;
      font-size: 5.5px;
      font-weight: bold;
      color: #FFB6B6;
      white-space: nowrap;
    }

    /* Cada mes es un <td> real (no un div absoluto dentro de un único
       td ancho): un border-left en un td SIEMPRE respeta el alto real de
       la fila (lo estira la tabla, sin depender de % ni de height:100%),
       mientras que un div hijo con height:100%/position:absolute dentro
       de una celda de alto automático colapsaba a 0 en dompdf y dejaba
       las líneas verticales "punteadas" cuando el nombre de la tarea
       ocupaba varias líneas. La barra sigue viviendo en mm dentro del
       primer <td class="col-month">, y su position:absolute puede
       pintarse por encima de los <td> siguientes sin problema aunque su
       ancho layout real sea solo el de esa primera celda. */
    /* Solo border-left (nunca border-right, que ya trae .gantt-table td por
       defecto): con border-collapse + table-layout:fixed dompdf no fusiona
       bien el border-right de una celda con el border-left de la
       siguiente y pintaba doble línea en cada límite de mes. */
    .col-month, .col-month-header { padding: 0 !important; border-left: 1px solid #e4e4e4; border-right: none; position: relative; }
    /* Selector más específico que ".gantt-table tbody td" (el de arriba)
       para que gane y anule su border-bottom en las celdas del gráfico. */
    .gantt-table tbody td.col-month { border-bottom: none; }
    .col-month:first-child, .col-month-header:first-child { border-left: none; }

    /* Línea de "hoy" dentro del cuerpo: misma técnica que .col-month (una
       celda real partida justo en la fecha de hoy dentro del mes que la
       contiene) para que el border-left estire correcto hasta el fondo de
       cada fila sin importar cuánto crezca por texto envuelto. */
    .gantt-table td.today-col, .gantt-table th.today-col {
      border-left: 1.5px solid #FFB6B6 !important;
    }

    tr.level-0 .col-name { font-weight: bold; }
    tr.level-1 .col-name { font-weight: bold; padding-left: 5px !important; }
    tr.level-2 .col-name { padding-left: 10px !important; color: #404040; }
    tr.level-2 .col-name::before { content: "- "; color: #a5a5a5; }

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
      z-index: 2;
    }

    .bar.level-0 {
      top: 3px;
      height: 4px;
      background-color: #207de8;
      border-radius: 1px;
      z-index: 2;
    }

    .bar-label {
      position: absolute;
      top: -0.5px;
      left: 100%;
      margin-left: 3px;
      font-size: 5.5px;
      color: #404040;
      white-space: nowrap;
    }

    /* Sin border-top propio: la última fila de la tabla ya cierra con su
       border-bottom, y un border-top acá quedaba pegado a esa línea
       pintando un doble borde inferior. */
    .footer {
      margin-top: 6px;
      color: #808080;
      font-size: 6.5px;
      display: table;
      width: 100%;
    }

    .footer .footer-left,
    .footer .footer-right {
      display: table-cell;
    }

    .footer .footer-right {
      text-align: right;
    }
  </style>
</head>
<body>
<div class="header">
  <h1>{{ $project['name'] }} | Gantt</h1>
</div>

<div class="legend">
  <span><i style="background-color:#207de8;"></i>Sprint</span>
  <span><i style="background-color:#4fc7d5;"></i>Backlog</span>
  <span><i style="background-color:#9d9d9d;"></i>Por hacer</span>
  <span><i style="background-color:#ffc000;"></i>En progreso</span>
  <span><i style="background-color:#d860ba;"></i>En revisión</span>
  <span><i style="background-color:#81c783;"></i>Hecho</span>
</div>

@php
  $statusColors = [
    'backlog' => '#4fc7d5',
    'por_hacer' => '#9d9d9d',
    'en_progreso' => '#ffc000',
    'en_revision' => '#d860ba',
    'hecho' => '#81c783',
  ];

  // % de cada mes respecto al ancho total de la tabla (410mm), para que
  // cada mes sea un <td> real dentro de la fila (ver .col-month arriba).
  $totalTableMm = 410;

  // El mes que contiene "hoy" se parte en dos <td> reales (antes/después de
  // la fecha) para poder pintar la línea de hoy como border-left de una
  // celda real: igual que con .col-month, eso es lo único que en dompdf
  // estira correcto hasta el fondo de cada fila sin importar el alto real
  // que termine teniendo (texto envuelto, etc). Se calcula una sola vez y
  // se aplica igual en TODAS las filas para que cada fila siga teniendo el
  // mismo número de columnas (table-layout:fixed lo requiere).
  $todayMonthIndex = null;
  $todayOffsetInMonth = null;
  if ($todayMm !== null) {
    foreach ($months as $idx => $month) {
      if ($todayMm >= $month['left'] && $todayMm <= $month['left'] + $month['width']) {
        $todayMonthIndex = $idx;
        $todayOffsetInMonth = max(0.1, $todayMm - $month['left']);
        break;
      }
    }
  }
  // Cabecera de años: se recalcula acá (en vez de usar $years, calculado
  // en el service) para que el colspan de cada año cuente exactamente las
  // mismas columnas <th> que se van a imprimir por mes más abajo,
  // incluyendo la columna extra que se inserta al partir el mes de "hoy".
  // Así año/mes/cuerpo comparten siempre la misma grilla de columnas.
  $yearColumns = [];
  foreach ($months as $idx => $month) {
    $y = $month['year'];
    if (!isset($yearColumns[$y])) {
      $yearColumns[$y] = ['year' => $y, 'colspan' => 0];
    }
    $yearColumns[$y]['colspan'] += ($todayMonthIndex === $idx) ? 2 : 1;
  }
  $yearColumns = array_values($yearColumns);
@endphp

<table class="gantt-table">
  <thead>
  <tr>
    <th class="col-wbs" rowspan="2">#</th>
    <th class="col-name" rowspan="2">Nombre de la tarea</th>
    <th class="col-date" rowspan="2">Inicio</th>
    <th class="col-date" rowspan="2">Fin</th>
    <th class="col-duration" rowspan="2">Dur.</th>
    <th class="col-status" rowspan="2">Estado</th>
    <th class="col-predecessor" rowspan="2">Predec.</th>
    @foreach ($yearColumns as $year)
      <th class="col-year" colspan="{{ $year['colspan'] }}">{{ $year['year'] }}</th>
    @endforeach
  </tr>
  <tr>
    @foreach ($months as $i => $month)
      @if ($todayMonthIndex === $i)
        <th class="col-month-header" style="width: {{ $todayOffsetInMonth / $totalTableMm * 100 }}%;">
          <span class="month-label-overlay" style="width: {{ $month['width'] }}mm;">{{ $month['label'] }}</span>
          <span class="hoy-label">Hoy</span>
        </th>
        <th class="col-month-header today-col" style="width: {{ ($month['width'] - $todayOffsetInMonth) / $totalTableMm * 100 }}%;"></th>
      @else
        <th class="col-month-header" style="width: {{ $month['width'] / $totalTableMm * 100 }}%;">{{ $month['label'] }}</th>
      @endif
    @endforeach
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
      @foreach ($months as $i => $month)
        @php
          $cellContent = null;
          if ($i === 0) {
            $cellContent = '<div class="timeline-row">';
            if ($row['start'] && $row['end']) {
              $barStyle = 'left: ' . $row['left'] . 'mm; width: ' . $row['width'] . 'mm; ' . ($row['level'] === 0 ? '' : 'background-color: ' . ($statusColors[$row['status'] ?? ''] ?? '#94a3b8') . ';');
              $cellContent .= '<div class="bar level-' . $row['level'] . '" style="' . $barStyle . '"></div>';
            }
            $cellContent .= '</div>';
          }
        @endphp
        @if ($todayMonthIndex === $i)
          <td class="col-month" style="width: {{ $todayOffsetInMonth / $totalTableMm * 100 }}%;">{!! $cellContent !!}</td>
          <td class="col-month today-col" style="width: {{ ($month['width'] - $todayOffsetInMonth) / $totalTableMm * 100 }}%;"></td>
        @else
          <td class="col-month" style="width: {{ $month['width'] / $totalTableMm * 100 }}%;">{!! $cellContent !!}</td>
        @endif
      @endforeach
    </tr>
  @endforeach
  </tbody>
</table>

<div class="footer">
  <span class="footer-left">Rango: {{ $range['start'] ?? '-' }} a {{ $range['end'] ?? '-' }}</span>
  <span class="footer-right">Generado el {{ \Illuminate\Support\Carbon::parse($generatedAt)->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
