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
      font-size: 9.5px;
      color: #000000;
      line-height: 1.4;
    }

    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; vertical-align: top; }

    .label {
      font-size: 7.5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }

    /* ---------- Encabezado ---------- */
    .header-table { margin-bottom: 26px; }
    .header-table td { vertical-align: top; }

    .doc-title { font-size: 22px; font-weight: bold; letter-spacing: -0.3px; }

    .logo-cell { text-align: right; }
    .logo-img { max-height: 30px; max-width: 140px; }
    .logo-mark {
      display: inline-block;
      width: 30px;
      height: 30px;
      line-height: 30px;
      text-align: center;
      background: #000000;
      color: #ffffff;
      font-size: 11px;
      font-weight: bold;
    }

    /* ---------- Datos (número / fechas / empresa) ---------- */
    .meta-table { margin-bottom: 22px; }
    .meta-table td { padding-bottom: 7px; font-size: 9.5px; }
    .meta-table td:first-child { width: 45%; }
    .meta-value { font-weight: bold; }

    /* ---------- Empresa / trabajador (dos columnas) ---------- */
    .parties-table { margin-bottom: 26px; }
    .parties-table td { width: 50%; }
    .party-name { font-size: 10px; font-weight: bold; margin-bottom: 3px; }
    .party-line { font-size: 9px; line-height: 1.6; }

    .divider { border-top: 1px solid #000000; margin-bottom: 20px; }

    /* ---------- Monto total (destacado) ---------- */
    .hero-line { font-size: 14px; font-weight: bold; margin-bottom: 26px; }

    /* ---------- Detalle / fórmula ---------- */
    .section-title {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-weight: bold;
      margin-bottom: 8px;
      margin-top: 22px;
    }

    .detail-table { margin-bottom: 4px; }
    .detail-table th {
      text-align: left;
      font-size: 7.5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: normal;
      padding-bottom: 6px;
      border-bottom: 1px solid #000000;
    }
    .detail-table th.num, .detail-table td.num { text-align: right; }
    .detail-table td {
      padding: 6px 0;
      font-size: 9.5px;
      vertical-align: top;
    }
    .detail-table td.num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .detail-table .formula { font-size: 8.5px; }

    .subtotal-row td { padding-top: 8px; font-weight: bold; border-top: 1px solid #000000; }

    .total-block { margin-top: 14px; }
    .total-block table td { padding: 4px 0; font-size: 9.5px; }
    .total-block .total-row td {
      padding-top: 8px;
      border-top: 1px solid #000000;
      font-size: 12px;
      font-weight: bold;
    }
    .total-block td:first-child { text-align: left; }
    .total-block td.num { text-align: right; font-variant-numeric: tabular-nums; }

    .footnote {
      margin-top: 18px;
      font-size: 9px;
      line-height: 1.6;
    }

    /* ---------- Firma ---------- */
    .signature-table { margin-top: 55px; margin-bottom: 20px; }
    .signature-cell { width: 220px; }
    .signature-line {
      border-top: 1px solid #000000;
      padding-top: 6px;
      font-size: 8.5px;
    }

    .legal-note {
      font-size: 7.5px;
      line-height: 1.6;
      margin-bottom: 6px;
    }

    .footer-note {
      font-size: 7.5px;
    }
  </style>
</head>
<body>

  @php
    $baseComputable = $base['computable'] + ($type === 'cts' ? $extra['sixth_gratification'] : 0);
  @endphp

  {{-- Encabezado --}}
  <table class="header-table">
    <tr>
      <td style="width: 60%;">
        <div class="doc-title">{{ $type === 'cts' ? 'Constancia de depósito CTS' : 'Boleta de gratificación' }}</div>
      </td>
      <td class="logo-cell" style="width: 40%;">
        @if($company_logo)
          <img class="logo-img" src="{{ $company_logo }}" alt="{{ $company->name ?? '' }}">
        @else
          <span class="logo-mark">{{ $company->abbreviation ?? substr($company->name ?? '—', 0, 2) }}</span>
        @endif
      </td>
    </tr>
  </table>

  {{-- Datos del documento --}}
  <table class="meta-table">
    <tr>
      <td>
        <div class="label">Concepto</div>
        <div class="meta-value">{{ $concept }}</div>
      </td>
      <td>
        <div class="label">Periodo cubierto</div>
        <div class="meta-value">{{ $semester_start->translatedFormat('d \d\e F \Y') }} — {{ $semester_end->translatedFormat('d \d\e F \Y') }}</div>
      </td>
    </tr>
    <tr>
      <td>
        <div class="label">Fecha de emisión</div>
        <div class="meta-value">{{ now()->format('d/m/Y') }}</div>
      </td>
      <td>
        <div class="label">Meses computados</div>
        <div class="meta-value">{{ $months }} de 6</div>
      </td>
    </tr>
    @if($type === 'cts')
      <tr>
        <td>
          <div class="label">Banco</div>
          <div class="meta-value">{{ $extra['bank'] ?? '—' }}</div>
        </td>
        <td>
          <div class="label">Cuenta CTS</div>
          <div class="meta-value">{{ $extra['account'] ?? '—' }}</div>
        </td>
      </tr>
    @endif
  </table>

  {{-- Empresa / trabajador --}}
  <table class="parties-table">
    <tr>
      <td>
        <div class="label">De</div>
        <div class="party-name">{{ $company->businessName ?? $company->name ?? '' }}</div>
        <div class="party-line">
          RUC {{ $company->num_doc ?? '—' }}<br>
          {{ $company->address ?? '' }}
        </div>
      </td>
      <td>
        <div class="label">Trabajador</div>
        <div class="party-name">{{ $worker->nombre_completo }}</div>
        <div class="party-line">
          DNI {{ $worker->vat }}<br>
          {{ $worker->position->name ?? '—' }} · Ingreso {{ $worker->fecha_inicio ? \Carbon\Carbon::parse($worker->fecha_inicio)->format('d/m/Y') : '—' }}
        </div>
      </td>
    </tr>
  </table>

  <div class="divider"></div>

  {{-- Monto total, destacado --}}
  <div class="hero-line">
    S/ {{ number_format($amount, 2) }} {{ $type === 'cts' ? 'depositados' : 'pagados' }} el {{ now()->translatedFormat('d \d\e F \d\e Y') }}
  </div>

  {{-- Base computable --}}
  <div class="section-title">Cómo se calculó la base computable</div>
  <table class="detail-table">
    <tr>
      <th style="width: 40%;">Concepto</th>
      <th style="width: 35%;">Fórmula</th>
      <th class="num" style="width: 25%;">Monto</th>
    </tr>
    <tr>
      <td>Remuneración básica</td>
      <td class="formula">Sueldo del trabajador</td>
      <td class="num">S/ {{ number_format($base['salary'], 2) }}</td>
    </tr>
    <tr>
      <td>Asignación familiar</td>
      <td class="formula">Monto legal vigente (10% RMV)</td>
      <td class="num">S/ {{ number_format($base['family_allowance'], 2) }}</td>
    </tr>
    <tr>
      <td>Promedio de variables</td>
      <td class="formula">Horas extra / bonif. de últimos 6 meses ÷ 6</td>
      <td class="num">S/ {{ number_format($base['avg_variable'], 2) }}</td>
    </tr>
    @if($type === 'cts')
      <tr>
        <td>1/6 de gratificación</td>
        <td class="formula">Grati. bruta de referencia ÷ 6</td>
        <td class="num">S/ {{ number_format($extra['sixth_gratification'], 2) }}</td>
      </tr>
    @endif
    <tr class="subtotal-row">
      <td>Base computable</td>
      <td class="formula">Suma de los conceptos anteriores</td>
      <td class="num">S/ {{ number_format($baseComputable, 2) }}</td>
    </tr>
  </table>

  {{-- Cálculo final --}}
  <div class="section-title">Cómo se calculó el {{ strtolower($concept) }}</div>
  <div class="total-block">
    <table>
      @if($type === 'cts')
        <tr>
          <td>Proporcional por meses completos</td>
          <td class="num">(S/ {{ number_format($baseComputable, 2) }} ÷ 12) × {{ $months }} = S/ {{ number_format(($baseComputable / 12) * $months, 2) }}</td>
        </tr>
        <tr>
          <td>Descuento por días LSGH / faltas no justificadas</td>
          <td class="num">(S/ {{ number_format($baseComputable, 2) }} ÷ 360) × {{ $extra['lsgh_days'] }} = − S/ {{ number_format(($baseComputable / 360) * $extra['lsgh_days'], 2) }}</td>
        </tr>
      @else
        <tr>
          <td>Base computable proporcional al semestre</td>
          <td class="num">S/ {{ number_format($baseComputable, 2) }} × {{ $months }} ÷ 6 = S/ {{ number_format($amount, 2) }}</td>
        </tr>
      @endif
      <tr class="total-row">
        <td>Total {{ $concept }}</td>
        <td class="num">S/ {{ number_format($amount, 2) }}</td>
      </tr>
    </table>
  </div>

  @if($type === 'gratificacion' && $extra['extraordinary_bonus'] > 0)
    <div class="footnote">
      Adicionalmente corresponde una Bonificación Extraordinaria (9%, Ley N° 29351) de
      S/ {{ number_format($extra['extraordinary_bonus'], 2) }} = S/ {{ number_format($amount, 2) }} × 9%, abonada en concepto aparte.
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
