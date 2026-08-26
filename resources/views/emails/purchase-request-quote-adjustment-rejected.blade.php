@extends('emails.layouts.base')

@section('content')
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="max-width:640px;background:#ffffff;border:1px solid #e6e8ee;border-radius:16px;overflow:hidden;">

          <!-- Header -->
          <tr>
            <td style="padding:24px 24px 16px 24px;background:#fef2f2;border-bottom:1px solid #fecaca;">
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
                      style="display:inline-block;padding:6px 10px;border:1px solid #fca5a5;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#dc2626;background:#fee2e2;">
                      Rechazado
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Ajuste de Margen Rechazado
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Cotización #{{ $quote_number }}
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">

              <p style="margin:0 0 16px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Tu solicitud de ajuste de bono/descuento para la cotización <strong>#{{ $quote_number }}</strong>
                ha sido <strong style="color:#dc2626;">rechazada</strong>
                @if($resolver_name) por <strong>{{ $resolver_name }}</strong> @endif.
                No se realizó ningún cambio; el margen se mantiene sin variación.
              </p>

              @if($rejection_reason)
                <div
                  style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #dc2626;background:#fef2f2;border-radius:0 10px 10px 0;">
                  <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#dc2626;margin-bottom:6px;">
                    Motivo del rechazo
                  </div>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    {{ $rejection_reason }}
                  </div>
                </div>
              @endif

              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:0 auto 8px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 24px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Ver Solicitud de Ajuste
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
@endsection
