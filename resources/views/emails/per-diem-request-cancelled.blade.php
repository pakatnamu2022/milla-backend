@extends('emails.layouts.per-diem')

@section('title', 'Solicitud cancelada.')

@section('subtitle', 'Tu solicitud de viáticos fue cancelada.')

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

        {{-- Código --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/hash.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $request_code }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Código</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Destino --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/map-pin.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $destination }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Destino</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Fechas --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/calendar.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $start_date }}
                    — {{ $end_date }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Fechas</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Motivo de cancelación --}}
        @if(isset($cancellation_reason) && $cancellation_reason)
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/x-circle.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p
                      style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.3;">{{ $cancellation_reason }}</p>
                    <p
                      style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                      Motivo de cancelación</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

      </table>
    </td>
  </tr>

  {{-- Spacer --}}
  <tr>
    <td style="padding-bottom:32px;font-size:0;line-height:0;">&nbsp;</td>
  </tr>

  {{-- Botón --}}
  @isset($button_url)
    <tr>
      <td align="center" style="padding-bottom:40px;">
        <a href="{{ $button_url }}"
           style="display:inline-block;padding:13px 28px;background:#01237e;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;line-height:1;text-decoration:none;border-radius:8px;">
          Ver solicitud
        </a>
      </td>
    </tr>
  @endisset
@endsection
