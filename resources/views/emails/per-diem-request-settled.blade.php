@extends('emails.layouts.per-diem')

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
        <tr>
          <td colspan="2" style="padding:16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $request_code }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Código</p>
          </td>
        </tr>
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_spent, 2) }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Total gastado</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_asume_empresa, 2) }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Empresa asume</p>
          </td>
        </tr>
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_asume_colaborador, 2) }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Colaborador asume</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_reembolsar, 2) }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">A reembolsar</p>
          </td>
        </tr>
        @if(isset($balance_to_return) && $balance_to_return > 0)
          <tr>
            <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_budget, 2) }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Presupuesto asignado</p>
            </td>
            <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($balance_to_return, 2) }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Saldo a devolver</p>
            </td>
          </tr>
          <tr>
            <td colspan="2" style="padding:16px 0;vertical-align:top;font-size:0;line-height:0;">&nbsp;</td>
          </tr>
        @else
          <tr>
            <td colspan="2" style="padding:16px 0;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ number_format($total_budget, 2) }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Presupuesto asignado</p>
            </td>
          </tr>
        @endif
      </table>
    </td>
  </tr>

  {{-- Mensaje (solo si hay saldo a devolver) --}}
  @if(isset($balance_to_return) && $balance_to_return > 0)
    <tr>
      <td style="padding:0 0 32px 0;">
        <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.7;color:#6b7280;">
          @if($recipient_type === 'employee')
            Tienes un saldo de S/ {{ number_format($balance_to_return, 2) }} para devolver. Coordina con el área correspondiente.
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
