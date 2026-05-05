@extends('emails.layouts.per-diem')

@section('title', 'Liquidación aprobada.')

@section('subtitle'){{ $action_required }}@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        Estimado/a <strong style="font-weight:600;">{{ $recipient_name }}</strong>,
      </p>
    </td>
  </tr>

  {{-- Contexto --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.7;color:#6b7280;">
        Como <strong style="font-weight:600;color:#111111;">{{ $recipient_role }}</strong>, le notificamos que la liquidación
        <strong style="font-weight:600;color:#111111;">{{ $request_code }}</strong> del colaborador
        <strong style="font-weight:600;color:#111111;">{{ $employee_name }}</strong> ha sido aprobada por el jefe directo.
      </p>
    </td>
  </tr>

  {{-- Campos: datos del viaje --}}
  <tr>
    <td>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $request_code }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Código</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;width:50%;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $employee_name }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Colaborador</p>
          </td>
        </tr>
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $sede_service }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Sede de servicio</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $district }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Distrito</p>
          </td>
        </tr>
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $start_date }} — {{ $end_date }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Fechas</p>
          </td>
          <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $days_count }} días</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Duración</p>
          </td>
        </tr>
        {{-- Resumen financiero --}}
        <tr>
          <td class="pd-half" style="padding:16px 20px 16px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ $total_spent }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Total gastado</p>
          </td>
          @if(isset($total_company_amount) || isset($total_employee_amount))
            <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;vertical-align:top;">
              @isset($total_company_amount)
                <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ $total_company_amount }}</p>
                <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Empresa asume</p>
              @endisset
            </td>
          @else
            <td class="pd-half" style="padding:16px 0 16px 20px;border-bottom:1px solid #f3f4f6;"></td>
          @endif
        </tr>
        @isset($total_employee_amount)
          <tr>
            <td colspan="2" style="padding:16px 0;vertical-align:top;">
              <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/ {{ $total_employee_amount }}</p>
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Colaborador asume</p>
            </td>
          </tr>
        @endisset
        <tr>
          <td colspan="2" style="padding:16px 0;vertical-align:top;">
            <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.3;">{{ $purpose }}</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Motivo</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Spacer --}}
  <tr>
    <td style="padding-bottom:32px;font-size:0;line-height:0;">&nbsp;</td>
  </tr>

  {{-- Botón --}}
  @isset($view_url)
    <tr>
      <td align="center" style="padding-bottom:40px;">
        <a href="{{ $view_url }}"
           style="display:inline-block;padding:13px 28px;background:#01237e;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;line-height:1;text-decoration:none;border-radius:8px;">
          Ver detalles de liquidación
        </a>
      </td>
    </tr>
  @endisset
@endsection
