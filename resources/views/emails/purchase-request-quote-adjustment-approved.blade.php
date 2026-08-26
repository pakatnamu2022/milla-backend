@extends('emails.layouts.main')

@section('title', 'Ajuste de margen aprobado.')

@section('subtitle')
  Los cambios ya fueron aplicados a la cotización.
@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        Tu solicitud de ajuste de bono/descuento para la cotización <strong style="font-weight:600;">#{{ $quote_number }}</strong>
        ha sido <strong style="font-weight:600;color:#15803d;">aprobada</strong>@if($resolver_name) por <strong style="font-weight:600;">{{ $resolver_name }}</strong>@endif.
        El margen del vehículo ya fue actualizado.
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

        {{-- Margen antes --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/percent.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $currency_symbol }} {{ number_format($margin_amount_before, 2) }} ({{ number_format($margin_pct_before, 2) }}%)</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Margen antes</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Margen ahora --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/trending-up.svg?color=%2315803d&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#15803d;line-height:1.2;">{{ $currency_symbol }} {{ number_format($margin_amount_after, 2) }} ({{ number_format($margin_pct_after, 2) }}%)</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Margen ahora</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

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
