@extends('emails.layouts.per-diem')

@section('title', 'Solicitud aprobada.')

@section('subtitle')
  @if($recipient_type === 'employee')
    Tu solicitud de viáticos fue aprobada.
  @elseif($recipient_type === 'boss')
    La solicitud de {{ $employee_name }} fue aprobada.
  @else
    La solicitud fue aprobada exitosamente.
  @endif
@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        @if($recipient_type === 'employee')
          Hola <strong style="font-weight:600;">{{ $employee_name }}</strong>,
        @else
          Estimado/a,
        @endif
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

        {{-- Presupuesto aprobado --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/banknote.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                    S/ {{ number_format($total_budget, 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Presupuesto aprobado</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Aprobado por (opcional) --}}
        @isset($approver_name)
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
                    <p
                      style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $approver_name }}</p>
                    <p
                      style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                      Aprobado por</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endisset

      </table>
    </td>
  </tr>

  {{-- Mensaje --}}
  <tr>
    <td style="padding:20px 0 32px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.7;color:#6b7280;">
        @if($recipient_type === 'employee')
          El desembolso será procesado pronto. Te notificaremos sobre los siguientes pasos.
        @elseif($recipient_type === 'boss')
          La solicitud será procesada por contabilidad para el desembolso correspondiente.
        @else
          Por favor, procede con el desembolso según los montos aprobados.
        @endif
      </p>
    </td>
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
