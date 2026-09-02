<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Boleta {{ $concept }} — {{ $worker->nombre_completo }}</title>
  <style>
    @page {
      margin: 22mm 20mm;
    }

    * { padding: 0; box-sizing: border-box; }

    /*
     * OJO: nunca poner "margin: 0" en <html> (ni directo ni vía *) — dompdf traduce
     * el margen de @page al elemento <html>, y una regla que se lo resetee a 0
     * pisa ese margen (el PDF sale pegado al borde, sin importar lo que diga @page).
     * El reset de margen va sobre <body> y los demás elementos, nunca sobre html.
     */
    body, table, td, div, p, h1, h2, h3 { margin: 0; }

    body {
      font-family: Helvetica, Arial, sans-serif;
      font-size: 10.5px;
      color: #1d1d1f;
      line-height: 1.4;
    }

    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; vertical-align: top; }

    .muted { color: #6e6e73; }
    .label {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: #6e6e73;
      margin-bottom: 3px;
    }

    /* ---------- Encabezado ---------- */
    .header-table { margin-bottom: 22px; }

    .logo-img { max-height: 32px; max-width: 150px; }
    .logo-mark {
      display: inline-block;
      width: 32px;
      height: 32px;
      line-height: 32px;
      text-align: center;
      background: #1d1d1f;
      color: #ffffff;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.5px;
    }

    .company-block { text-align: right; }
    .company-name { font-size: 10.5px; font-weight: bold; }
    .company-meta { font-size: 8.5px; color: #6e6e73; margin-top: 3px; line-height: 1.5; }

    .divider { border-top: 1px solid #e5e5e5; margin-bottom: 24px; }

    /* ---------- Título y monto ---------- */
    .hero-table { margin-bottom: 26px; }
    .hero-table td { vertical-align: bottom; }

    .eyebrow {
      font-size: 8.5px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #6e6e73;
      margin-bottom: 5px;
    }
    .doc-title { font-size: 20px; font-weight: bold; letter-spacing: -0.2px; }
    .doc-subtitle { font-size: 9px; color: #6e6e73; margin-top: 4px; }

    .hero-amount { text-align: right; }
    .hero-amount .eyebrow { text-align: right; }
    .hero-amount .value { font-size: 22px; font-weight: bold; letter-spacing: -0.3px; }

    /* ---------- Datos del trabajador ---------- */
    .info-box {
      border-top: 1px solid #e5e5e5;
      border-bottom: 1px solid #e5e5e5;
      padding: 14px 0;
      margin-bottom: 26px;
    }
    .info-grid td { padding: 0 16px 12px 0; width: 25%; }
    .info-grid tr:last-child td { padding-bottom: 0; }
    .info-value { font-size: 10.5px; font-weight: bold; }

    /* ---------- Secciones ---------- */
    .section-title {
      font-size: 8.5px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #6e6e73;
      margin-bottom: 8px;
    }

    .line-table { margin-bottom: 22px; }
    .line-table td {
      padding: 7px 0;
      border-bottom: 1px solid #f0f0f0;
      font-size: 10.5px;
    }
    .line-table td.num { text-align: right; font-variant-numeric: tabular-nums; }

    .total-row td {
      border-bottom: none;
      border-top: 1px solid #1d1d1f;
      padding-top: 10px;
      font-size: 12px;
      font-weight: bold;
    }

    .footnote-box {
      margin-bottom: 22px;
      padding: 10px 14px;
      border-left: 2px solid #d2d2d7;
      font-size: 9px;
      color: #6e6e73;
      line-height: 1.6;
    }
    .footnote-box strong { color: #1d1d1f; }

    /* ---------- Firma ---------- */
    .signature-table { margin-top: 50px; margin-bottom: 20px; }
    .signature-cell { width: 220px; }
    .signature-line {
      border-top: 1px solid #1d1d1f;
      padding-top: 6px;
      font-size: 8.5px;
      color: #6e6e73;
    }

    .legal-note {
      font-size: 7.5px;
      color: #86868b;
      line-height: 1.6;
      margin-bottom: 6px;
    }

    .footer-note {
      font-size: 7.5px;
      color: #a1a1a6;
    }
  </style>
</head>
<body>

  {{-- Encabezado: logo de la empresa + datos legales --}}
  <table class="header-table">
    <tr>
      <td style="width: 50%;">
        @if($company_logo)
          <img class="logo-img" src="{{ $company_logo }}" alt="{{ $company->name ?? '' }}">
        @else
          <span class="logo-mark">{{ $company->abbreviation ?? substr($company->name ?? '—', 0, 2) }}</span>
        @endif
      </td>
      <td class="company-block" style="width: 50%;">
        <div class="company-name">{{ $company->businessName ?? $company->name ?? '' }}</div>
        <div class="company-meta">
          RUC {{ $company->num_doc ?? '—' }}<br>
          {{ $company->address ?? '' }}
        </div>
      </td>
    </tr>
  </table>

  <div class="divider"></div>

  {{-- Título del documento + monto total --}}
  <table class="hero-table">
    <tr>
      <td style="width: 60%;">
        <div class="eyebrow">{{ $type === 'cts' ? 'Depósito semestral' : 'Boleta de pago' }}</div>
        <div class="doc-title">{{ $concept }}</div>
        <div class="doc-subtitle">
          {{ $semester_start->translatedFormat('d \d\e F \d\e Y') }} — {{ $semester_end->translatedFormat('d \d\e F \d\e Y') }}
        </div>
      </td>
      <td class="hero-amount" style="width: 40%;">
        <div class="eyebrow">Total {{ $type === 'cts' ? 'a depositar' : 'a pagar' }}</div>
        <div class="value">S/ {{ number_format($amount, 2) }}</div>
      </td>
    </tr>
  </table>

  {{-- Datos del trabajador --}}
  <div class="info-box">
    <table class="info-grid">
      <tr>
        <td colspan="4">
          <div class="label">Trabajador</div>
          <div class="info-value">{{ $worker->nombre_completo }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">DNI</div>
          <div class="info-value">{{ $worker->vat }}</div>
        </td>
        <td>
          <div class="label">Cargo</div>
          <div class="info-value">{{ $worker->position->name ?? '—' }}</div>
        </td>
        <td>
          <div class="label">Fecha de ingreso</div>
          <div class="info-value">{{ $worker->fecha_inicio ? \Carbon\Carbon::parse($worker->fecha_inicio)->format('d/m/Y') : '—' }}</div>
        </td>
        <td>
          <div class="label">Meses computados</div>
          <div class="info-value">{{ $months }} de 6</div>
        </td>
      </tr>
      @if($type === 'cts')
        <tr>
          <td>
            <div class="label">Banco</div>
            <div class="info-value">{{ $extra['bank'] ?? '—' }}</div>
          </td>
          <td colspan="2">
            <div class="label">Cuenta CTS</div>
            <div class="info-value">{{ $extra['account'] ?? '—' }}</div>
          </td>
          <td>
            <div class="label">Fecha de emisión</div>
            <div class="info-value">{{ now()->format('d/m/Y') }}</div>
          </td>
        </tr>
      @else
        <tr>
          <td colspan="3"></td>
          <td>
            <div class="label">Fecha de emisión</div>
            <div class="info-value">{{ now()->format('d/m/Y') }}</div>
          </td>
        </tr>
      @endif
    </table>
  </div>

  {{-- Remuneración computable --}}
  <div class="section-title">Remuneración computable</div>
  <table class="line-table">
    <tr>
      <td>Remuneración básica</td>
      <td class="num">{{ number_format($base['salary'], 2) }}</td>
    </tr>
    <tr>
      <td>Asignación familiar</td>
      <td class="num">{{ number_format($base['family_allowance'], 2) }}</td>
    </tr>
    <tr>
      <td>Promedio de horas extra y bonificaciones (6 meses)</td>
      <td class="num">{{ number_format($base['avg_variable'], 2) }}</td>
    </tr>
    @if($type === 'cts')
      <tr>
        <td>1/6 de gratificación</td>
        <td class="num">{{ number_format($extra['sixth_gratification'], 2) }}</td>
      </tr>
    @endif
    <tr class="total-row">
      <td>Base computable</td>
      <td class="num">
        {{ number_format(
          $base['computable'] + ($type === 'cts' ? $extra['sixth_gratification'] : 0),
          2
        ) }}
      </td>
    </tr>
  </table>

  {{-- Cálculo --}}
  <div class="section-title">Cálculo</div>
  <table class="line-table">
    @if($type === 'cts')
      <tr>
        <td class="muted">Días LSGH / faltas no justificadas descontadas</td>
        <td class="num muted">{{ $extra['lsgh_days'] }} día(s)</td>
      </tr>
    @endif
    <tr class="total-row">
      <td>Total {{ $concept }}</td>
      <td class="num">S/ {{ number_format($amount, 2) }}</td>
    </tr>
  </table>

  @if($type === 'gratificacion' && $extra['extraordinary_bonus'] > 0)
    <div class="footnote-box">
      Adicionalmente corresponde una Bonificación Extraordinaria (9%, Ley N° 29351) de
      <strong>S/ {{ number_format($extra['extraordinary_bonus'], 2) }}</strong>, abonada en concepto aparte.
    </div>
  @endif

  {{-- Firma --}}
  <table class="signature-table">
    <tr>
      <td class="signature-cell">
        <div class="signature-line">Firma autorizada</div>
      </td>
    </tr>
  </table>

  @if($type === 'cts')
    <div class="legal-note">
      Constancia emitida en aplicación del Art. 24° del Texto Único Ordenado del Decreto Legislativo
      N° 650, Ley de Compensación por Tiempo de Servicios, aprobado mediante D.S. N° 001-97-TR.
    </div>
  @endif

  <div class="footer-note">
    Documento generado automáticamente — {{ now()->format('d/m/Y H:i') }}
  </div>

</body>
</html>
