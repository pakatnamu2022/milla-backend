@extends('emails.layouts.main')

@section('title', 'Liquidación completada.')

@section('subtitle')
  @if($recipient_type === 'employee')
    Tu liquidación de viáticos fue procesada.
  @else
    Liquidación de {{ $employee_name }} procesada.
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

        {{-- Presupuesto Asignado --}}
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
                    Presupuesto Asignado · Monto aprobado para el viático</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Gastado del Presupuesto --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/receipt.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                    S/ {{ number_format($budget_spent, 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Gastado del Presupuesto
                    @if($total_budget > 0)
                      · {{ number_format(($budget_spent / $total_budget) * 100, 1) }}% del presupuesto utilizado
                    @endif
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Gastos Complementarios --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/layers.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                    S/ {{ number_format($gastos_complementarios, 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Gastos Complementarios · Pasajes, alojamiento y similares</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Gastos de Empresa --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/building-2.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                    S/ {{ number_format($total_asume_empresa, 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Gastos de Empresa · Pagados directamente por la empresa</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Gasto del Colaborador --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/wallet.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                    S/ {{ number_format($total_asume_colaborador, 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Gasto del Colaborador · Lo que el colaborador asumió</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Total General --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/sigma.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                    S/ {{ number_format($total_general, 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                    Total General · Presupuesto + complementarios + empresa</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Saldo a devolver (opcional) --}}
        @if(isset($balance_to_return) && $balance_to_return > 0)
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/refresh-cw.svg?color=%23111111&width=28&height=28"
                         alt="" width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p
                      style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">
                      S/ {{ number_format($balance_to_return, 2) }}</p>
                    <p
                      style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">
                      Saldo a devolver</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

      </table>
    </td>
  </tr>

  {{-- Mensaje (solo si hay saldo a devolver) --}}
  @if(isset($balance_to_return) && $balance_to_return > 0)
    <tr>
      <td style="padding:20px 0 32px 0;">
        <p
          style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.7;color:#6b7280;">
          @if($recipient_type === 'employee')
            Tienes un saldo de S/ {{ number_format($balance_to_return, 2) }} para devolver. Coordina con el área
            correspondiente.
          @else
            El colaborador tiene un saldo de S/ {{ number_format($balance_to_return, 2) }} para devolver.
          @endif
        </p>
      </td>
    </tr>
  @else
    <tr>
      <td style="padding-bottom:32px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>
  @endif

  {{-- Botón --}}
  @isset($button_url)
    <tr>
      <td align="center" style="padding-bottom:40px;">
        <a href="{{ $button_url }}"
           style="display:inline-block;padding:13px 28px;background:#01237e;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;line-height:1;text-decoration:none;border-radius:8px;">
          Ver liquidación
        </a>
      </td>
    </tr>
  @endisset
@endsection
