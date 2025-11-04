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
            <td
              style="padding:24px 24px 16px 24px;background:linear-gradient(90deg, #3b82f6, #1e40af);border-bottom:1px solid #eef0f5;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <div
                      style="display:inline-block;padding:6px 12px;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#01237E;background:#ffffff;">
                      ✓ Vehículo Recepcionado
                    </div>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 24px/1.25 Inter,Arial,Helvetica,sans-serif;color:#ffffff;">
                Notificación de Recepción
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#e0e7ff;">
                El vehículo de tu cliente ha llegado a destino
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">

              <!-- Saludo -->
              <p style="margin:0 0 16px 0;font:400 15px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hola <strong style="font-weight:600;color:#01237E;">{{ $advisor_name }}</strong>,
              </p>

              <p style="margin:0 0 20px 0;font:400 15px/1.7 Inter,Arial,Helvetica,sans-serif;color:#374151;">
                Te informamos que el vehículo <strong>VIN: {{ $vehicle_vin }}</strong> ha sido recepcionado.
              </p>

              <!-- Información del Vehículo -->
              <div
                style="margin:0 0 20px 0;padding:18px;border:2px solid #e6e8ee;border-radius:12px;background:#f9fafb;">
                <h3
                  style="margin:0 0 12px 0;font:600 16px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;padding-bottom:8px;border-bottom:1px solid #e5e7eb;">
                  Información del Vehículo
                </h3>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="margin-top:8px;">
                  <tr>
                    <td
                      style="padding:6px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;width:35%;">
                      VIN:
                    </td>
                    <td style="padding:6px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $vehicle_vin }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Marca:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $vehicle_brand }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Modelo:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $vehicle_model }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Año:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $vehicle_year }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Color:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $vehicle_color }}
                    </td>
                  </tr>
                </table>
              </div>

              <!-- Detalles de Recepción -->
              <div
                style="margin:0 0 20px 0;padding:18px;border:2px solid #e6e8ee;border-radius:12px;background:#f9fafb;">
                <h3
                  style="margin:0 0 12px 0;font:600 16px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;padding-bottom:8px;border-bottom:1px solid #e5e7eb;">
                  Detalles de Recepción
                </h3>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="margin-top:8px;">
                  <tr>
                    <td
                      style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;width:35%;">
                      Origen:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $origin }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Destino:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $destination }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Fecha de recepción:
                    </td>
                    <td style="padding:6px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#059669;">
                      {{ $received_date }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                      Recepcionado por:
                    </td>
                    <td style="padding:6px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                      {{ $received_by }}
                    </td>
                  </tr>
                </table>
              </div>

              <!-- Accesorios Recepcionados -->
              @if(count($received_items) > 0)
                <div
                  style="margin:0 0 20px 0;padding:18px;border:2px solid #e6e8ee;border-radius:12px;background:#fef9f3;">
                  <h3
                    style="margin:0 0 12px 0;font:600 16px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;padding-bottom:8px;border-bottom:1px solid #e5e7eb;">
                    Accesorios Recepcionados
                  </h3>

                  <ul
                    style="padding:0 0 0 20px;margin:8px 0 0 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#374151;">
                    @foreach($received_items as $item)
                      <li style="margin:6px 0;padding:4px 0;">
                        <span style="font-weight:600;color:#111827;">{{ $item['name'] }}</span>
                        <span style="color:#6b7280;"> - Cantidad: </span>
                        <span style="font-weight:600;color:#059669;">{{ $item['quantity'] }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <!-- Observaciones -->
              @if($note)
                <div
                  style="margin:0 0 20px 0;padding:14px 16px;border-left:4px solid #f59e0b;background:#fffbeb;border-radius:8px;">
                  <div style="font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#92400e;margin-bottom:6px;">
                    Observaciones
                  </div>
                  <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#78350f;">
                    {{ $note }}
                  </div>
                </div>
              @endif

              <!-- Mensaje de acción -->
              <div
                style="margin:0 0 20px 0;padding:16px;border:1px dashed #d1d5db;border-radius:12px;background:#fcfdfd;">
                <p style="margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Por favor, programar fecha de entrega y lavado del vehículo.
                </p>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 24px;background:#f9fafc;border-top:1px solid #eef0f5;">
              <p style="margin:0 0 8px 0;font:400 12px/1.6 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                Este es un correo automático de notificación. No responder a este mensaje.
              </p>
              <p style="margin:0;font:400 12px/1.6 Inter,Arial,Helvetica,sans-serif;color:#9ca3af;">
                &copy; {{ date('Y') }} Automotores Pakatnamu. Todos los derechos reservados.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
@endsection
