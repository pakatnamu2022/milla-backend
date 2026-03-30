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
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      color: #111111;
      background: #ffffff;
      padding: 0 0 24px;
    }

    .page {
      background: #ffffff;
      padding: 8px 10px 12px;
      margin: 0 auto;
    }

    .card {
      border: 1px solid #d2d2d2;
      border-radius: 7px;
      overflow: hidden;
      margin-bottom: 11px;
    }

    .card-title {
      background: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      letter-spacing: 0.3px;
      text-transform: uppercase;
      padding: 5px 12px;
    }

    /* ══ HEADER ══ */
    .header-shell {
      border: 1px solid #d2d2d2;
      border-radius: 7px;
      overflow: hidden;
      margin-bottom: 11px;
      background: #ffffff;
      display: table;
      width: 100%;
    }

    .header-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin-bottom: 0;
    }

    .header-table td {
      vertical-align: middle;
      padding: 0;
    }

    .header-logo-cell {
      width: 160px;
      border-right: 1px solid #e0e0e0;
      padding: 16px 14px;
      text-align: center;
      vertical-align: middle;
      background: #fafafa;
    }

    .header-logo-cell img {
      display: block;
      max-width: 130px;
      height: auto;
      margin: 0 auto;
    }

    .header-title-cell {
      padding: 16px 20px;
      text-align: center;
      flex: 1;
      vertical-align: middle;
    }

    .header-title-main {
      font-size: 14px;
      font-weight: bold;
      color: #22293a;
      letter-spacing: 0.5px;
      line-height: 1.3;
      text-transform: uppercase;
      text-align: center;
    }

    .header-meta-cell {
      width: 150px;
      padding: 14px 14px;
      vertical-align: middle;
      text-align: right;
      background: #e0e0e0;
      border-left: 1px solid #d2d2d2;
    }

    .meta-row {
      margin-bottom: 4px;
      border-bottom: 1px solid #eeeeee;
      padding-bottom: 4px;
    }

    .meta-row:last-child {
      margin-bottom: 0;
      border-bottom: none;
      padding-bottom: 0;
    }

    .meta-label {
      font-size: 7px;
      font-weight: bold;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #888888;
      display: block;
      margin-bottom: 1px;
    }

    .meta-value {
      font-size: 8px;
      color: #111111;
      font-weight: bold;
      display: block;
    }

    /* ══ DATOS DEL TRABAJADOR ══ */
    .worker-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin-bottom: 0;
    }

    .worker-table td {
      padding: 0;
      vertical-align: top;
    }

    .field-label {
      font-size: 7px;
      font-weight: bold;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #888888;
      display: block;
      margin-bottom: 3px;
    }

    .field-value {
      font-size: 9.5px;
      color: #111111;
      font-weight: 500;
      display: block;
    }

    .field-value.empty {
      color: #cccccc;
      font-style: italic;
    }

    /* ══ TABLA EQUIPOS ══ */
    .equipment-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin-bottom: 0;
      font-size: 9px;
    }

    .equipment-table thead {
      background: #f5f5f5;
    }

    .equipment-table th {
      border: none;
      border-right: 1px solid #dcdcdc;
      border-bottom: 1px solid #dcdcdc;
      padding: 6px 8px;
      text-align: left;
      font-size: 7.5px;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #111111;
    }

    .equipment-table td {
      border: none;
      border-right: 1px solid #e6e6e6;
      border-bottom: 1px solid #e6e6e6;
      padding: 6px 8px;
      color: #111111;
    }

    .equipment-table th:last-child,
    .equipment-table td:last-child {
      border-right: none;
    }

    .equipment-table tbody tr:last-child td {
      border-bottom: none;
    }

    .equipment-table tbody tr:nth-child(even) {
      background: #fafafa;
    }

    .eq-num {
      text-align: center;
      font-weight: bold;
    }

    .eq-serial {
      font-size: 8px;
      color: #555555;
    }

    /* ══ OBSERVACIONES ══ */
    .obs-section {
      margin-bottom: 0;
      padding: 10px 12px;
    }

    .obs-text {
      font-size: 9px;
      line-height: 1.5;
      color: #333333;
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
      padding: 10px 12px;
    }

    .decl-text {
      font-size: 8.5px;
      line-height: 1.6;
      color: #333333;
      text-align: justify;
    }

    .decl-text p {
      margin-bottom: 8px;
    }

    .decl-text p:last-child {
      margin-bottom: 0;
    }

    /* ══ FIRMAS ══ */
    .sig-section {
      margin-bottom: 0;
    }

    .sig-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
    }

    .sig-body-row td {
      border: 1px solid #e0e0e0;
      padding: 10px;
      vertical-align: top;
    }

    .sig-inner {
      width: 100%;
      border-collapse: collapse;
    }

    .huella-box {
      display: inline-block;
      width: 60px;
      height: 60px;
      border: 1px solid #aaaaaa;
      background: #fafafa;
    }

    .huella-label {
      display: block;
      font-size: 7px;
      text-align: center;
      margin-top: 3px;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #888888;
    }

    .sig-line {
      display: block;
      border-bottom: 1px solid #111111;
      height: 30px;
      margin-bottom: 4px;
    }

    .sig-name {
      display: block;
      font-size: 8px;
      font-weight: bold;
      color: #111111;
      text-align: center;
      margin-bottom: 2px;
      letter-spacing: 0.5px;
    }

    .sig-role {
      display: block;
      font-size: 7px;
      color: #888888;
      text-align: center;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .sig-simple {
      text-align: center;
    }

    /* ══ FOOTER ══ */
    .footer {
      border-top: 1px solid #dddddd;
      padding-top: 8px;
      margin-top: 8px;
    }

    .footer table {
      width: 100%;
      border-collapse: collapse;
    }

    .footer-left {
      font-size: 7px;
      color: #888888;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .footer-dot {
      display: inline-block;
      width: 2px;
      height: 2px;
      background: #111111;
      border-radius: 50%;
      margin: 0 6px;
      vertical-align: middle;
    }

    .footer-right {
      font-size: 7px;
      color: #888888;
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
  <div class="header-shell">
  <table class="header-table">
    <tr>
      <td class="header-logo-cell">
        @php
          $areaName = strtoupper($assignment->worker?->area?->nombre ?? 'EMPRESA');
          $logoKey = 'gp'; // default

          // Mapear áreas a logos
          if (str_contains($areaName, 'GESTIÓN')) $logoKey = 'gp';
          elseif (str_contains($areaName, 'ADMINISTRACIÓN')) $logoKey = 'ap';
          elseif (str_contains($areaName, 'TECNOLOGÍA')) $logoKey = 'tp';
          elseif (str_contains($areaName, 'DIRECCIÓN')) $logoKey = 'dp';
          $logoUrl = getBase64Image(config('companies.logos.' . $logoKey . '.path', '/companies/gplogo.png'));
          $companyName = config('companies.names.' . $logoKey, 'EMPRESA');
        @endphp
        <img src="{{ $logoUrl }}" alt="Logo"
             style="max-width: 80px; height: auto; margin-bottom: 8px;">
      </td>
      <td class="header-title-cell">
        <div class="header-title-main">Acta de Asignación de Equipos</div>
      </td>
      <td class="header-meta-cell">
        <div class="meta-row">
          <span class="meta-label">N° ACTA</span>
          <span class="meta-value">{{ now()->format('Y-m-d') }}</span>
        </div>
        <div class="meta-row">
          <span class="meta-label">Generado</span>
          <span class="meta-value">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
      </td>
    </tr>
  </table>
  </div>

  <!-- ══ DATOS DEL TRABAJADOR ══ -->
  <div class="card">
  <div class="card-title">Datos del Colaborador</div>
  <table class="worker-table">
    <tr>
      <td style="width:34%; padding:10px 14px; border-right:1px solid #e0e0e0;">
        <span class="field-label">Trabajador</span>
        <span class="field-value">{{ strtoupper($assignment->worker?->nombre_completo ?? '—') }}</span>
      </td>
      <td style="width:16%; padding:10px 14px; border-right:1px solid #e0e0e0;">
        <span class="field-label">DNI</span>
        <span
          class="field-value {{ empty($assignment->worker?->vat) ? 'empty' : '' }}">{{ $assignment->worker?->vat ?? '—' }}</span>
      </td>
      <td style="width:20%; padding:10px 14px; border-right:1px solid #e0e0e0;">
        <span class="field-label">Fecha Asignación</span>
        <span class="field-value">{{ \Carbon\Carbon::parse($assignment->fecha)->format('d / m / Y') }}</span>
      </td>
      <td style="width:30%; padding:10px 14px;">
        <span class="field-label">Área</span>
        <span
          class="field-value {{ empty($assignment->worker?->area?->nombre) ? 'empty' : '' }}">{{ $assignment->worker?->area?->nombre ?? '—' }}</span>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="padding:10px 14px; border-top:1px solid #e0e0e0; border-right:1px solid #e0e0e0;">
        <span class="field-label">Cargo</span>
        <span
          class="field-value {{ empty($assignment->worker?->position?->nombre) ? 'empty' : '' }}">{{ $assignment->worker?->position?->nombre ?? '—' }}</span>
      </td>
      <td colspan="2" style="padding:10px 14px; border-top:1px solid #e0e0e0;">
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
        éstos es de mi única y exclusiva responsabilidad, por lo cual autorizo se descuente el valor correspondiente del
        pago de planilla; en caso de finalizar mi contrato laboral me comprometo a realizar la devolución de la
        totalidad de los equipos asignados y autorizo el descuento de salarios, prestaciones sociales, vacaciones,
        indemnizaciones, bonificaciones y demás derechos que me correspondan del valor correspondiente a daños, pérdida
        o robo de los equipos en comento.</p>
    </div>
  </div>
  </div>

  <!-- ══ FIRMAS ══ -->
  <div class="card">
    <div class="card-title">Firmas de Conformidad</div>
  <div class="sig-section">
    <table class="sig-table">
      <tr class="sig-body-row">
        <!-- Colaborador con huella -->
        <td>
          <table class="sig-inner">
            <tr>
              <td style="width:80px; text-align:center; padding-right:14px; vertical-align:bottom;">
                <span class="huella-box"></span>
                <span class="huella-label">Huella</span>
              </td>
              <td style="vertical-align:bottom;">
                <span class="sig-line"></span>
                <span class="sig-name">{{ strtoupper($assignment->worker?->nombre_completo ?? '') }}</span>
                <span class="sig-role">Colaborador</span>
              </td>
            </tr>
          </table>
        </td>
        <!-- Responsable TICs -->
        <td>
          <div class="sig-simple">
            <span class="sig-line"></span>
            <span class="sig-name">&nbsp;</span>
            <span class="sig-role">Responsable TICs — Área de Tecnología</span>
          </div>
        </td>
      </tr>
    </table>
  </div>
  </div>

  <!-- ══ FOOTER ══ -->
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

</div>
</body>
</html>
