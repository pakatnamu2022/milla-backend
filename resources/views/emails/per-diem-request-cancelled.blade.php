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
                    <span style="display:inline-block;padding:6px 10px;border:1px solid #ef4444;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#7f1d1d;background:#fee2e2;">
                      Cancelada
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Solicitud de Viáticos Cancelada
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Tu solicitud ha sido cancelada
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hola <strong style="font-weight:600;color:#111827;">{{ $employee_name }}</strong>,
              </p>

              <div style="margin:0 0 16px 0;padding:16px;border:1px solid #eef0f5;border-radius:12px;background:#fbfbfe;">
                <p style="margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Tu solicitud de viáticos <strong>{{ $request_code }}</strong> ha sido cancelada.
                </p>
              </div>

              <div style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #ef4444;background:#fef2f2;border-radius:10px;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#7f1d1d;margin-bottom:4px;">
                  Información de la Solicitud
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Código:</strong> {{ $request_code }}<br>
                  <strong>Destino:</strong> {{ $destination }}<br>
                  <strong>Fecha inicio:</strong> {{ $start_date }}<br>
                  <strong>Fecha fin:</strong> {{ $end_date }}<br>
                  @if(isset($cancellation_reason) && $cancellation_reason)
                    <strong>Motivo de cancelación:</strong> {{ $cancellation_reason }}
                  @endif
                </div>
              </div>

              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:20px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}" style="display:inline-block;padding:12px 20px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Ver Solicitud
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
