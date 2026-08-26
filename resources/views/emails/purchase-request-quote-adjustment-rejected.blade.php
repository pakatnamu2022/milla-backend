@extends('emails.layouts.main')

@section('title', 'Ajuste de margen rechazado.')

@section('subtitle')
  No se realizó ningún cambio en la cotización.
@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        Tu solicitud de ajuste de bono/descuento para la cotización <strong style="font-weight:600;">#{{ $quote_number }}</strong>
        ha sido <strong style="font-weight:600;color:#dc2626;">rechazada</strong>@if($resolver_name) por <strong style="font-weight:600;">{{ $resolver_name }}</strong>@endif.
        El margen se mantiene sin variación.
      </p>
    </td>
  </tr>

  {{-- Campos --}}
  <tr>
    <td>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- Cotización --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/file-text.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">Cotización #{{ $quote_number }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Cotización</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        @if($rejection_reason)
        {{-- Motivo del rechazo --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/alert-circle.svg?color=%23dc2626&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.3;">{{ $rejection_reason }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Motivo del rechazo</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

      </table>
    </td>
  </tr>

  {{-- Botón --}}
  <tr>
    <td align="center" style="padding:20px 0 40px 0;">
      <a href="{{ $button_url }}"
         style="display:inline-block;padding:13px 28px;background:#111111;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;line-height:1;text-decoration:none;border-radius:8px;">
        Ver Solicitud de Ajuste
      </a>
    </td>
  </tr>
@endsection
