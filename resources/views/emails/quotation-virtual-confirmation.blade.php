@extends('emails.layouts.base')

@section('content')
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center">
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
                      style="display:inline-block;padding:6px 10px;border:1px solid #e6e8ee;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#01237E;background:#eef2ff;">
                      Confirmación Pendiente
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Confirma tu Cotización
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Cotización #{{ $quotation_number }} &mdash; Requiere tu confirmación
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">

              <!-- Saludo -->
              <p style="margin:0 0 16px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hola <strong style="font-weight:600;">{{ $customer_name }}</strong>,
              </p>
              <p style="margin:0 0 20px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hemos preparado una cotización para ti. Para continuar con el proceso, necesitamos que confirmes tu aceptación de esta cotización.
              </p>

              <!-- Datos de la cotización -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #01237E;background:#f0f4ff;border-radius:0 10px 10px 0;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;margin-bottom:6px;">
                  Información de la Cotización
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>N° Cotización:</strong> {{ $quotation_number }}<br>
                  <strong>Fecha:</strong> {{ $quotation_date }}<br>
                  <strong>Fecha de Vencimiento:</strong> {{ $expiration_date }}<br>
                  <strong>Sede:</strong> {{ $sede_name }}
                </div>
              </div>

              <!-- Datos del vehículo -->
              @if($vehicle_plate !== 'N/A')
                <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                  <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                    Datos del Vehículo
                  </div>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    <strong>Vehículo:</strong> {{ $vehicle_brand }} {{ $vehicle_model }}<br>
                    <strong>Placa:</strong> {{ $vehicle_plate }}
                  </div>
                </div>
              @endif

              <!-- Monto total destacado -->
              <div style="margin:0 0 20px 0;padding:18px 20px;border:2px solid #01237E;border-radius:12px;background:#f0f4ff;text-align:center;">
                <div style="font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#4b5563;margin-bottom:4px;">
                  Monto Total
                </div>
                <div style="font:700 28px/1.3 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                  {{ $currency }} {{ number_format($total_amount, 2) }}
                </div>
              </div>

              <!-- Asesor de contacto -->
              <div style="margin:0 0 20px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Asesor de Contacto
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Nombre:</strong> {{ $advisor_name }}<br>
                  @if($advisor_phone !== 'N/A')
                    <strong>Teléfono:</strong> {{ $advisor_phone }}<br>
                  @endif
                  @if($advisor_email !== 'N/A')
                    <strong>Email:</strong> {{ $advisor_email }}
                  @endif
                </div>
              </div>

              <!-- Instrucciones de confirmación -->
              <div
                style="margin:0 0 20px 0;padding:14px 16px;border:1px dashed #01237E;border-radius:12px;background:#f0f4ff;">
                <strong
                  style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                  ¿Cómo confirmar tu cotización?
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Haz clic en el botón "Confirmar Cotización" a continuación. Serás redirigido a una página segura donde podrás revisar los detalles completos y confirmar tu aceptación.
                </div>
              </div>

              <!-- Botón de confirmación -->
              @if(isset($confirmation_link))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:0 auto 16px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $confirmation_link }}"
                         style="display:inline-block;padding:14px 32px;font:600 15px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Confirmar Cotización
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

              <!-- Información de expiración del link -->
              @if(isset($token_expires_at))
                <div style="margin:0 0 16px 0;padding:10px 14px;border-radius:8px;background:#fef3c7;border:1px solid #fcd34d;">
                  <p style="margin:0;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#92400e;text-align:center;">
                    <strong>Importante:</strong> Este link de confirmación expira el {{ $token_expires_at }}
                  </p>
                </div>
              @endif

              <!-- Nota informativa -->
              <div style="margin:0;padding:12px 14px;border-radius:8px;background:#f3f4f6;border:1px solid #e5e7eb;">
                <p style="margin:0;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#374151;">
                  Si tienes alguna pregunta sobre esta cotización o necesitas realizar alguna modificación, no dudes en contactar a tu asesor.
                </p>
              </div>

            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

  <style>
    @media (max-width: 480px) {
      h1 { font-size: 18px !important; }
      p, td, div { font-size: 13px !important; }
      .btn { padding: 12px 24px !important; }
    }
  </style>
@endsection
