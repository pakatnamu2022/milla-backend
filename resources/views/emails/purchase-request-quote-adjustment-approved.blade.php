@extends('emails.layouts.base')

@section('content')
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="max-width:640px;background:#ffffff;border:1px solid #e6e8ee;border-radius:16px;overflow:hidden;">

          <!-- Header -->
          <tr>
            <td style="padding:24px 24px 16px 24px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;">
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
                      style="display:inline-block;padding:6px 10px;border:1px solid #86efac;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#15803d;background:#dcfce7;">
                      Aprobado
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Ajuste de Margen Aprobado
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Cotización #{{ $quote_number }}
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">

              <p style="margin:0 0 20px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Tu solicitud de ajuste de bono/descuento para la cotización <strong>#{{ $quote_number }}</strong>
                ha sido <strong style="color:#15803d;">aprobada</strong>
                @if($resolver_name) por <strong>{{ $resolver_name }}</strong> @endif. Los cambios ya fueron
                aplicados y el margen fue actualizado.
              </p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                <thead>
                <tr>
                  <th colspan="2" style="padding:12px 16px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-transform:uppercase;border-bottom:2px solid #e6e8ee;text-align:left;">
                    Margen Actualizado
                  </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">Antes</td>
                  <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                    S/ {{ number_format($margin_amount_before, 2) }} ({{ number_format($margin_pct_before, 2) }}%)
                  </td>
                </tr>
                <tr style="background:#f0fdf4;">
                  <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#15803d;">Ahora</td>
                  <td style="padding:12px 16px;font:700 15px/1.6 Inter,Arial,Helvetica,sans-serif;color:#15803d;text-align:right;">
                    S/ {{ number_format($margin_amount_after, 2) }} ({{ number_format($margin_pct_after, 2) }}%)
                  </td>
                </tr>
                </tbody>
              </table>

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
