@php
  function getBase64ImagePhoneUnassign($path) {
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
  <title>Acta de Desasignación de Línea Telefónica</title>
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
      width: 140px;
      text-align: center;
      vertical-align: middle;
      border-right: 1px solid #e0e0e0;
    }

    .h-logo img {
      max-height: 30px;
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
      font-size: 8px;
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

    /* ══ DATOS LÍNEA ══ */
    .line-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin-bottom: 0;
    }

    .line-table td {
      padding: 5px 10px;
      vertical-align: top;
      border-right: 1px solid #e8e8e8;
      border-bottom: 1px solid #e8e8e8;
    }

    .line-table td:last-child {
      border-right: none;
    }

    .line-table tr:last-child td {
      border-bottom: none;
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
  </style>
</head>
<body>
<div class="page">

  <!-- ══ HEADER ══ -->
  @php
    $company = strtolower($assignment->worker->sede->company->abbreviation ?? '');
    $logoUrl = getBase64ImagePhoneUnassign(config('companies.logos.' . $company . '.large', '/companies/gplargo.png'));
  @endphp
  <div class="page-header">
    <div class="header-inner">
      <div class="h-logo">
        <img src="{{ $logoUrl }}" alt="Logo">
      </div>
      <div class="h-title">
        <div class="h-title-main">ACTA DE DESASIGNACIÓN DE LÍNEA TELEFÓNICA</div>
        <div class="h-title-sub">Documento interno · Área de Tecnología e Informática</div>
      </div>
      <div class="h-meta">
        <div class="h-meta-lbl">N° ACTA</div>
        <div class="h-meta-val">{{ str_pad($assignment->id, 8, '0', STR_PAD_LEFT) }}</div>
        <div class="h-meta-date">{{ now()->format('d/m/Y') }}</div>
      </div>
    </div>
  </div>

  <!-- ══ DATOS DEL COLABORADOR ══ -->
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
        <td style="width:25%;">
          <span class="field-label">Fecha Asignación</span>
          <span
            class="field-value">{{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d / m / Y') : '—' }}</span>
        </td>
        <td style="width:25%;">
          <span class="field-label">Fecha Desasignación</span>
          <span
            class="field-value">{{ $assignment->unassigned_at ? \Carbon\Carbon::parse($assignment->unassigned_at)->format('d / m / Y') : now()->format('d / m / Y') }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <span class="field-label">Cargo</span>
          <span
            class="field-value {{ empty($assignment->worker?->position?->name) ? 'empty' : '' }}">{{ $assignment->worker?->position?->name ?? '—' }}</span>
        </td>
        <td colspan="2">
          <span class="field-label">Área</span>
          <span
            class="field-value {{ empty($assignment->worker?->area?->name) ? 'empty' : '' }}">{{ $assignment->worker?->area?->name ?? '—' }}</span>
        </td>
      </tr>
    </table>
  </div>

  <!-- ══ DATOS DE LA LÍNEA ══ -->
  <div class="card">
    <div class="card-title">Datos de la Línea Telefónica</div>
    <table class="line-table">
      <tr>
        <td style="width:25%;">
          <span class="field-label">Número de Línea</span>
          <span class="field-value">{{ $assignment->phoneLine?->line_number ?? '—' }}</span>
        </td>
        <td style="width:25%;">
          <span class="field-label">Operador</span>
          <span class="field-value">{{ $assignment->phoneLine?->telephoneAccount?->operator ?? '—' }}</span>
        </td>
        <td style="width:25%;">
          <span class="field-label">Cuenta</span>
          <span class="field-value">{{ $assignment->phoneLine?->telephoneAccount?->account_number ?? '—' }}</span>
        </td>
        <td style="width:25%;">
          <span class="field-label">Plan</span>
          <span class="field-value">{{ $assignment->phoneLine?->telephonePlan?->name ?? '—' }}</span>
        </td>
      </tr>
    </table>
  </div>

  @if($assignment->observacion ?? false)
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
    <div class="card-title">Declaración de Desasignación</div>
    <div class="decl-section">
      <div class="decl-text">
        <p>Certifico que la línea telefónica corporativa detallada en el presente documento ha sido desasignada a mi
          persona en la fecha indicada. Me comprometo a cesar inmediatamente cualquier uso de dicha línea a partir de
          esta fecha y a no retener ningún dispositivo o SIM asociado a la misma.</p>
        <p>Declaro haber entregado al área de TICs todos los elementos vinculados a esta línea en las condiciones
          acordadas, quedando exento de toda responsabilidad sobre el uso o costos generados por la línea referida a
          partir de la fecha de desasignación. En caso de detectarse consumos posteriores atribuibles a mi persona, o de
          no haberse realizado la devolución íntegra de los dispositivos asociados, reconozco mi responsabilidad y
          autorizo se descuente el valor correspondiente de cualquier liquidación, prestaciones sociales, bonificaciones
          y demás haberes que me correspondan.</p>
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
              <td style="vertical-align:bottom; text-align:center;">
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
          <div class="sig-sub">ÁREA DE TECNOLOGÍAS DE INFORMACIÓN Y COMUNICACIONES</div>
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
        ÁREA DE TECNOLOGÍAS DE INFORMACIÓN Y COMUNICACIONES
      </td>
      <td class="footer-right">Pág. 1 / 1</td>
    </tr>
  </table>
</div>
</body>
</html>
