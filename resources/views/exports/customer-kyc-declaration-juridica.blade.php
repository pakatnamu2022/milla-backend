@php
  function kycLegalBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType  = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }

  $partner = $declaration->businessPartner;

  $repDocDesc = strtoupper($declaration->rep_doc_type ?? '');
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Declaración Jurada KYC – Persona Jurídica</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      color: #22293a;
      background: #fff;
      padding: 0 0 100px;
    }

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

    .page-header { padding: 5px 10px 5px; margin-bottom: 6px; }
    .header-inner { display: table; width: 100%; border-radius: 6px; overflow: hidden; }
    .h-logo { display: table-cell; width: 140px; text-align: center; vertical-align: middle; padding: 4px 8px; }
    .h-logo img { max-height: 34px; width: auto; display: block; margin: 0 auto; }
    .h-title { display: table-cell; vertical-align: middle; text-align: center; padding: 4px 10px; }
    .h-title-main { font-size: 12px; font-weight: bold; color: #22293a; letter-spacing: 0.3px; line-height: 1.3; }
    .h-title-sub { font-size: 8px; color: #888888; margin-top: 1px; }

    .content { padding: 0 10px; }

    .card { border: 1px solid #d2d2d2; border-radius: 5px; overflow: hidden; margin-bottom: 6px; }
    .card-title { background-color: #e0e0e0; color: #22293a; font-weight: bold; font-size: 10px; padding: 3px 10px; letter-spacing: 0.3px; }

    table.dt { width: 100%; border-collapse: collapse; }
    table.dt td { padding: 3px 8px; border-bottom: 1px solid #ebebeb; border-right: 1px solid #ebebeb; font-size: 11px; vertical-align: top; }
    table.dt td:last-child { border-right: none; }
    table.dt tr:last-child td { border-bottom: none; }

    .lbl { font-weight: bold; color: #22293a; background: #f5f5f5; white-space: nowrap; }

    .sub-lbl { background: #f5f5f5; font-weight: bold; color: #22293a; font-size: 10px; padding: 4px 8px; border-bottom: 1px solid #ebebeb; }

    .chk {
      display: inline-block;
      width: 13px; height: 13px;
      border: 1.5px solid #aaaaaa;
      border-radius: 2px;
      vertical-align: middle;
      margin-left: 4px;
      background: #fff;
      font-size: 10px; font-weight: bold;
      text-align: center; line-height: 13px;
      color: #ffffff;
    }
    .chk.on { color: #000000; }

    .sig-wrap { border: 1px solid #d2d2d2; border-radius: 5px; overflow: hidden; display: table; width: 100%; margin-bottom: 6px; }
    .sig-col { display: table-cell; width: 50%; vertical-align: top; border-right: 1px solid #d2d2d2; }
    .sig-col:last-child { border-right: none; }
    .sig-date-row { text-align: center; font-size: 10px; font-weight: bold; margin-bottom: 4px; color: #22293a; }
    .sig-hdr { background: #e0e0e0; color: #000000; font-weight: bold; font-size: 10px; text-align: center; padding: 3px 8px; }
    .sig-body { padding: 6px 12px; }
    .sig-line { height: 50px; }
    .sig-line-tall { height: 80px; }
    .sig-sub { font-size: 9px; color: #777777; margin-top: 3px; text-align: center; }

    .notes {
      position: fixed; bottom: 20px; left: 10px; right: 10px;
      border: 1px solid #d2d2d2; border-radius: 5px;
      padding: 5px 10px; font-size: 8px; color: #4a5568; background: #fafafa;
    }

    .foot {
      position: fixed; bottom: 0; left: 10px; right: 10px;
      border-top: 1px solid #d2d2d2; padding: 4px 0 2px;
      text-align: center; background: #fff;
    }
    .foot img { height: 11px; width: auto; margin: 0 4px; }
  </style>
</head>
<body>

<div class="watermark">PAKATNAMU</div>

{{-- ── ENCABEZADO ──────────────────────────────── --}}
<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      <img src="{{ kycLegalBase64Image('img/logo-milla.png') }}" alt="Logo">
    </div>
    <div class="h-title">
      <div class="h-title-main">DECLARACIÓN JURADA DE CONOCIMIENTO DEL CLIENTE</div>
      <div class="h-title-main">RÉGIMEN GENERAL – PERSONA JURÍDICA</div>
      <div class="h-title-sub">Conforme al D.Leg. N° 1372 y normativa SBS/UIF-Perú</div>
    </div>
  </div>
</div>

<p style="padding: 0 10px; font-size:9px; margin-bottom:5px; color:#555;">
  Por el presente documento, declaro bajo juramento lo siguiente:
</p>

<div class="content">

  {{-- ── 1-5. DATOS DE LA PERSONA JURÍDICA ─────── --}}
  <div class="card">
    <div class="card-title">1–5. DATOS DE LA PERSONA JURÍDICA</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:22%;">1. Denominación / Razón Social</td>
        <td colspan="3">{{ $declaration->company_name ?? $partner?->full_name ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">2. N° de RUC</td>
        <td style="width:28%;">{{ $declaration->ruc ?? $partner?->num_doc ?? '—' }}</td>
        <td class="lbl" style="width:22%;">Registro equivalente (no domiciliados)</td>
        <td>{{ $declaration->foreign_registry_number ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">3. Objeto social / Actividad económica principal</td>
        <td colspan="3">{{ $declaration->business_purpose ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">4. Identificación de Beneficiarios Finales (D.Leg. N° 1372)</td>
        <td colspan="3">{{ $declaration->final_beneficiaries ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">5. Propósito de la relación con el sujeto obligado</td>
        <td colspan="3">{{ $declaration->purpose_relationship ?? '—' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── 6. DATOS DEL REPRESENTANTE (EJECUTANTE) ── --}}
  <div class="card">
    <div class="card-title">6. DATOS DEL REPRESENTANTE (EJECUTANTE)</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:22%;">a) Nombres y Apellidos</td>
        <td colspan="3">{{ $declaration->rep_full_name ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">b) Tipo y N° de documento</td>
        <td colspan="3">
          DNI <span class="chk {{ $repDocDesc === 'DNI' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Pasaporte <span class="chk {{ $repDocDesc === 'PASAPORTE' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Carné de Extranjería <span class="chk {{ $repDocDesc === 'CARNE_EXTRANJERIA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Otro: {{ $declaration->rep_doc_other ?? '___________' }}
          &nbsp;&nbsp;&nbsp;
          <strong>N°: {{ $declaration->rep_doc_number ?? '—' }}</strong>
        </td>
      </tr>
      <tr>
        <td class="lbl">c) Representación</td>
        <td colspan="3">
          Poder <span class="chk {{ $declaration->rep_representation_type === 'PODER' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Mandato <span class="chk {{ $declaration->rep_representation_type === 'MANDATO' ? 'on' : '' }}">X</span>
        </td>
      </tr>
      <tr>
        <td class="sub-lbl" colspan="4">Instrumento Público Notarial</td>
      </tr>
      <tr>
        <td colspan="4" style="padding: 4px 8px;">
          Escritura Pública <span class="chk {{ $declaration->rep_instrument_type === 'ESCRITURA_PUBLICA' ? 'on' : '' }}">X</span>
          @if($declaration->rep_instrument_type === 'ESCRITURA_PUBLICA')
            &nbsp; Fecha: <strong>{{ $declaration->rep_escritura_date?->format('d/m/Y') ?? '—' }}</strong>
            &nbsp;&nbsp; Notario: <strong>{{ $declaration->rep_notary_name ?? '—' }}</strong>
          @endif
          <br>
          Copia Certificada de Acta <span class="chk {{ $declaration->rep_instrument_type === 'COPIA_CERTIFICADA_ACTA' ? 'on' : '' }}">X</span>
          @if($declaration->rep_instrument_type === 'COPIA_CERTIFICADA_ACTA')
            &nbsp; Fecha copia: <strong>{{ $declaration->rep_acta_certified_date?->format('d/m/Y') ?? '—' }}</strong>
            &nbsp;&nbsp; Fecha acta: <strong>{{ $declaration->rep_acta_date?->format('d/m/Y') ?? '—' }}</strong>
          @endif
          <br>
          Otros <span class="chk {{ $declaration->rep_instrument_type === 'OTROS' ? 'on' : '' }}">X</span>
          @if($declaration->rep_instrument_type === 'OTROS')
            &nbsp; {{ $declaration->rep_instrument_other ?? '—' }}
          @endif
        </td>
      </tr>
      <tr>
        <td class="sub-lbl" colspan="4">Datos de Inscripción Registral</td>
      </tr>
      <tr>
        <td class="lbl" style="width:22%;">Partida N°</td>
        <td style="width:28%;">{{ $declaration->rep_registry_partition ?? '—' }}</td>
        <td class="lbl">Asiento N°</td>
        <td>{{ $declaration->rep_registry_seat ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">Rubro</td>
        <td>{{ $declaration->rep_registry_section ?? '—' }}</td>
        <td class="lbl">Zona Registral N°</td>
        <td>{{ $declaration->rep_registry_zone ?? '—' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── 7. DIRECCIÓN DE OFICINA ──────────────────── --}}
  <div class="card">
    <div class="card-title">7. DIRECCIÓN DE LA OFICINA O LOCAL PRINCIPAL</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:22%;">7.1 Tipo y nombre de vía</td>
        <td colspan="3">
          @php
            $streetLabel = match($declaration->office_street_type) {
              'JR'     => 'Jr.',
              'AV'     => 'Av.',
              'CALLE'  => 'Calle',
              'PASAJE' => 'Pasaje',
              'OVALO'  => 'Óvalo',
              default  => '—',
            };
          @endphp
          {{ $streetLabel }} {{ $declaration->office_street_name ?? '' }}
          &nbsp;&nbsp;
          N°: {{ $declaration->office_number ?? '—' }}
          &nbsp;&nbsp;
          Of./Int. N°: {{ $declaration->office_int_number ?? '—' }}
        </td>
      </tr>
      <tr>
        <td class="lbl">Urb. / Complejo / Zona / Sector</td>
        <td colspan="3">{{ $declaration->office_urbanization ?? '—' }}</td>
      </tr>
      <tr>
        <td class="lbl">Dist. / Prov. / Depto.</td>
        <td colspan="3">
          @php
            $dist = $declaration->officeDistrict;
            $parts = array_filter([
              $dist?->name ?? '',
              $dist?->province?->name ?? '',
              $dist?->province?->department?->name ?? '',
            ]);
          @endphp
          {{ implode(' / ', $parts) ?: '—' }}
        </td>
      </tr>
      <tr>
        <td class="lbl">7.2 N° Teléfono</td>
        <td colspan="3">{{ $declaration->office_phone ?? '—' }}</td>
      </tr>
    </table>
  </div>

  {{-- ── 8. IDENTIDAD DEL BENEFICIARIO ─────────── --}}
  <div class="card">
    <div class="card-title">8. IDENTIDAD DEL BENEFICIARIO DE LA OPERACIÓN</div>
    <table class="dt">
      <tr>
        <td colspan="4">
          Realizo esta operación a favor de: &nbsp;
          1. De mí mismo <span class="chk {{ $declaration->beneficiary_type === 'PROPIO' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          2. Tercero persona natural <span class="chk {{ $declaration->beneficiary_type === 'TERCERO_NATURAL' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          3. Tercero persona jurídica <span class="chk {{ $declaration->beneficiary_type === 'PERSONA_JURIDICA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          4. Tercero ente jurídico <span class="chk {{ $declaration->beneficiary_type === 'ENTE_JURIDICO' ? 'on' : '' }}">X</span>
        </td>
      </tr>

      {{-- 8.1 Propio --}}
      @if($declaration->beneficiary_type === 'PROPIO')
      <tr>
        <td class="sub-lbl" colspan="4">8.1 Operación a favor de sí mismo</td>
      </tr>
      <tr>
        <td class="lbl" style="width:26%;">i) Origen de los fondos/activos</td>
        <td colspan="3">{{ $declaration->own_funds_origin ?? '—' }}</td>
      </tr>
      @endif

      {{-- 8.2 Tercero persona natural --}}
      @if($declaration->beneficiary_type === 'TERCERO_NATURAL')
      <tr>
        <td class="sub-lbl" colspan="4">8.2 Operación a favor de tercero persona natural</td>
      </tr>
      <tr>
        <td class="lbl" style="width:26%;">i) Nombres y apellidos</td>
        <td style="width:24%;">{{ $declaration->third_full_name ?? '—' }}</td>
        <td class="lbl" style="width:18%;">ii) Tipo y N° doc.</td>
        <td>{{ ($declaration->third_doc_type ?? '—') . ' ' . ($declaration->third_doc_number ?? '') }}</td>
      </tr>
      <tr>
        <td class="lbl">iii) Datos de la representación</td>
        <td colspan="3">
          Poder por Escritura Pública <span class="chk {{ $declaration->third_representation_type === 'PODER_ESCRITURA_PUBLICA' ? 'on' : '' }}">X</span>
          &nbsp;&nbsp;
          Mandato <span class="chk {{ $declaration->third_representation_type === 'MANDATO' ? 'on' : '' }}">X</span>
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

      {{-- 8.3 Persona jurídica / Ente jurídico --}}
      @if(in_array($declaration->beneficiary_type, ['PERSONA_JURIDICA', 'ENTE_JURIDICO']))
      <tr>
        <td class="sub-lbl" colspan="4">8.3 Operación a favor de tercero persona jurídica o ente jurídico</td>
      </tr>
      <tr>
        <td class="lbl" style="width:26%;">i) Denominación / Razón Social</td>
        <td style="width:24%;">{{ $declaration->entity_name ?? '—' }}</td>
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

  {{-- ── 9. NÚMERO DE CUENTA / BILLETERA ─────────── --}}
  @if($declaration->account_number)
  <div class="card">
    <div class="card-title">9. NÚMERO DE CUENTA / BILLETERA DE ACTIVOS VIRTUALES</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:22%;">N° de cuenta / Dirección billetera</td>
        <td>{{ $declaration->account_number }}</td>
      </tr>
    </table>
  </div>
  @endif

  {{-- ── FIRMA ────────────────────────────────────── --}}
  <div class="sig-date-row">
    Fecha de Declaración: {{ $declaration->declaration_date->format('d') }} / {{ $declaration->declaration_date->format('m') }} / {{ $declaration->declaration_date->format('Y') }}
  </div>
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig-hdr">SELLO DE LA EMPRESA</div>
      <div class="sig-body">
        <div class="sig-line sig-line-tall"></div>
      </div>
    </div>
    <div class="sig-col">
      <div class="sig-hdr">FIRMA DEL REPRESENTANTE</div>
      <div class="sig-body">
        <div class="sig-line"></div>
        <div class="sig-sub">{{ $declaration->rep_full_name ?? '' }}</div>
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
    <img src="{{ kycLegalBase64Image('images/ap/brands/suzuki.png') }}" alt="Suzuki">
    <img src="{{ kycLegalBase64Image('images/ap/brands/subaru.png') }}" alt="Subaru">
    <img src="{{ kycLegalBase64Image('images/ap/brands/dfsk.png') }}" alt="DFSK">
    <img src="{{ kycLegalBase64Image('images/ap/brands/mazda.png') }}" alt="Mazda">
    <img src="{{ kycLegalBase64Image('images/ap/brands/citroen.jpg') }}" alt="Citroën">
    <img src="{{ kycLegalBase64Image('images/ap/brands/renault.png') }}" alt="Renault">
    <img src="{{ kycLegalBase64Image('images/ap/brands/haval.png') }}" alt="Haval">
    <img src="{{ kycLegalBase64Image('images/ap/brands/great-wall.png') }}" alt="Great Wall">
    <img src="{{ kycLegalBase64Image('images/ap/brands/changan.png') }}" alt="Changan">
    <img src="{{ kycLegalBase64Image('images/ap/brands/jac.png') }}" alt="JAC">
  </div>

</div>{{-- /content --}}

</body>
</html>
