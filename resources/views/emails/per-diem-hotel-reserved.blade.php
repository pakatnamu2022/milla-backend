@extends('emails.layouts.per-diem')

@section('title', 'Reserva confirmada.')

@section('subtitle', 'Tu alojamiento fue reservado.')

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        Hola <strong style="font-weight:600;">{{ $employee_name }}</strong>,
      </p>
    </td>
  </tr>

  {{-- Campos --}}
  <tr>
    <td>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td colspan="2" style="padding:16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $hotel_name }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Hotel</p>
          </td>
        </tr>
        @if(isset($address) && $address)
          <tr>
            <td colspan="2" style="padding:16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.3;">{{ $address }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Dirección</p>
            </td>
          </tr>
        @endif
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $checkin_date }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Check-in</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $checkout_date }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Check-out</p>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="padding:16px 0;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $nights_count }} {{ $nights_count == 1 ? 'noche' : 'noches' }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Estadía</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Mensaje --}}
  <tr>
    <td style="padding:20px 0 32px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.7;color:#6b7280;">
        Tu solicitud de viáticos está ahora en progreso. Confirma tu reserva directamente con el hotel.
      </p>
    </td>
  </tr>

  {{-- Botón --}}
  @isset($button_url)
    <tr>
      <td align="center" style="padding-bottom:40px;">
        <a href="{{ $button_url }}"
           style="display:inline-block;padding:13px 28px;background:#01237e;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;line-height:1;text-decoration:none;border-radius:8px;">
          Ver detalles
        </a>
      </td>
    </tr>
  @endisset
@endsection
