@extends('emails.layouts.base')

@section('content')
  <!-- Wrapper -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#f6f7fb;padding:24px 0;">
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
                    Completada
                  </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Liquidación de Viáticos Completada
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Tu liquidación ha sido procesada exitosamente
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hola <strong style="font-weight:600;color:#111827;">{{ $employee_name }}</strong>,
              </p>

              <div
                style="margin:0 0 16px 0;padding:16px;border:1px solid #eef0f5;border-radius:12px;background:#fbfbfe;">
                <p style="margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  La liquidación de tu solicitud de viáticos <strong>{{ $request_code }}</strong> ha sido completada exitosamente.
                </p>
              </div>

              <div
                style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #10b981;background:#ecfdf5;border-radius:10px;">
                <div
                  style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#065f46;margin-bottom:4px;">
                  Resumen de Liquidación
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Código:</strong> {{ $request_code }}<br>
                  <strong>Total gastado:</strong> S/ {{ number_format($total_spent, 2) }}<br>
                  <strong>Total que asume la empresa:</strong> S/ {{ number_format($total_asume_empresa, 2) }}<br>
                  <strong>Total que asume el colaborador:</strong> S/ {{ number_format($total_asume_colaborador, 2) }}<br>
                  <strong>Total a reembolsar:</strong> S/ {{ number_format($total_reembolsar, 2) }}<br>
                  <br>
                  <strong>Presupuesto asignado:</strong> S/ {{ number_format($total_budget, 2) }}<br>
                  @if($balance_to_return > 0)
                    <strong>Saldo a devolver:</strong> S/ {{ number_format($balance_to_return, 2) }}
                  @endif
                </div>
              </div>

              @if(isset($balance_to_return) && $balance_to_return > 0)
                <div
                  style="margin:0 0 16px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                  <strong
                    style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                    Acción requerida
                  </strong>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    Tienes un saldo de S/ {{ number_format($balance_to_return, 2) }} para devolver. Por favor, coordina con el área correspondiente.
                  </div>
                </div>
              @endif

              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:20px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 20px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Ver Liquidación
                      </a>
                    </td>
                  </tr>
                </table>
              @endif
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
      table, td { background-color: #0b0f1a !important; }
      .invert-bg { background-color: #0b0f1a !important; }
      h1, h2, h3, p, div, span, strong { color: #e5e7eb !important; }
    }
  </style>
@endsection
