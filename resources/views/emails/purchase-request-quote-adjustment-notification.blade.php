@extends('emails.layouts.main')

@section('title', 'Ajuste de margen pendiente.')

@section('subtitle')
  Se requiere tu aprobación para proceder.
@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        <strong style="font-weight:600;">{{ $requester_name }}</strong> ha solicitado modificar los bonos/descuentos de una cotización ya pagada. Esto mueve el margen real del vehículo, por lo que requiere tu revisión y aprobación.
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

        @if($holder_name)
        {{-- Titular --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/briefcase.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $holder_name }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Titular</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

        {{-- Solicitante --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/user.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $requester_name }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Solicitado por</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        @if($reason)
        {{-- Motivo --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/message-square.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.3;">{{ $reason }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Motivo del ajuste</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

        {{-- Margen actual --}}
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
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Margen actual</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Margen simulado --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/trending-up.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $currency_symbol }} {{ number_format($margin_amount_after, 2) }} ({{ number_format($margin_pct_after, 2) }}%)</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Margen simulado con el ajuste</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        @if(!empty($items))
        {{-- Cambios solicitados --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:top;padding-right:12px;padding-top:2px;">
                  <img src="https://api.iconify.design/lucide/list.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 6px 0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Cambios solicitados</p>
                  @foreach($items as $item)
                    <p style="margin:0 0 4px 0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.6;color:#111111;">
                      {{ ['create' => 'Agregar', 'update' => 'Editar', 'delete' => 'Eliminar'][$item['action']] ?? $item['action'] }} &middot; {{ $item['concept'] ?? '-' }}
                      @if(($item['action'] ?? null) !== 'delete')
                        &mdash; {{ $currency_symbol }} {{ number_format($item['previous_precio_unitario'] ?? 0, 2) }} &rarr; {{ $currency_symbol }} {{ number_format($item['new_precio_unitario'] ?? 0, 2) }}
                      @endif
                    </p>
                  @endforeach
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

      </table>
    </td>
  </tr>

  {{-- Mensaje --}}
  <tr>
    <td style="padding:20px 0 32px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.7;color:#6b7280;">
        Revisa el detalle y aprueba o rechaza esta solicitud a la brevedad posible.
      </p>
    </td>
  </tr>

  {{-- Botón --}}
  <tr>
    <td align="center" style="padding-bottom:40px;">
      <a href="{{ $button_url }}"
         style="display:inline-block;padding:13px 28px;background:#111111;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;line-height:1;text-decoration:none;border-radius:8px;">
        Ver Solicitud de Ajuste
      </a>
    </td>
  </tr>
@endsection
