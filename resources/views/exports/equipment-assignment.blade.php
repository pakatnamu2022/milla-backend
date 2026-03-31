@php
  function getBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType  = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }
@endphp

  <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acta de Asignación de Equipos</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      color: #22293a;
      background: #ffffff;
      margin: 0;
      padding: 0 0 30px;
    }

    .page {
      background: #ffffff;
      padding: 4px 8px 6px;
      margin: 0 auto;
    }

    .card {
      border: 1px solid #cccccc;
      border-radius: 4px;
      overflow: hidden;
      margin-bottom: 8px;
      background: #ffffff;
    }

    .card-title {
      background: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10.5px;
      letter-spacing: 0.3px;
      padding: 4px 12px;
      border-bottom: 1px solid #d2d2d2;
    }

    /* ─── HEADER ─────────────────────────────────── */
    .page-header {
      margin-bottom: 8px;
    }

    .header-inner {
      display: table;
      width: 100%;
      border: 1.5px solid #e0e0e0;
      border-radius: 5px;
      overflow: hidden;
    }

    .h-logo {
      display: table-cell;
      width: auto;
      text-align: center;
      vertical-align: middle;
      padding: 5px 10px;
      border-right: 1px solid #e0e0e0;
    }

    .h-logo img {
      max-width: 300px;
      height: 50px;
      width: auto;
      display: block;
    }

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

    .h-title-sub {
      font-size: 8.5px;
      color: #888888;
      margin-top: 2px;
    }

    .h-meta {
      display: table-cell;
      width: 140px;
      vertical-align: middle;
      background: #e0e0e0;
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
      font-size: 13px;
      font-weight: bold;
      color: #22293a;
      white-space: nowrap;
      margin-top: 2px;
    }

    .h-meta-date {
      font-size: 8.5px;
      color: #555555;
      margin-top: 3px;
    }

    /* ══ DATOS DEL TRABAJADOR ══ */
    .worker-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin-bottom: 0;
    }

    .worker-table td {
      padding: 5px 10px;
      vertical-align: top;
      border-right: 1px solid #e8e8e8;
      border-bottom: 1px solid #e8e8e8;
    }

    .worker-table td:last-child {
      border-right: none;
    }

    .worker-table tr:last-child td {
      border-bottom: none;
    }

    .field-label {
      font-size: 8px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      color: #777777;
      display: block;
      margin-bottom: 4px;
    }

    .field-value {
      font-size: 10px;
      color: #1a1a1a;
      display: block;
    }

    .field-value.empty {
      color: #999999;
      font-style: italic;
    }

    /* ══ TABLA EQUIPOS ══ */
    .equipment-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin-bottom: 0;
      font-size: 10px;
    }

    .equipment-table thead {
      background: #f8f8f8;
    }

    .equipment-table th {
      border: none;
      border-right: 1px solid #d5d5d5;
      border-bottom: 2px solid #d5d5d5;
      padding: 5px 8px;
      text-align: left;
      font-size: 9px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      color: #333333;
    }

    .equipment-table td {
      border: none;
      border-right: 1px solid #e8e8e8;
      border-bottom: 1px solid #e8e8e8;
      padding: 5px 8px;
      color: #1a1a1a;
      font-size: 10px;
    }

    .equipment-table th:last-child,
    .equipment-table td:last-child {
      border-right: none;
    }

    .equipment-table tbody tr:last-child td {
      border-bottom: none;
    }

    .equipment-table tbody tr:nth-child(even) {
      background: #fcfcfc;
    }

    .eq-num {
      text-align: center;
      font-size: 10px;
    }

    .eq-serial {
      font-size: 10px;
      color: #666666;
    }

    /* ══ OBSERVACIONES ══ */
    .obs-section {
      margin-bottom: 0;
      padding: 7px 12px;
    }

    .obs-text {
      font-size: 10px;
      line-height: 1.6;
      color: #2a2a2a;
    }

    .obs-text p {
      margin-bottom: 8px;
    }

    .obs-text p:last-child {
      margin-bottom: 0;
    }

    /* ══ DECLARACIÓN ══ */
    .decl-section {
      margin-bottom: 0;
      padding: 7px 12px;
    }

    .decl-text {
      font-size: 10px;
      line-height: 1.65;
      color: #2a2a2a;
      text-align: justify;
    }

    .decl-text p {
      margin-bottom: 8px;
    }

    .decl-text p:last-child {
      margin-bottom: 0;
    }

    /* ══ FIRMAS ══ */
    .sig-wrap {
      display: table;
      width: 100%;
      border-collapse: collapse;
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
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      text-align: center;
      padding: 4px 8px;
    }

    .sig-body {
      padding: 16px 18px 14px;
    }

    .sig-inner {
      width: 100%;
      border-collapse: collapse;
    }

    .huella-box {
      display: inline-block;
      width: 70px;
      height: 80px;
      border: 1px solid #aaaaaa;
      background: #fafafa;
    }

    .huella-label {
      display: block;
      font-size: 7.5px;
      text-align: center;
      margin-top: 3px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      color: #777777;
    }

    .sig-line {
      display: block;
      border-bottom: 1px solid #22293a;
      height: 80px;
      margin-bottom: 4px;
    }

    .sig-sub {
      font-size: 9px;
      color: #777777;
      margin-top: 4px;
      text-align: center;
    }

    /* ══ FOOTER ══ */
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      border-top: 1px solid #cccccc;
      padding: 5px 8px;
      background: #ffffff;
    }

    .footer table {
      width: 100%;
      border-collapse: collapse;
    }

    .footer-left {
      font-size: 8px;
      color: #777777;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .footer-dot {
      display: inline-block;
      width: 2px;
      height: 2px;
      background: #1a1a1a;
      border-radius: 50%;
      margin: 0 6px;
      vertical-align: middle;
    }

    .footer-right {
      font-size: 8px;
      color: #777777;
      text-align: right;
    }

    @media print {
      body {
        background: white;
        padding: 0;
      }

      .page {
        box-shadow: none;
        padding: 14mm;
      }
    }
  </style>
</head>
<body>
<div class="page">

  <!-- ══ HEADER ══ -->
  @php
    $areaName = strtoupper($assignment->worker?->area?->nombre ?? 'EMPRESA');
    $logoKey = 'gp'; // default

    // Mapear áreas a logos
    if (str_contains($areaName, 'GESTIÓN')) $logoKey = 'gp';
    elseif (str_contains($areaName, 'ADMINISTRACIÓN')) $logoKey = 'ap';
    elseif (str_contains($areaName, 'TECNOLOGÍA')) $logoKey = 'tp';
    elseif (str_contains($areaName, 'DIRECCIÓN')) $logoKey = 'dp';
    $logoUrl = getBase64Image(config('companies.logos.' . $logoKey . '.path', '/companies/gplogo.png'));
  @endphp
  <div class="page-header">
    <div class="header-inner">
      <div class="h-logo">
        <img src="{{ $logoUrl }}" alt="Logo">
      </div>
      <div class="h-title">
        <div class="h-title-main">ACTA DE ASIGNACIÓN DE EQUIPOS</div>
        <div class="h-title-sub">Documento interno · Área de Tecnología e Informática</div>
      </div>
      <div class="h-meta">
        <div class="h-meta-lbl">N° ACTA</div>
        <div class="h-meta-val">{{ now()->format('Y-m-d') }}</div>
        <div class="h-meta-date">{{ now()->format('d/m/Y') }}</div>
      </div>
    </div>
  </div>

  <!-- ══ DATOS DEL TRABAJADOR ══ -->
  <div class="card">
    <div class="card-title">Datos del Colaborador</div>
    <table class="worker-table">
      <tr>
        <td style="width:34%;">
          <span class="field-label">Trabajador</span>
          <span class="field-value">{{ strtoupper($assignment->worker?->nombre_completo ?? '—') }}</span>
        </td>
        <td style="width:16%;">
          <span class="field-label">DNI</span>
          <span
            class="field-value {{ empty($assignment->worker?->vat) ? 'empty' : '' }}">{{ $assignment->worker?->vat ?? '—' }}</span>
        </td>
        <td style="width:20%;">
          <span class="field-label">Fecha Asignación</span>
          <span class="field-value">{{ \Carbon\Carbon::parse($assignment->fecha)->format('d / m / Y') }}</span>
        </td>
        <td style="width:30%;">
          <span class="field-label">Área</span>
          <span
            class="field-value {{ empty($assignment->worker?->area?->name) ? 'empty' : '' }}">{{ $assignment->worker?->area?->name ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <span class="field-label">Cargo</span>
          <span
            class="field-value {{ empty($assignment->worker?->position?->name) ? 'empty' : '' }}">{{ $assignment->worker?->position?->name ?? '—' }}</span>
        </td>
        <td colspan="2">
          <span class="field-label">Correo Electrónico</span>
          <span
            class="field-value {{ empty($assignment->worker?->email) ? 'empty' : '' }}">{{ $assignment->worker?->email ?? '—' }}</span>
        </td>
      </tr>
    </table>
  </div>

  <!-- ══ EQUIPOS ASIGNADOS ══ -->
  <div class="card">
    <div class="card-title">Equipos Asignados</div>
    <table class="equipment-table">
      <thead>
      <tr>
        <th style="width: 8%">#</th>
        <th style="width: 15%">Tipo</th>
        <th style="width: 25%">Marca / Modelo</th>
        <th style="width: 22%">N° Serie / IMEI</th>
        <th style="width: 30%">Observación</th>
      </tr>
      </thead>
      <tbody>
      @foreach($assignment->items as $index => $item)
        <tr>
          <td class="eq-num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
          <td>{{ $item->equipment?->equipmentType?->nombre ?? '—' }}</td>
          <td>{{ strtoupper($item->equipment?->marca_modelo ?? '—') }}</td>
          <td class="eq-serial">{{ $item->equipment?->serie ?? '—' }}</td>
          <td>{{ $item->observacion ?? '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  @if($assignment->observacion)
    <!-- ══ OBSERVACIONES ══ -->
    <div class="card">
      <div class="card-title">Observaciones</div>
      <div class="obs-section">
        <div class="obs-text">
          <p>{{ $assignment->observacion }}</p>
        </div>
      </div>
    </div>
  @endif

  <!-- ══ DECLARACIÓN ══ -->
  <div class="card">
    <div class="card-title">Declaración y Compromiso</div>
    <div class="decl-section">
      <div class="decl-text">
        <p>Certifico que los elementos detallados en el presente documento, me han sido entregados en las cantidades
          descritas para mi cuidado y custodia con el propósito de cumplir con las tareas y asignaciones propias de mi
          cargo en la empresa, siendo estos de mi única y exclusiva responsabilidad. Me comprometo a usar correctamente
          los recursos, y solo para los fines establecidos, a no instalar ni permitir la instalación de software por
          personal ajeno al área de Sistemas; declaro además conocer y cumplir las normas internas actualizadas de
          seguridad TIC's, entregadas físicamente, publicadas y accesibles en todo momento desde la intranet de la
          empresa.</p>
        <p>Todo daño físico causado por maltrato o por el uso inapropiado de los equipos asignados, el robo o pérdida de
          éstos es de mi única y exclusiva responsabilidad, por lo cual autorizo se descuente el valor correspondiente
          del
          pago de planilla; en caso de finalizar mi contrato laboral me comprometo a realizar la devolución de la
          totalidad de los equipos asignados y autorizo el descuento de salarios, prestaciones sociales, vacaciones,
          indemnizaciones, bonificaciones y demás derechos que me correspondan del valor correspondiente a daños,
          pérdida
          o robo de los equipos en comento.</p>
      </div>
    </div>
  </div>

  <!-- ══ FIRMAS ══ -->
  <div class="card">
    <div class="card-title">Firmas de Conformidad</div>
    <div class="sig-wrap">
      <div class="sig-col">
        <div class="sig-hdr">COLABORADOR</div>
        <div class="sig-body">
          <table class="sig-inner">
            <tr>
              <td style="width:66px; text-align:center; padding-right:12px; vertical-align:bottom;">
                <span class="huella-box"></span>
                <span class="huella-label">Huella</span>
              </td>
              <td style="vertical-align:bottom;">
                <span class="sig-line"></span>
                <span class="sig-sub">{{ strtoupper($assignment->worker?->nombre_completo ?? '') }}</span>
              </td>
            </tr>
          </table>
        </div>
      </div>
      <div class="sig-col">
        <div class="sig-hdr">RESPONSABLE TICS</div>
        <div class="sig-body">
          <span class="sig-line"></span>
          <div class="sig-sub">Área de Tecnología e Informática</div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ══ FOOTER FIJO ══ -->
<div class="footer">
  <table>
    <tr>
      <td class="footer-left">
        Documento de uso interno
        <span class="footer-dot"></span>
        Área de Tecnología e Informática
      </td>
      <td class="footer-right">FP-17-02 &nbsp;|&nbsp; Pág. 1 / 1</td>
    </tr>
  </table>
</div>
</body>
</html>
