@extends('emails.layouts.base')

@section('content')
  <!-- Wrapper -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center">
        <!-- Container -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="max-width:640px;background:#ffffff;border:1px solid #e6e8ee;border-radius:16px;overflow:hidden;">
          <!-- Header -->
          <tr>
            <td style="padding:24px 24px 16px 24px;background:#f9fafc;border-bottom:1px solid #eef0f5;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    @if(isset($logo))
                      <img src="{{ $logo }}" alt="Logo" width="120"
                           style="display:block;height:auto;border:0;outline:none;text-decoration:none;max-width:160px;">
                    @endif
                  </td>
                  <td align="right" style="vertical-align:middle;">
                  <span
                    style="display:inline-block;padding:6px 10px;border:1px solid #10b981;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#065f46;background:#d1fae5;">
                    Liquidación Aprobada
                  </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Notificación de Liquidación de Viáticos
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                {{ $action_required }}
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Estimado/a <strong style="font-weight:600;color:#111827;">{{ $recipient_name }}</strong>,
              </p>

              <p style="margin:0 0 16px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Como <strong>{{ $recipient_role }}</strong>, le notificamos que la liquidación de viáticos
                <strong>{{ $request_code }}</strong> del colaborador <strong>{{ $employee_name }}</strong> ha sido
                aprobada por el jefe directo.
              </p>

              <div
                style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #3b82f6;background:#eff6ff;border-radius:10px;">
                <div
                  style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#1e3a8a;margin-bottom:4px;">
                  Detalles del Viaje
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Código:</strong> {{ $request_code }}<br>
                  <strong>Empleado:</strong> {{ $employee_name }}<br>
                  <strong>Empresa:</strong> {{ $sede_service }}<br>
                  <strong>Sede servicio:</strong> {{ $sede_service }}<br>
                  <strong>Distrito:</strong> {{ $district }}<br>
                  <strong>Fecha inicio:</strong> {{ $start_date }}<br>
                  <strong>Fecha fin:</strong> {{ $end_date }}<br>
                  <strong>Días:</strong> {{ $days_count }}<br>
                  <strong>Motivo:</strong> {{ $purpose }}
                </div>
              </div>

              <!-- Resumen Financiero -->
              <div
                style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #10b981;background:#ecfdf5;border-radius:10px;">
                <div
                  style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#065f46;margin-bottom:8px;">
                  Resumen Financiero
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Total gastado:</strong> S/ {{ $total_spent }}<br>
                  @if(isset($total_company_amount))
                    <strong>Total asumido por la empresa:</strong> S/ {{ $total_company_amount }}<br>
                  @endif
                  @if(isset($total_employee_amount))
                    <strong>Total asumido por el colaborador:</strong> S/ {{ $total_employee_amount }}<br>
                  @endif
                </div>
              </div>

              <!-- Action button -->
              @if(isset($view_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:20px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $view_url }}"
                         style="display:inline-block;padding:12px 20px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Ver Detalles de Liquidación
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

              <!-- Footer note -->
              <div
                style="margin:16px 0 0 0;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                <p style="margin:0;font:400 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                  <strong>Nota:</strong> Esta es una notificación automática. Por favor, revise los detalles de la
                  liquidación en el sistema para proceder según corresponda.
                </p>
              </div>

              <!-- Contact -->
              <p
                style="margin:16px 0 0 0;font:400 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;text-align:center;">
                Si tiene alguna pregunta, contacte a {{ $contact_info ?? 'rrhh@grupopakatnamu.com' }}
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:16px 24px;background:#f9fafc;border-top:1px solid #eef0f5;">
              <p style="margin:0;font:400 12px/1.5 Inter,Arial,Helvetica,sans-serif;color:#9ca3af;text-align:center;">
                {{ $company_name ?? 'Grupo Pakatnamu' }} &copy; {{ date('Y') }}<br>
                Enviado el {{ $send_date }}
              </p>
            </td>
          </tr>
        </table>
        <!-- /Container -->
      </td>
    </tr>
  </table>

  <!-- Dark mode support -->
  <style>
    @media (prefers-color-scheme: dark) {
      table, td {
        background-color: #0b0f1a !important;
      }

      .invert-bg {
        background-color: #0b0f1a !important;
      }

      h1, h2, h3, p, div, span, strong {
        color: #e5e7eb !important;
      }
    }

    /* Mobile responsive */
    @media (max-width: 480px) {
      table[style*="padding:24px"] > tr > td {
        padding: 15px !important;
      }

      span[style*="padding:6px 10px"] {
        font-size: 11px !important;
        padding: 4px 10px !important;
      }

      h1 {
        font-size: 18px !important;
        line-height: 1.3 !important;
      }

      p, td, div {
        font-size: 13px !important;
      }

      a[style*="padding:12px"] {
        padding: 10px 16px !important;
        font-size: 13px !important;
      }

      table[style*="margin:20px"] {
        margin: 15px auto !important;
      }
    }
  </style>
@endsection
