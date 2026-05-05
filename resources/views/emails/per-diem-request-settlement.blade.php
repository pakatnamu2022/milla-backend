@extends('emails.layouts.per-diem')

@section('title', 'Liquidación en revisión.')

@section('subtitle')
  @if($recipient_type === 'employee')
    Tu liquidación de viáticos fue enviada a revisión.
  @else
    {{ $employee_name }} envió su liquidación a revisión.
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

  {{-- Campos: resumen --}}
  <tr>
    <td>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- Código --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/hash.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $request_code }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Código</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Destino --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/map-pin.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $destination }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Destino</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Fechas --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/calendar.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $start_date }} — {{ $end_date }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Fechas</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Presupuesto asignado --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/banknote.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_budget, 2) }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Presupuesto asignado</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Total gastado --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/receipt.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_general_comprobante, 2) }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Total gastado</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Empresa asume --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f3f4f6;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/building-2.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_general_asume_empresa, 2) }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Empresa asume</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Colaborador asume --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/wallet.svg?color=%23111111&width=28&height=28" alt="" width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_general_asume_colaborador, 2) }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    @if($recipient_type === 'employee')Tú asumes@else Colaborador asume@endif
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>

  {{-- Tabla: gastos empresa --}}
  @if(count($gastos_empresa) > 0)
    <tr>
      <td style="padding:28px 0 8px 0;">
        <p style="margin:0 0 12px 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;line-height:1.4;">Gastos asumidos por la empresa</p>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <thead>
            <tr>
              <th style="padding:0 8px 10px 0;text-align:left;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">Fecha</th>
              <th style="padding:0 8px 10px 8px;text-align:left;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">Tipo</th>
              <th style="padding:0 0 10px 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">Monto</th>
            </tr>
          </thead>
          <tbody>
            @foreach($gastos_empresa as $gasto)
              <tr>
                <td style="padding:10px 8px 10px 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">{{ $gasto['fecha'] }}</td>
                <td style="padding:10px 8px;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">{{ $gasto['tipo'] }}</td>
                <td style="padding:10px 0 10px 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">S/ {{ number_format($gasto['asume_empresa'], 2) }}</td>
              </tr>
            @endforeach
            <tr>
              <td colspan="2" style="padding:12px 8px 0 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;">Total empresa</td>
              <td style="padding:12px 0 0 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;">S/ {{ number_format($total_empresa_asume_empresa, 2) }}</td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  @endif

  {{-- Tabla: gastos colaborador --}}
  @if(count($gastos_colaborador) > 0)
    <tr>
      <td style="padding:28px 0 8px 0;">
        <p style="margin:0 0 12px 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;line-height:1.4;">
          @if($recipient_type === 'employee')Tus gastos personales@else Gastos personales del colaborador@endif
        </p>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <thead>
            <tr>
              <th style="padding:0 8px 10px 0;text-align:left;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">Fecha</th>
              <th style="padding:0 8px 10px 8px;text-align:left;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">Tipo</th>
              <th style="padding:0 8px 10px 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">Empresa</th>
              <th style="padding:0 0 10px 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">
                @if($recipient_type === 'employee')Tú@else Colab.@endif
              </th>
            </tr>
          </thead>
          <tbody>
            @foreach($gastos_colaborador as $gasto)
              <tr>
                <td style="padding:10px 8px 10px 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">{{ $gasto['fecha'] }}</td>
                <td style="padding:10px 8px;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">{{ $gasto['tipo'] }}</td>
                <td style="padding:10px 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">S/ {{ number_format($gasto['asume_empresa'], 2) }}</td>
                <td style="padding:10px 0 10px 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#111111;border-bottom:1px solid #f8f8f8;">S/ {{ number_format($gasto['asume_colaborador'], 2) }}</td>
              </tr>
            @endforeach
            <tr>
              <td colspan="2" style="padding:12px 8px 0 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;">Total</td>
              <td style="padding:12px 8px 0 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;">S/ {{ number_format($total_colaborador_asume_empresa, 2) }}</td>
              <td style="padding:12px 0 0 8px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;">S/ {{ number_format($total_colaborador_asume_colaborador, 2) }}</td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  @endif

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
          @if($recipient_type === 'employee')Ver mi liquidación@else Revisar liquidación@endif
        </a>
      </td>
    </tr>
  @endisset
@endsection
