<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Boleta {{ $concept }} — {{ $worker->nombre_completo }}</title>
  <style>
    @page {
      margin: 16mm 16mm 20mm 16mm;
    }

    * { padding: 0; box-sizing: border-box; }

    /*
     * OJO: nunca poner "margin: 0" en <html> (ni directo ni vía *) — dompdf traduce
     * el margen de @page al elemento <html>, y una regla que se lo resetee a 0
     * pisa ese margen (el PDF sale pegado al borde, sin importar lo que diga @page).
     * El reset de margen va sobre <body> y los demás elementos, nunca sobre html.
     */
    body, table, td, div, p, h1, h2, h3 { margin: 0; }

    /*
     * Diseño minimalista: un solo tamaño base, sin cajas de color ni bordes. La
     * jerarquía se construye solo con espacio en blanco y peso/tamaño de fuente.
     */
    body {
      font-family: Helvetica, Arial, sans-serif;
      font-size: 10.5px;
      color: #000000;
      line-height: 1.6;
    }

    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; vertical-align: top; }

    .field-label { font-size: 9px; margin-bottom: 2px; }
    .field-value { font-weight: bold; }

    /* ---------- Encabezado ---------- */
    .header-table td { vertical-align: top; }
    .doc-title { font-size: 21px; font-weight: bold; }
    .doc-subtitle { font-size: 10.5px; margin-top: 4px; }

    .logo-cell { text-align: right; }
    .logo-img { max-height: 46px; max-width: 200px; }
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

    /* ---------- Bloques de contenido: separación solo por espacio ---------- */
    .block { margin-top: 30px; }
    .block-tight { margin-top: 22px; }

    /* ---------- Datos del documento ---------- */
    .meta-table td { padding-right: 16px; }
    .meta-table tr + tr td { padding-top: 14px; }

    /* ---------- Empresa / trabajador ---------- */
    .parties-table td { width: 50%; }
    .party-name { font-weight: bold; margin-bottom: 3px; }
    .party-line { line-height: 1.6; }

    /* ---------- Secciones de cálculo ---------- */
    .section-title { font-size: 9px; font-weight: bold; margin-bottom: 10px; }

    .line-table td { padding: 5px 0; }
    .line-table td.num { text-align: right; font-variant-numeric: tabular-nums; }

    .breakdown-table { margin: 4px 0 4px 14px; width: calc(100% - 14px); }
    .breakdown-table td { padding: 2px 0; font-size: 9px; }
    .breakdown-table td.num { text-align: right; font-variant-numeric: tabular-nums; }

    .total-line td { padding-top: 12px; font-weight: bold; }
    .total-line td.num { text-align: right; font-variant-numeric: tabular-nums; }

    /* ---------- Resultado final ---------- */
    .result-label { font-size: 9px; margin-bottom: 4px; }
    .result-amount { font-size: 26px; font-weight: bold; }
    .result-sub { font-size: 9px; margin-top: 6px; }

    .footnote { line-height: 1.6; }

    /* ---------- Firma (centrada) ---------- */
    .signature-block { text-align: center; margin-top: 70px; }
    .signature-rule { display: inline-block; }
    .signature-line { font-size: 9px; margin-top: 6px; }

    .legal-note { line-height: 1.6; font-size: 9px; margin-top: 26px; }

    /* ---------- Pie de página: fijo al fondo de la hoja ---------- */
    .footer-note {
      position: fixed;
      bottom: -11mm;
      left: 0;
      right: 0;
      font-size: 8.5px;
      text-align: center;
    }
  </style>
</head>
<body>

  @php
    $baseComputable = $base['computable'] + ($type === 'cts' ? $extra['sixth_gratification'] : 0);
    $isCts = $type === 'cts';
  @endphp

  {{-- Encabezado --}}
  <table class="header-table">
    <tr>
      <td style="width: 60%;">
        <div class="doc-title">{{ $isCts ? 'Constancia de depósito de CTS' : 'Boleta de gratificación' }}</div>
        <div class="doc-subtitle">{{ $company->businessName ?? $company->name ?? '' }} — {{ $worker->nombre_completo }}</div>
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
  <table class="meta-table block">
    <tr>
      <td>
        <div class="field-label">Concepto</div>
        <div class="field-value">{{ $concept }}</div>
      </td>
      <td>
        <div class="field-label">Fecha de emisión</div>
        <div class="field-value">{{ now()->format('d/m/Y') }}</div>
      </td>
      <td>
        <div class="field-label">Meses computados</div>
        <div class="field-value">{{ $months }} de 6</div>
      </td>
    </tr>
    <tr>
      <td colspan="3">
        <div class="field-label">Periodo cubierto (semestre)</div>
        <div class="field-value">{{ $semester_start->translatedFormat('d \d\e F \d\e Y') }} — {{ $semester_end->translatedFormat('d \d\e F \d\e Y') }}</div>
      </td>
    </tr>
    @if($isCts)
      <tr>
        <td>
          <div class="field-label">Banco</div>
          <div class="field-value">{{ $extra['bank'] ?? '—' }}</div>
        </td>
        <td colspan="2">
          <div class="field-label">Cuenta CTS</div>
          <div class="field-value">{{ $extra['account'] ?? '—' }}</div>
        </td>
      </tr>
    @endif
  </table>

  {{-- Empresa / trabajador --}}
  <table class="parties-table block">
    <tr>
      <td>
        <div class="field-label">De</div>
        <div class="party-name">{{ $company->businessName ?? $company->name ?? '' }}</div>
        <div class="party-line">
          RUC {{ $company->num_doc ?? '—' }}<br>
          {{ $company->address ?? '' }}
          @if($isCts && $legal_representative)
            <br>Representada por {{ $legal_representative->nombre_completo }} — DNI {{ $legal_representative->vat }}
          @endif
        </div>
      </td>
      <td>
        <div class="field-label">Trabajador</div>
        <div class="party-name">{{ $worker->nombre_completo }}</div>
        <div class="party-line">
          DNI {{ $worker->vat }}<br>
          {{ $worker->position->name ?? '—' }} · Ingreso {{ $worker->fecha_inicio ? \Carbon\Carbon::parse($worker->fecha_inicio)->format('d/m/Y') : '—' }}
        </div>
      </td>
    </tr>
  </table>

  {{-- Base computable --}}
  <div class="block">
    <div class="section-title">Base computable</div>
    <table class="line-table">
      <tr>
        <td>Remuneración básica</td>
        <td class="num">S/ {{ number_format($base['salary'], 2) }}</td>
      </tr>
      <tr>
        <td>Asignación familiar</td>
        <td class="num">S/ {{ number_format($base['family_allowance'], 2) }}</td>
      </tr>
      <tr>
        <td>
          Promedio de conceptos variables (últimos {{ $base['avg_breakdown']['months_counted'] }} mes(es))
          @if($base['avg_variable'] > 0)
            <table class="breakdown-table">
              <tr>
                <td>Horas extra</td>
                <td class="num">S/ {{ number_format($base['avg_breakdown']['overtime'], 2) }}</td>
              </tr>
              <tr>
                <td>Feriado</td>
                <td class="num">S/ {{ number_format($base['avg_breakdown']['holiday'], 2) }}</td>
              </tr>
              <tr>
                <td>DDT</td>
                <td class="num">S/ {{ number_format($base['avg_breakdown']['compensatory'], 2) }}</td>
              </tr>
              <tr>
                <td>Bonif. nocturna</td>
                <td class="num">S/ {{ number_format($base['avg_breakdown']['night_bonus'], 2) }}</td>
              </tr>
              <tr>
                <td>Bonos o comisiones</td>
                <td class="num">S/ {{ number_format($base['avg_breakdown']['bonus'], 2) }}</td>
              </tr>
            </table>
          @endif
        </td>
        <td class="num">S/ {{ number_format($base['avg_variable'], 2) }}</td>
      </tr>
      @if($isCts)
        <tr>
          <td>1/6 de gratificación de referencia</td>
          <td class="num">S/ {{ number_format($extra['sixth_gratification'], 2) }}</td>
        </tr>
      @endif
      <tr class="total-line">
        <td>Base computable total</td>
        <td class="num">S/ {{ number_format($baseComputable, 2) }}</td>
      </tr>
    </table>
  </div>

  {{-- Cálculo --}}
  @if($isCts || $months < 6)
    <div class="block-tight">
      <div class="section-title">Cálculo</div>
      <table class="line-table">
        @if($isCts)
          <tr>
            <td>Proporcional ({{ $months }}/6 meses)</td>
            <td class="num">(S/ {{ number_format($baseComputable, 2) }} ÷ 12) × {{ $months }} = S/ {{ number_format(($baseComputable / 12) * $months, 2) }}</td>
          </tr>
          <tr>
            <td>Descuento LSGH ({{ $extra['lsgh_days'] }} día(s))</td>
            <td class="num">(S/ {{ number_format($baseComputable, 2) }} ÷ 360) × {{ $extra['lsgh_days'] }} = − S/ {{ number_format(($baseComputable / 360) * $extra['lsgh_days'], 2) }}</td>
          </tr>
        @else
          <tr>
            <td>Proporcional ({{ $months }}/6 meses)</td>
            <td class="num">S/ {{ number_format($baseComputable, 2) }} × {{ $months }} ÷ 6 = S/ {{ number_format($amount, 2) }}</td>
          </tr>
        @endif
      </table>
    </div>
  @endif

  {{-- Resultado final --}}
  <div class="block">
    <div class="result-label">Total {{ $concept }}</div>
    <div class="result-amount">S/ {{ number_format($amount, 2) }}</div>
    <div class="result-sub">Son: {{ $amount_words }} — {{ $isCts ? 'depositado' : 'pagado' }} el {{ now()->format('d/m/Y') }}</div>
  </div>

  @if(!$isCts && $extra['extraordinary_bonus'] > 0)
    <div class="block-tight footnote">
      Bonificación Extraordinaria (Ley N° 29351): S/ {{ number_format($amount, 2) }} × 9% = S/ {{ number_format($extra['extraordinary_bonus'], 2) }}, abonada en concepto aparte.
    </div>
  @endif

  {{-- Firma --}}
  <div class="signature-block">
    <div class="signature-rule">_______________________</div>
    <div class="signature-line">Firma autorizada</div>
  </div>

  @if($isCts)
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
