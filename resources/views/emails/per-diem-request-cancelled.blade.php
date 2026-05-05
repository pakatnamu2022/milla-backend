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
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $request_code }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Código</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $destination }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Destino</p>
          </td>
        </tr>
        @if(isset($cancellation_reason) && $cancellation_reason)
          <tr>
            <td colspan="2" style="padding:16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $start_date }} — {{ $end_date }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Fechas</p>
            </td>
          </tr>
          <tr>
            <td colspan="2" style="padding:16px 0;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.3;">{{ $cancellation_reason }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Motivo de cancelación</p>
            </td>
          </tr>
        @else
          <tr>
            <td colspan="2" style="padding:16px 0;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $start_date }} — {{ $end_date }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Fechas</p>
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
