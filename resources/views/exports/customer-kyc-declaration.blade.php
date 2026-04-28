@php
  function kycBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType  = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }

  $partner    = $declaration->businessPartner;
  $isPep      = in_array($declaration->pep_status, ['SI_SOY', 'SI_HE_SIDO']);
  $isPepRel   = $declaration->is_pep_relative === 'SI_SOY';

  $checkmark = function(bool $val): string {
    return $val ? 'X' : '&nbsp;';
  };
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Declaración Jurada – Conocimiento del Cliente</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, sans-serif;
      font-size: 8.5px;
      color: #1a1a2e;
      background: #fff;
      padding: 10px 14px 30px;
    }

    /* ── ENCABEZADO ── */
    .header-table {
      width: 100%;
      border-collapse: collapse;
      border: 1.5px solid #c0c0c0;
      border-radius: 4px;
      margin-bottom: 8px;
    }
    .header-table td { vertical-align: middle; padding: 0; }
    .h-logo {
      width: 120px;
      text-align: center;
      padding: 6px 10px;
      border-right: 1px solid #c0c0c0;
    }
    .h-logo img { max-height: 34px; width: auto; }
    .h-title {
      text-align: center;
      padding: 6px 12px;
      border-right: 1px solid #c0c0c0;
    }
    .h-title-main {
      font-size: 10px;
      font-weight: bold;
      letter-spacing: 0.4px;
      line-height: 1.4;
    }
    .h-title-sub {
      font-size: 7.5px;
      color: #666;
      margin-top: 2px;
    }
    .h-meta {
      width: 130px;
      text-align: center;
      background: #e8e8e8;
      padding: 6px 10px;
    }
    .h-meta-lbl { font-size: 7px; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; color: #444; }
    .h-meta-val { font-size: 12px; font-weight: bold; color: #1a1a2e; margin-top: 2px; }
    .h-meta-date { font-size: 7px; color: #666; margin-top: 2px; }

    /* ── SECCIONES ── */
    .section {
      border: 1px solid #ccc;
      border-radius: 3px;
      margin-bottom: 6px;
      overflow: hidden;
    }
    .section-title {
      background: #d8d8d8;
      font-weight: bold;
      font-size: 8px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
      padding: 3px 10px;
      border-bottom: 1px solid #ccc;
    }
    .section-body { padding: 6px 10px; }

    /* ── FILAS DE DATOS ── */
    .data-table {
      width: 100%;
      border-collapse: collapse;
    }
    .data-table td {
      padding: 3px 6px;
      vertical-align: top;
      border-right: 1px solid #e5e5e5;
      border-bottom: 1px solid #e5e5e5;
    }
    .data-table td:last-child { border-right: none; }
    .data-table tr:last-child td { border-bottom: none; }

    .lbl {
      font-size: 7px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: #777;
      display: block;
      margin-bottom: 2px;
    }
    .val {
      font-size: 8.5px;
      font-weight: bold;
      color: #1a1a2e;
    }

    /* ── CHECKS INLINE ── */
    .check-row { font-size: 8px; margin-bottom: 4px; line-height: 1.8; }
    .check-box {
      display: inline-block;
      border: 1px solid #555;
      width: 11px;
      height: 11px;
      text-align: center;
      line-height: 11px;
      font-size: 9px;
      font-weight: bold;
      margin: 0 3px;
      vertical-align: middle;
    }

    /* ── SUBSECCIÓN PEP ── */
    .sub-title {
      font-weight: bold;
      font-size: 8px;
      margin-bottom: 4px;
      color: #333;
    }
    .pep-detail {
      background: #f7f7f7;
      border: 1px solid #ddd;
      border-radius: 3px;
      padding: 5px 8px;
      margin-top: 4px;
      font-size: 8px;
    }

    /* ── TABLA PARIENTES ── */
    .relatives-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
      font-size: 8px;
    }
    .relatives-table th {
      background: #e0e0e0;
      padding: 3px 6px;
      text-align: left;
      font-size: 7.5px;
      border: 1px solid #ccc;
    }
    .relatives-table td {
      border: 1px solid #ddd;
      padding: 3px 6px;
      vertical-align: top;
    }

    /* ── FIRMA ── */
    .signature-section {
      margin-top: 10px;
      border: 1px solid #ccc;
      border-radius: 3px;
      padding: 10px 14px;
    }
    .sig-row {
      display: table;
      width: 100%;
    }
    .sig-cell {
      display: table-cell;
      width: 50%;
      vertical-align: bottom;
      padding: 4px 10px;
    }
    .sig-line {
      border-top: 1.5px solid #333;
      margin-top: 30px;
      padding-top: 3px;
      text-align: center;
      font-size: 7.5px;
      color: #444;
    }

    /* ── NOTA AL PIE ── */
    .footer-note {
      margin-top: 8px;
      font-size: 7px;
      color: #777;
      font-style: italic;
      border-top: 1px solid #ddd;
      padding-top: 5px;
    }

    .text-bold { font-weight: bold; }
    .text-center { text-align: center; }
    .mt4 { margin-top: 4px; }
    .mt6 { margin-top: 6px; }
  </style>
</head>
<body>

{{-- ══════════════════════════ ENCABEZADO ══════════════════════════ --}}
<table class="header-table">
  <tr>
    <td class="h-logo">
      <img src="{{ kycBase64Image('img/logo-milla.png') }}" alt="Logo">
    </td>
    <td class="h-title">
      <div class="h-title-main">DECLARACIÓN JURADA DE CONOCIMIENTO DEL CLIENTE</div>
      <div class="h-title-main">RÉGIMEN GENERAL – PERSONA NATURAL</div>
      <div class="h-title-sub">Conforme al D.Leg. N° 1372 y normativa SBS/UIF-Perú</div>
    </td>
    <td class="h-meta">
      <div class="h-meta-lbl">N° Declaración</div>
      <div class="h-meta-val">DJ-{{ str_pad($declaration->id, 6, '0', STR_PAD_LEFT) }}</div>
      <div class="h-meta-date">{{ $declaration->declaration_date->format('d/m/Y') }}</div>
    </td>
  </tr>
</table>

<p style="font-size:8px; margin-bottom:6px;">
  Por el presente documento, declaro bajo juramento, lo siguiente:
</p>

{{-- ══════════════════════════ 1-2 DATOS PERSONALES ══════════════════════════ --}}
<div class="section">
  <div class="section-title">1. Datos Personales</div>
  <div class="section-body">
    <table class="data-table">
      <tr>
        <td width="40%">
          <span class="lbl">1. Nombres y Apellidos</span>
          <span class="val">{{ $partner?->full_name ?? '—' }}</span>
        </td>
        <td width="30%">
          <span class="lbl">Apellido Paterno</span>
          <span class="val">{{ $partner?->paternal_surname ?? '—' }}</span>
        </td>
        <td width="30%">
          <span class="lbl">Apellido Materno</span>
          <span class="val">{{ $partner?->maternal_surname ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <span class="lbl">2. Tipo y N° de documento de identidad</span>
          <div class="check-row">
            <span class="check-box">{{ ($partner?->document_type_id && strtoupper($partner?->documentType?->description ?? '') === 'DNI') ? 'X' : '&nbsp;' }}</span> DNI &nbsp;&nbsp;
            <span class="check-box">{{ (strtoupper($partner?->documentType?->description ?? '') === 'PASAPORTE') ? 'X' : '&nbsp;' }}</span> Pasaporte &nbsp;&nbsp;
            <span class="check-box">{{ (strtoupper($partner?->documentType?->description ?? '') === 'CARNÉ DE EXTRANJERÍA') ? 'X' : '&nbsp;' }}</span> Carné de Extranjería &nbsp;&nbsp;
            <span class="check-box">&nbsp;</span> Otro: ______________
            &nbsp;&nbsp; <span class="lbl" style="display:inline;">N°:</span>
            <span class="val">{{ $partner?->num_doc ?? '—' }}</span>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <span class="lbl">3. Nacionalidad (extranjero)</span>
          <span class="val">{{ $partner?->nationality === 'EXTRANJERO' ? ($partner?->nationality ?? '—') : '—' }}</span>
        </td>
        <td colspan="2">
          <span class="lbl">4. Estado Civil</span>
          <div class="check-row">
            <span class="check-box">{{ strtoupper($partner?->maritalStatus?->description ?? '') === 'SOLTERO' || strtoupper($partner?->maritalStatus?->description ?? '') === 'SOLTERA' ? 'X' : '&nbsp;' }}</span> Soltero/a &nbsp;
            <span class="check-box">{{ strtoupper($partner?->maritalStatus?->description ?? '') === 'CASADO' || strtoupper($partner?->maritalStatus?->description ?? '') === 'CASADA' ? 'X' : '&nbsp;' }}</span> Casado/a &nbsp;
            <span class="check-box">{{ strtoupper($partner?->maritalStatus?->description ?? '') === 'VIUDO' || strtoupper($partner?->maritalStatus?->description ?? '') === 'VIUDA' ? 'X' : '&nbsp;' }}</span> Viudo/a &nbsp;
            <span class="check-box">{{ strtoupper($partner?->maritalStatus?->description ?? '') === 'DIVORCIADO' || strtoupper($partner?->maritalStatus?->description ?? '') === 'DIVORCIADA' ? 'X' : '&nbsp;' }}</span> Divorciado/a
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <span class="lbl">5. Nombres y apellidos del cónyuge o conviviente</span>
          <span class="val">{{ $partner?->spouse_full_name ?? '—' }}</span>
        </td>
      </tr>
    </table>
  </div>
</div>

{{-- ══════════════════════════ DOMICILIO ══════════════════════════ --}}
<div class="section">
  <div class="section-title">6. Domicilio</div>
  <div class="section-body">
    <table class="data-table">
      <tr>
        <td colspan="3">
          <span class="lbl">Jr. / Av. / Calle / Pasaje / Óvalo – N° – Dpto./Int.</span>
          <span class="val">{{ $partner?->direction ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td>
          <span class="lbl">Distrito</span>
          <span class="val">{{ $partner?->district?->name ?? '—' }}</span>
        </td>
        <td>
          <span class="lbl">Provincia</span>
          <span class="val">{{ $partner?->district?->province?->name ?? '—' }}</span>
        </td>
        <td>
          <span class="lbl">Departamento</span>
          <span class="val">{{ $partner?->district?->province?->department?->name ?? '—' }}</span>
        </td>
      </tr>
    </table>
  </div>
</div>

{{-- ══════════════════════════ OCUPACIÓN / CONTACTO ══════════════════════════ --}}
<div class="section">
  <div class="section-title">7–9. Ocupación, Contacto y Propósito</div>
  <div class="section-body">
    <table class="data-table">
      <tr>
        <td width="33%">
          <span class="lbl">7. Ocupación / Cargo</span>
          <span class="val">{{ $declaration->occupation ?? '—' }}</span>
        </td>
        <td width="22%">
          <span class="lbl">8. Teléfono Fijo (cód. ciudad)</span>
          <span class="val">{{ $declaration->fixed_phone ?? '—' }}</span>
        </td>
        <td width="22%">
          <span class="lbl">Celular</span>
          <span class="val">{{ $partner?->phone ?? '—' }}</span>
        </td>
        <td width="23%">
          <span class="lbl">Correo Electrónico</span>
          <span class="val">{{ $partner?->email ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="4">
          <span class="lbl">9. Propósito de la relación con el sujeto obligado</span>
          <span class="val">{{ $declaration->purpose_relationship ?? '—' }}</span>
        </td>
      </tr>
    </table>
  </div>
</div>

{{-- ══════════════════════════ PEP 10.1 ══════════════════════════ --}}
<div class="section">
  <div class="section-title">10. Persona Expuesta Políticamente (PEP)</div>
  <div class="section-body">

    <div class="sub-title">10.1 ¿Ha cumplido en los últimos 5 años funciones públicas en organismo público u organización internacional?</div>
    <div class="check-row">
      <span class="check-box">{{ $declaration->pep_status === 'SI_SOY' ? 'X' : '&nbsp;' }}</span> SI SOY &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->pep_status === 'SI_HE_SIDO' ? 'X' : '&nbsp;' }}</span> SI HE SIDO &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->pep_status === 'NO_SOY' ? 'X' : '&nbsp;' }}</span> NO SOY &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->pep_status === 'NO_HE_SIDO' ? 'X' : '&nbsp;' }}</span> NO HE SIDO
    </div>

    <div class="sub-title mt6">¿Ha sido colaborador directo de la máxima autoridad en dichas instituciones?</div>
    <div class="check-row">
      <span class="check-box">{{ $declaration->pep_collaborator_status === 'SI_SOY' ? 'X' : '&nbsp;' }}</span> SI SOY &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->pep_collaborator_status === 'SI_HE_SIDO' ? 'X' : '&nbsp;' }}</span> SI HE SIDO &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->pep_collaborator_status === 'NO_SOY' ? 'X' : '&nbsp;' }}</span> NO SOY &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->pep_collaborator_status === 'NO_HE_SIDO' ? 'X' : '&nbsp;' }}</span> NO HE SIDO
    </div>

    @if($isPep)
    <div class="pep-detail mt4">
      <span class="lbl">Si marcó "SI SOY" o "SI HE SIDO", complete la siguiente información:</span>
      <table class="data-table mt4">
        <tr>
          <td width="40%">
            <span class="lbl">Cargo</span>
            <span class="val">{{ $declaration->pep_position ?? '—' }}</span>
          </td>
          <td width="60%">
            <span class="lbl">Nombre de la institución (organismo público u organización internacional)</span>
            <span class="val">{{ $declaration->pep_institution ?? '—' }}</span>
          </td>
        </tr>
      </table>
    </div>

    {{-- 10.2 Familiares del PEP --}}
    <div class="sub-title mt6">10.2 Nombres y apellidos de parientes (hasta 2° grado consanguinidad y 2° afinidad) y cónyuge/conviviente:</div>
    @if(!empty($declaration->pep_relatives))
    <table class="relatives-table">
      <tr>
        <th>#</th>
        <th>Nombres y Apellidos del Pariente</th>
      </tr>
      @foreach($declaration->pep_relatives as $i => $relative)
      <tr>
        <td width="8%" class="text-center">{{ $i + 1 }}</td>
        <td>{{ $relative ?? '—' }}</td>
      </tr>
      @endforeach
    </table>
    @else
    <div class="pep-detail">No se registraron parientes.</div>
    @endif

    @if($declaration->pep_spouse_name)
    <div class="mt4"><span class="lbl">Cónyuge o conviviente:</span> <span class="val">{{ $declaration->pep_spouse_name }}</span></div>
    @endif
    @endif

    {{-- 10.3 Pariente de PEP --}}
    <div class="sub-title mt6">10.3 ¿Es pariente de PEP hasta el 2° grado de consanguinidad o afinidad, o cónyuge/conviviente?</div>
    <div class="check-row">
      <span class="check-box">{{ $declaration->is_pep_relative === 'SI_SOY' ? 'X' : '&nbsp;' }}</span> SI SOY &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->is_pep_relative === 'NO_SOY' ? 'X' : '&nbsp;' }}</span> NO SOY
    </div>

    @if($isPepRel && !empty($declaration->pep_relative_data))
    <table class="relatives-table mt4">
      <tr>
        <th>#</th>
        <th>Nombres y Apellidos del PEP</th>
        <th>Parentesco</th>
      </tr>
      @foreach($declaration->pep_relative_data as $i => $rel)
      <tr>
        <td width="6%" class="text-center">{{ $i + 1 }}</td>
        <td>{{ $rel['pep_full_name'] ?? '—' }}</td>
        <td>{{ $rel['relationship'] ?? '—' }}</td>
      </tr>
      @endforeach
    </table>
    @endif
  </div>
</div>

{{-- ══════════════════════════ BENEFICIARIO 11 ══════════════════════════ --}}
<div class="section">
  <div class="section-title">11. Identidad del Beneficiario de la Operación</div>
  <div class="section-body">

    <div class="check-row">
      Realizo esta operación a favor de: &nbsp;
      <span class="check-box">{{ $declaration->beneficiary_type === 'PROPIO' ? 'X' : '&nbsp;' }}</span> 1. De mí mismo &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->beneficiary_type === 'TERCERO_NATURAL' ? 'X' : '&nbsp;' }}</span> 2. Tercero persona natural &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->beneficiary_type === 'PERSONA_JURIDICA' ? 'X' : '&nbsp;' }}</span> 3. Persona jurídica &nbsp;&nbsp;
      <span class="check-box">{{ $declaration->beneficiary_type === 'ENTE_JURIDICO' ? 'X' : '&nbsp;' }}</span> 4. Ente jurídico
    </div>

    {{-- 11.1 Propio --}}
    @if($declaration->beneficiary_type === 'PROPIO')
    <div class="sub-title mt6">11.1 Operación a favor de sí mismo</div>
    <table class="data-table">
      <tr>
        <td>
          <span class="lbl">i) Origen de los fondos/activos</span>
          <span class="val">{{ $declaration->own_funds_origin ?? '—' }}</span>
        </td>
      </tr>
    </table>
    @endif

    {{-- 11.2 Tercero Persona Natural --}}
    @if($declaration->beneficiary_type === 'TERCERO_NATURAL')
    <div class="sub-title mt6">11.2 Operación a favor de tercero persona natural</div>
    <table class="data-table">
      <tr>
        <td width="50%">
          <span class="lbl">i) Nombres y apellidos del tercero</span>
          <span class="val">{{ $declaration->third_full_name ?? '—' }}</span>
        </td>
        <td width="25%">
          <span class="lbl">ii) Tipo de documento</span>
          <span class="val">{{ $declaration->third_doc_type ?? '—' }}</span>
        </td>
        <td width="25%">
          <span class="lbl">N° de documento</span>
          <span class="val">{{ $declaration->third_doc_number ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <span class="lbl">iii) Datos de la representación</span>
          <div class="check-row">
            <span class="check-box">{{ $declaration->third_representation_type === 'ESCRITURA_PUBLICA' ? 'X' : '&nbsp;' }}</span> Poder por Escritura Pública &nbsp;
            <span class="check-box">{{ $declaration->third_representation_type === 'MANDATO' ? 'X' : '&nbsp;' }}</span> Mandato &nbsp;
            <span class="check-box">{{ $declaration->third_representation_type === 'PODER' ? 'X' : '&nbsp;' }}</span> Poder &nbsp;
            <span class="check-box">{{ $declaration->third_representation_type === 'OTROS' ? 'X' : '&nbsp;' }}</span> Otros
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <span class="lbl">iv) ¿El tercero es o ha sido PEP?</span>
          <div class="check-row">
            <span class="check-box">{{ $declaration->third_pep_status === 'SI_ES' ? 'X' : '&nbsp;' }}</span> SI ES &nbsp;
            <span class="check-box">{{ $declaration->third_pep_status === 'SI_HA_SIDO' ? 'X' : '&nbsp;' }}</span> SI HA SIDO &nbsp;
            <span class="check-box">{{ $declaration->third_pep_status === 'NO_ES' ? 'X' : '&nbsp;' }}</span> NO ES &nbsp;
            <span class="check-box">{{ $declaration->third_pep_status === 'NO_HA_SIDO' ? 'X' : '&nbsp;' }}</span> NO HA SIDO
          </div>
          @if(in_array($declaration->third_pep_status, ['SI_ES', 'SI_HA_SIDO']))
          <div class="pep-detail mt4">
            <table class="data-table">
              <tr>
                <td width="40%"><span class="lbl">Cargo</span><span class="val">{{ $declaration->third_pep_position ?? '—' }}</span></td>
                <td><span class="lbl">Institución</span><span class="val">{{ $declaration->third_pep_institution ?? '—' }}</span></td>
              </tr>
            </table>
          </div>
          @endif
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <span class="lbl">v) Origen de los fondos/activos involucrados en la operación</span>
          <span class="val">{{ $declaration->third_funds_origin ?? '—' }}</span>
        </td>
      </tr>
    </table>
    @endif

    {{-- 11.3 Persona Jurídica / Ente Jurídico --}}
    @if(in_array($declaration->beneficiary_type, ['PERSONA_JURIDICA', 'ENTE_JURIDICO']))
    <div class="sub-title mt6">11.3 Operación a favor de persona jurídica o ente jurídico</div>
    <table class="data-table">
      <tr>
        <td width="60%">
          <span class="lbl">i) Denominación o Razón Social</span>
          <span class="val">{{ $declaration->entity_name ?? '—' }}</span>
        </td>
        <td width="40%">
          <span class="lbl">ii) N° de RUC</span>
          <span class="val">{{ $declaration->entity_ruc ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <span class="lbl">iii) Datos de la representación</span>
          <div class="check-row">
            <span class="check-box">{{ $declaration->entity_representation_type === 'PODER_POR_ACTA' ? 'X' : '&nbsp;' }}</span> Poder por Acta &nbsp;
            <span class="check-box">{{ $declaration->entity_representation_type === 'ESCRITURA_PUBLICA' ? 'X' : '&nbsp;' }}</span> Poder por Escritura Pública &nbsp;
            <span class="check-box">{{ $declaration->entity_representation_type === 'MANDATO' ? 'X' : '&nbsp;' }}</span> Mandato
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <span class="lbl">iv) Origen de los fondos/activos involucrados en la operación</span>
          <span class="val">{{ $declaration->entity_funds_origin ?? '—' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <span class="lbl">v) Identificación del Beneficiario Final (conforme al D.Leg. N° 1372 y modificatorias)</span>
          <span class="val">{{ $declaration->entity_final_beneficiary ?? '—' }}</span>
        </td>
      </tr>
    </table>
    @endif

  </div>
</div>

{{-- ══════════════════════════ FIRMA ══════════════════════════ --}}
<div class="signature-section">
  <p style="font-size:8px; margin-bottom:8px;">
    Afirmo y ratifico todo lo manifestado en la presente declaración jurada:
  </p>
  <div class="sig-row">
    <div class="sig-cell">
      <div class="sig-line">
        FECHA: {{ $declaration->declaration_date->format('d') }} /
               {{ $declaration->declaration_date->format('m') }} /
               {{ $declaration->declaration_date->format('Y') }}
      </div>
    </div>
    <div class="sig-cell">
      <div class="sig-line">FIRMA DEL DECLARANTE</div>
    </div>
  </div>
</div>

{{-- NOTA AL PIE --}}
<div class="footer-note">
  Nota: Para ser conservada por el sujeto obligado y, en su caso, exhibida a solicitud de la UIF-Perú en actividades de supervisión.
  No se envía a la UIF-Perú, salvo solicitud expresa.
</div>

</body>
</html>
