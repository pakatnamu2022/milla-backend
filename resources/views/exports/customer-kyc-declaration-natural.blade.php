@php
  function kycBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType  = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }

  $partner  = $declaration->businessPartner;
  $isPep    = in_array($declaration->pep_status, ['SI_SOY', 'SI_HE_SIDO']);
  $isPepRel = $declaration->is_pep_relative === 'SI_SOY';

  $docDesc = strtoupper($partner?->documentType?->description ?? '');
  $marital = strtoupper($partner?->maritalStatus?->description ?? '');
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Declaración Jurada KYC</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      color: #22293a;
      background: #fff;
      padding: 0 0 100px;
    }

    /* ─── WATERMARK ──────────────────────────────── */
    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 120px;
      color: rgba(200, 200, 200, 0.12);
      font-weight: bold;
      z-index: -1;
      white-space: nowrap;
    }

    /* ─── HEADER ─────────────────────────────────── */
    .page-header {
      padding: 5px 10px 5px;
      margin-bottom: 6px;
    }

    .header-inner {
      display: table;
      width: 100%;
      border-radius: 6px;
      overflow: hidden;
    }

    .h-logo {
      display: table-cell;
      width: 140px;
      text-align: center;
      vertical-align: middle;
      padding: 4px 8px;
    }

    .h-logo img {
      max-height: 34px;
      width: auto;
      display: block;
      margin: 0 auto;
    }

    .h-title {
      display: table-cell;
      vertical-align: middle;
      text-align: center;
      padding: 4px 10px;
    }

    .h-title-main {
      font-size: 12px;
      font-weight: bold;
      color: #22293a;
      letter-spacing: 0.3px;
      line-height: 1.3;
    }

    .h-title-sub {
      font-size: 8px;
      color: #888888;
      margin-top: 1px;
    }

    /* Content wrapper */
    .content {
      padding: 0 10px;
    }

    /* ─── CARD ───────────────────────────────────── */
    .card {
      border: 1px solid #d2d2d2;
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 6px;
    }

    .card-title {
      background-color: #e0e0e0;
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      padding: 3px 10px;
      letter-spacing: 0.3px;
    }

    /* ─── DATA TABLE ─────────────────────────────── */
    table.dt {
      width: 100%;
      border-collapse: collapse;
    }

    table.dt td {
      padding: 3px 8px;
      border-bottom: 1px solid #ebebeb;
      border-right: 1px solid #ebebeb;
      font-size: 11px;
      vertical-align: top;
    }

    table.dt td:last-child {
      border-right: none;
    }

    table.dt tr:last-child td {
      border-bottom: none;
    }

    .lbl {
      font-weight: bold;
      color: #22293a;
      background: #f5f5f5;
      white-space: nowrap;
    }

    /* Sub-header dentro de card (para preguntas PEP/beneficiario) */
    .sub-lbl {
      background: #f5f5f5;
      font-weight: bold;
      color: #22293a;
      font-size: 10px;
      padding: 4px 8px;
      border-bottom: 1px solid #ebebeb;
    }

    /* ─── CHECKBOX ───────────────────────────────── */
    .chk {
      display: inline-block;
      width: 13px;
      height: 13px;
      border: 1.5px solid #aaaaaa;
      border-radius: 2px;
      vertical-align: middle;
      margin-left: 4px;
      background: #fff;
      font-size: 10px;
      font-weight: bold;
      text-align: center;
      line-height: 13px;
      color: #ffffff;
    }

    .chk.on {
      color: #000000;
    }

    /* ─── TABLA PARIENTES ────────────────────────── */
    table.rt {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
    }

    table.rt th {
      background: #f5f5f5;
      color: #22293a;
      font-weight: bold;
      font-size: 10px;
      padding: 3px 8px;
      text-align: left;
      border-right: 1px solid #d2d2d2;
      border-bottom: 1px solid #d2d2d2;
    }

    table.rt th:last-child { border-right: none; }

    table.rt td {
      padding: 3px 8px;
      font-size: 10px;
      border-bottom: 1px solid #ebebeb;
      border-right: 1px solid #ebebeb;
    }

    table.rt td:last-child { border-right: none; }
    table.rt tr:last-child td { border-bottom: none; }

    /* ─── SIGNATURES ─────────────────────────────── */
    .sig-wrap {
      border: 1px solid #d2d2d2;
      border-radius: 5px;
      overflow: hidden;
      display: table;
      width: 100%;
      margin-bottom: 6px;
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

    .sig-date-row {
      text-align: center;
      font-size: 10px;
      font-weight: bold;
      margin-bottom: 4px;
      color: #22293a;
    }

    .sig-hdr {
      background: #e0e0e0;
      color: #000000;
      font-weight: bold;
      font-size: 10px;
      text-align: center;
      padding: 3px 8px;
    }

    .sig-body {
      padding: 6px 12px;
    }

    .sig-line {
      height: 50px;
    }

    .sig-line-tall {
      height: 80px;
    }

    .sig-sub {
      font-size: 9px;
      color: #777777;
      margin-top: 3px;
      text-align: center;
    }

    /* ─── NOTES ──────────────────────────────────── */
    .notes {
      position: fixed;
      bottom: 20px;
      left: 10px;
      right: 10px;
      border: 1px solid #d2d2d2;
      border-radius: 5px;
      padding: 5px 10px;
      font-size: 8px;
      color: #4a5568;
      background: #fafafa;
    }

    /* ─── FOOTER BRANDS ──────────────────────────── */
    .foot {
      position: fixed;
      bottom: 0;
      left: 10px;
      right: 10px;
      border-top: 1px solid #d2d2d2;
      padding: 4px 0 2px;
      text-align: center;
      background: #fff;
    }

    .foot img {
      height: 11px;
      width: auto;
      margin: 0 4px;
    }
  </style>
</head>
<body>

<div class="watermark">PAKATNAMU</div>

{{-- ── ENCABEZADO ──────────────────────────────── --}}
<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      <img src="{{ kycBase64Image('img/logo-milla.png') }}" alt="Logo">
    </div>
    <div class="h-title">
      <div class="h-title-main">DECLARACIÓN JURADA DE CONOCIMIENTO DEL CLIENTE</div>
      <div class="h-title-main">RÉGIMEN GENERAL – PERSONA NATURAL</div>
      <div class="h-title-sub">Conforme al D.Leg. N° 1372 y normativa SBS/UIF-Perú</div>
    </div>
  </div>
</div>

<p style="padding: 0 10px; font-size:9px; margin-bottom:5px; color:#555;">
  Por el presente documento, declaro bajo juramento lo siguiente:
</p>

<div class="content">

  {{-- ── 1. DATOS PERSONALES ──────────────────────── --}}
  <div class="card">
    <div class="card-title">1. DATOS PERSONALES</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:18%;">Apellido Paterno</td>
        <td style="width:28%;">{{ $partner?->paternal_surname ?? '—' }}</td>
        <td class="lbl" style="width:18%;">Apellido Materno</td>
        <td>{{ $partner?->maternal_surname ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">Nombres</td>
        <td colspan="3">{{ $partner?->first_name ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">2. Tipo y N° de documento</td>
        <td colspan="3">
          DNI <span class="chk {{ $docDesc === 'DNI' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Pasaporte <span class="chk {{ $docDesc === 'PASAPORTE' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Carné de Extranjería <span class="chk {{ $docDesc === 'CARNÉ DE EXTRANJERÍA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Otro: ___________
          &nbsp;&nbsp;&nbsp;
          <strong>N°: {{ $partner?->num_doc ?? '—' }}</strong>
        </td>
      </tr>
      <tr>
        <td class="lbl">3. Nacionalidad</td>
        <td>{{ $partner?->nationality ?? 'PERUANO' }}</td>
        <td class="lbl">4. Estado Civil</td>
        <td>
          Soltero/a <span class="chk {{ (empty($marital) || in_array($marital, ['SOLTERO','SOLTERA'])) ? 'on' : '' }}">X</span>
          &nbsp;
          Casado/a <span class="chk {{ in_array($marital, ['CASADO','CASADA']) ? 'on' : '' }}">X</span>
          &nbsp;
          Viudo/a <span class="chk {{ in_array($marital, ['VIUDO','VIUDA']) ? 'on' : '' }}">X</span>
          &nbsp;
          Divorciado/a <span class="chk {{ in_array($marital, ['DIVORCIADO','DIVORCIADA']) ? 'on' : '' }}">X</span>
        </td>
      </tr>
      <tr>
        <td class="lbl">5. Cónyuge / Conviviente</td>
        <td colspan="3">{{ $partner?->spouse_full_name ?? '—' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── 6. DOMICILIO ─────────────────────────────── --}}
  <div class="card">
    <div class="card-title">6. DOMICILIO</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:18%;">Jr. / Av. / Calle / N° / Dpto.</td>
        <td colspan="3">{{ $partner?->direction ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl" style="width:18%;">Dist. / Prov. / Depto.</td>
        <td colspan="3">{{ implode(' / ', array_filter([$partner?->district?->name ?? '', $partner?->district?->province?->name ?? '', $partner?->district?->province?->department?->name ?? ''])) ?: '—' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── 7–9. OCUPACIÓN, CONTACTO Y PROPÓSITO ───── --}}
  <div class="card">
    <div class="card-title">7–9. OCUPACIÓN, CONTACTO Y PROPÓSITO</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:18%;">7. Ocupación / Cargo</td>
        <td style="width:28%;">{{ $declaration->occupation ?? '—' }}</td>
        <td class="lbl" style="width:18%;">8. Teléfono Fijo (cód. ciudad)</td>
        <td>{{ $declaration->fixed_phone ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">Celular</td>
        <td>{{ $partner?->phone ?? '—' }}</td>
        <td class="lbl">Correo Electrónico</td>
        <td>{{ $partner?->email ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">9. Propósito de la relación con el sujeto obligado</td>
        <td colspan="3">{{ $declaration->purpose_relationship ?? '—' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── 10. PEP ──────────────────────────────────── --}}
  <div class="card">
    <div class="card-title">10. PERSONA EXPUESTA POLÍTICAMENTE (PEP)</div>
    <table class="dt">
      <tr>
        <td class="sub-lbl" colspan="4">
          10.1 ¿Ha cumplido en los últimos 5 años funciones públicas en organismo público u organización internacional?
        </td>
      </tr>
      <tr>
        <td colspan="4">
          SI SOY <span class="chk {{ $declaration->pep_status === 'SI_SOY' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          SI HE SIDO <span class="chk {{ $declaration->pep_status === 'SI_HE_SIDO' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO SOY <span class="chk {{ $declaration->pep_status === 'NO_SOY' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO HE SIDO <span class="chk {{ $declaration->pep_status === 'NO_HE_SIDO' ? 'on' : '' }}">X</span>
        </td>
      </tr>
      <tr>
        <td class="sub-lbl" colspan="4">
          ¿Ha sido colaborador directo de la máxima autoridad en dichas instituciones?
        </td>
      </tr>
      <tr>
        <td colspan="4">
          SI SOY <span class="chk {{ $declaration->pep_collaborator_status === 'SI_SOY' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          SI HE SIDO <span class="chk {{ $declaration->pep_collaborator_status === 'SI_HE_SIDO' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO SOY <span class="chk {{ $declaration->pep_collaborator_status === 'NO_SOY' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO HE SIDO <span class="chk {{ $declaration->pep_collaborator_status === 'NO_HE_SIDO' ? 'on' : '' }}">X</span>
        </td>
      </tr>

      @if($isPep)
      <tr>
        <td class="lbl" style="width:18%;">Cargo / Función</td>
        <td style="width:28%;">{{ $declaration->pep_position ?? '—' }}</td>
        <td class="lbl" style="width:18%;">Nombre de la institución</td>
        <td>{{ $declaration->pep_institution ?? '—' }}</td>
      </tr>

      <tr>
        <td class="sub-lbl" colspan="4">
          10.2 Nombres y apellidos de parientes (hasta 2° grado consanguinidad y 2° afinidad) y cónyuge/conviviente:
        </td>
      </tr>
      <tr>
        <td colspan="4" style="padding: 4px 8px;">
          @if(!empty($declaration->pep_relatives))
            <table class="rt">
              <tr>
                <th style="width:8%;">#</th>
                <th>Nombres y Apellidos del Pariente</th>
              </tr>
              @foreach($declaration->pep_relatives as $i => $relative)
              <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $relative ?? '—' }}</td>
              </tr>
              @endforeach
            </table>
          @else
            <span style="color:#888;">No se registraron parientes.</span>
          @endif
        </td>
      </tr>
      <tr>
        <td class="lbl">Cónyuge / Conviviente del PEP</td>
        <td colspan="3">{{ $declaration->pep_spouse_name ?? '—' }}</td>
      </tr>
      @endif

      <tr>
        <td class="sub-lbl" colspan="4">
          10.3 ¿Es pariente de PEP hasta el 2° grado de consanguinidad o afinidad, o cónyuge/conviviente?
        </td>
      </tr>
      <tr>
        <td colspan="4">
          SI SOY <span class="chk {{ $declaration->is_pep_relative === 'SI_SOY' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO SOY <span class="chk {{ $declaration->is_pep_relative === 'NO_SOY' ? 'on' : '' }}">X</span>
        </td>
      </tr>

      @if($isPepRel && !empty($declaration->pep_relative_data))
      <tr>
        <td colspan="4" style="padding: 4px 8px;">
          <table class="rt">
            <tr>
              <th style="width:6%;">#</th>
              <th>Nombres y Apellidos del PEP</th>
              <th style="width:30%;">Parentesco</th>
            </tr>
            @foreach($declaration->pep_relative_data as $i => $rel)
            <tr>
              <td style="text-align:center;">{{ $i + 1 }}</td>
              <td>{{ $rel['pep_full_name'] ?? '—' }}</td>
              <td>{{ $rel['relationship'] ?? '—' }}</td>
            </tr>
            @endforeach
          </table>
        </td>
      </tr>
      @endif
    </table>
  </div>

  {{-- ── 11. IDENTIDAD DEL BENEFICIARIO ─────────── --}}
  <div class="card">
    <div class="card-title">11. IDENTIDAD DEL BENEFICIARIO DE LA OPERACIÓN</div>
    <table class="dt">
      <tr>
        <td colspan="4">
          Realizo esta operación a favor de: &nbsp;
          1. De mí mismo <span class="chk {{ $declaration->beneficiary_type === 'PROPIO' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          2. Tercero persona natural <span class="chk {{ $declaration->beneficiary_type === 'TERCERO_NATURAL' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          3. Persona jurídica <span class="chk {{ $declaration->beneficiary_type === 'PERSONA_JURIDICA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          4. Ente jurídico <span class="chk {{ $declaration->beneficiary_type === 'ENTE_JURIDICO' ? 'on' : '' }}">X</span>
        </td>
      </tr>

      {{-- 11.1 Propio --}}
      @if($declaration->beneficiary_type === 'PROPIO')
      <tr>
        <td class="sub-lbl" colspan="4">11.1 Operación a favor de sí mismo</td>
      </tr>
      <tr>
        <td class="lbl" style="width:22%;">i) Origen de los fondos/activos</td>
        <td colspan="3">{{ $declaration->own_funds_origin ?? '—' }}</td>
      </tr>
      @endif

      {{-- 11.2 Tercero Natural --}}
      @if($declaration->beneficiary_type === 'TERCERO_NATURAL')
      <tr>
        <td class="sub-lbl" colspan="4">11.2 Operación a favor de tercero persona natural</td>
      </tr>
      <tr>
        <td class="lbl" style="width:22%;">i) Nombres y apellidos del tercero</td>
        <td style="width:28%;">{{ $declaration->third_full_name ?? '—' }}</td>
        <td class="lbl" style="width:18%;">ii) Tipo de documento</td>
        <td>{{ $declaration->third_doc_type ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">N° de documento</td>
        <td colspan="3">{{ $declaration->third_doc_number ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">iii) Datos de la representación</td>
        <td colspan="3">
          Poder por Escritura Pública <span class="chk {{ $declaration->third_representation_type === 'ESCRITURA_PUBLICA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Mandato <span class="chk {{ $declaration->third_representation_type === 'MANDATO' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Poder <span class="chk {{ $declaration->third_representation_type === 'PODER' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Otros <span class="chk {{ $declaration->third_representation_type === 'OTROS' ? 'on' : '' }}">X</span>
        </td>
      </tr>
      <tr>
        <td class="lbl">iv) ¿El tercero es o ha sido PEP?</td>
        <td colspan="3">
          SI ES <span class="chk {{ $declaration->third_pep_status === 'SI_ES' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          SI HA SIDO <span class="chk {{ $declaration->third_pep_status === 'SI_HA_SIDO' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO ES <span class="chk {{ $declaration->third_pep_status === 'NO_ES' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          NO HA SIDO <span class="chk {{ $declaration->third_pep_status === 'NO_HA_SIDO' ? 'on' : '' }}">X</span>
        </td>
      </tr>
      @if(in_array($declaration->third_pep_status, ['SI_ES', 'SI_HA_SIDO']))
      <tr>
        <td class="lbl">Cargo</td>
        <td>{{ $declaration->third_pep_position ?? '—' }}</td>
        <td class="lbl">Institución</td>
        <td>{{ $declaration->third_pep_institution ?? '—' }}</td>
      </tr>
      @endif
      <tr>
        <td class="lbl">v) Origen de los fondos/activos</td>
        <td colspan="3">{{ $declaration->third_funds_origin ?? '—' }}</td>
      </tr>
      @endif

      {{-- 11.3 Persona Jurídica / Ente Jurídico --}}
      @if(in_array($declaration->beneficiary_type, ['PERSONA_JURIDICA', 'ENTE_JURIDICO']))
      <tr>
        <td class="sub-lbl" colspan="4">11.3 Operación a favor de persona jurídica o ente jurídico</td>
      </tr>
      <tr>
        <td class="lbl" style="width:22%;">i) Denominación / Razón Social</td>
        <td style="width:28%;">{{ $declaration->entity_name ?? '—' }}</td>
        <td class="lbl" style="width:18%;">ii) N° de RUC</td>
        <td>{{ $declaration->entity_ruc ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">iii) Datos de la representación</td>
        <td colspan="3">
          Poder por Acta <span class="chk {{ $declaration->entity_representation_type === 'PODER_POR_ACTA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Poder por Escritura Pública <span class="chk {{ $declaration->entity_representation_type === 'ESCRITURA_PUBLICA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Mandato <span class="chk {{ $declaration->entity_representation_type === 'MANDATO' ? 'on' : '' }}">X</span>
        </td>
      </tr>
      <tr>
        <td class="lbl">iv) Origen de los fondos/activos</td>
        <td colspan="3">{{ $declaration->entity_funds_origin ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">v) Identificación del Beneficiario Final (D.Leg. N° 1372)</td>
        <td colspan="3">{{ $declaration->entity_final_beneficiary ?? '—' }}</td>
      </tr>
      @endif
    </table>
  </div>

  {{-- ── FIRMA ────────────────────────────────────── --}}
  <div class="sig-date-row">
    Fecha de Declaración: {{ $declaration->declaration_date->format('d') }} / {{ $declaration->declaration_date->format('m') }} / {{ $declaration->declaration_date->format('Y') }}
  </div>
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig-hdr">HUELLA</div>
      <div class="sig-body">
        <div class="sig-line sig-line-tall"></div>
      </div>
    </div>
    <div class="sig-col">
      <div class="sig-hdr">FIRMA DEL DECLARANTE</div>
      <div class="sig-body">
        <div class="sig-line"></div>
        <div class="sig-sub">{{ $partner?->full_name ?? '' }}</div>
      </div>
    </div>
  </div>

  {{-- ── NOTA AL PIE ──────────────────────────────── --}}
  <div class="notes">
    <strong style="color:#000000;">NOTA:</strong>
    Para ser conservada por el sujeto obligado y, en su caso, exhibida a solicitud de la UIF-Perú en actividades de supervisión.
    No se envía a la UIF-Perú, salvo solicitud expresa. Manifiesto que los datos consignados son exactos y se ajustan fielmente a la realidad.
  </div>

  {{-- ── FOOTER MARCAS ────────────────────────────── --}}
  <div class="foot">
    <img src="{{ kycBase64Image('images/ap/brands/suzuki.png') }}" alt="Suzuki">
    <img src="{{ kycBase64Image('images/ap/brands/subaru.png') }}" alt="Subaru">
    <img src="{{ kycBase64Image('images/ap/brands/dfsk.png') }}" alt="DFSK">
    <img src="{{ kycBase64Image('images/ap/brands/mazda.png') }}" alt="Mazda">
    <img src="{{ kycBase64Image('images/ap/brands/citroen.jpg') }}" alt="Citroën">
    <img src="{{ kycBase64Image('images/ap/brands/renault.png') }}" alt="Renault">
    <img src="{{ kycBase64Image('images/ap/brands/haval.png') }}" alt="Haval">
    <img src="{{ kycBase64Image('images/ap/brands/great-wall.png') }}" alt="Great Wall">
    <img src="{{ kycBase64Image('images/ap/brands/changan.png') }}" alt="Changan">
    <img src="{{ kycBase64Image('images/ap/brands/jac.png') }}" alt="JAC">
  </div>

</div>{{-- /content --}}

</body>
</html>
