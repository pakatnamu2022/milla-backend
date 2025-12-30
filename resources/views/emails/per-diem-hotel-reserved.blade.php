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
                    <span style="display:inline-block;padding:6px 10px;border:1px solid #8b5cf6;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#4c1d95;background:#ede9fe;">
                      Hotel Reservado
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Reserva de Hotel Confirmada
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Tu alojamiento ha sido reservado
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
                  Se ha confirmado la reserva de hotel para tu solicitud de viáticos <strong>{{ $request_code }}</strong>.
                </p>
              </div>

              <div style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #8b5cf6;background:#f5f3ff;border-radius:10px;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#4c1d95;margin-bottom:4px;">
                  Detalles de la Reserva
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Hotel:</strong> {{ $hotel_name }}<br>
                  @if(isset($address) && $address)
                    <strong>Dirección:</strong> {{ $address }}<br>
                  @endif
                  <strong>Check-in:</strong> {{ $checkin_date }}<br>
                  <strong>Check-out:</strong> {{ $checkout_date }}<br>
                  <strong>Noches:</strong> {{ $nights_count }}<br>
                  @if(isset($total_cost) && $total_cost)
                    <strong>Costo total:</strong> S/ {{ number_format($total_cost, 2) }}
                  @endif
                </div>
              </div>

              <div style="margin:0 0 16px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                <strong style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                  Próximos pasos
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Tu solicitud de viáticos ahora está en progreso. Asegúrate de confirmar tu reserva directamente con el hotel.
                </div>
              </div>

              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:20px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}" style="display:inline-block;padding:12px 20px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Ver Detalles
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
