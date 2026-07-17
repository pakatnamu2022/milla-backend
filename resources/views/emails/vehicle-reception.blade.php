@extends('emails.layouts.main')

@section('email_subject')Recepción de Vehículo — VIN {{ $vehicle_vin }}@endsection

@section('title')Recepción de vehículo@endsection

@section('subtitle')
  VIN {{ $vehicle_vin }} &middot; {{ $vehicle_brand }} {{ $vehicle_model }}
@endsection

@section('content')
<tr>
<td>
  @php $font = "system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif"; @endphp

  {{-- Badge de estado --}}
  <p style="margin:0 0 28px 0;">
    <span style="display:inline-block;padding:5px 14px;border-radius:20px;
                 background:#dcfce7;color:#15803d;
                 font-family:{{ $font }};font-size:12px;font-weight:600;letter-spacing:0.3px;">
      ✓ Recepcionado satisfactoriamente
    </span>
  </p>

  {{-- ── VEHÍCULO ── --}}
  <p style="margin:0 0 4px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
            letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">
    Vehículo
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

    {{-- VIN --}}
    <tr>
      <td style="padding:14px 0;border-bottom:1px solid #f0f0f2;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/hash.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $vehicle_vin }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">VIN</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Marca / Modelo --}}
    <tr>
      <td style="padding:14px 0;border-bottom:1px solid #f0f0f2;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/car.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $vehicle_brand }} {{ $vehicle_model }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Marca / Modelo</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Año --}}
    <tr>
      <td style="padding:14px 0;border-bottom:1px solid #f0f0f2;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/calendar.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $vehicle_year }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Año</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Color --}}
    <tr>
      <td style="padding:14px 0;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/palette.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $vehicle_color }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Color</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </table>

  {{-- Separador --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin:8px 0 24px 0;">
    <tr><td style="height:1px;background:#f0f0f2;font-size:0;line-height:0;">&nbsp;</td></tr>
  </table>

  {{-- ── RECEPCIÓN ── --}}
  <p style="margin:0 0 4px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
            letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">
    Recepción
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

    {{-- Origen --}}
    <tr>
      <td style="padding:14px 0;border-bottom:1px solid #f0f0f2;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/map-pin.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $origin }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Origen</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Destino --}}
    <tr>
      <td style="padding:14px 0;border-bottom:1px solid #f0f0f2;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/navigation.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $destination }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Destino</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Fecha de recepción --}}
    <tr>
      <td style="padding:14px 0;border-bottom:1px solid #f0f0f2;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/clock.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $received_date }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Fecha de recepción</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Recepcionado por --}}
    <tr>
      <td style="padding:14px 0;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="width:44px;vertical-align:middle;padding-right:12px;">
              <img src="https://api.iconify.design/lucide/user.svg?color=%23111111&width=28&height=28" alt=""
                   width="28" height="28"
                   style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
            </td>
            <td style="vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $received_by }}</p>
              <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Recepcionado por</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </table>

  {{-- ── CHECKLIST ── --}}
  @if(count($received_items) > 0)
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="margin:8px 0 24px 0;">
      <tr><td style="height:1px;background:#f0f0f2;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>

    <p style="margin:0 0 4px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
              letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">
      Checklist recepcionado
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
      @foreach($received_items as $item)
        <tr>
          <td style="padding:12px 0;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f2;' : '' }}">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/check-circle.svg?color=%2315803d&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:middle;">
                  <p style="margin:0;font-family:{{ $font }};font-size:14px;font-weight:500;color:#111111;line-height:1.3;">
                    {{ $item['name'] }}
                    <span style="font-size:12px;font-weight:400;color:#aeaeb2;margin-left:6px;">× {{ $item['quantity'] }}</span>
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      @endforeach
    </table>
  @endif

  {{-- ── OBSERVACIONES ── --}}
  @if(!empty($note))
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="margin:8px 0 24px 0;">
      <tr><td style="height:1px;background:#f0f0f2;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>

    <p style="margin:0 0 8px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
              letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">
      Observaciones
    </p>
    <p style="margin:0;font-family:{{ $font }};font-size:13px;line-height:1.7;
              color:#3a3a3c;border-left:3px solid #e5e5e7;padding-left:14px;">
      {{ $note }}
    </p>
  @endif

  {{-- Fotos adjuntas --}}
  @if(!empty($has_photos))
    <p style="margin:20px 0 0 0;font-family:{{ $font }};font-size:13px;line-height:1.6;color:#6b7280;">
      Se adjuntan fotografías del vehículo al presente correo.
    </p>
  @endif

  {{-- Nota de coordinación --}}
  <p style="margin:20px 0 0 0;font-family:{{ $font }};font-size:13px;line-height:1.7;color:#3a3a3c;">
    Por favor, coordinar fecha de entrega y lavado del vehículo.
  </p>

  <div style="height:8px;font-size:0;"></div>
</td>
</tr>
@endsection
