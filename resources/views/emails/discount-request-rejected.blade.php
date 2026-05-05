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
                      style="display:inline-block;padding:6px 10px;border:1px solid #fca5a5;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#991b1b;background:#fee2e2;">
                      Rechazado
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Descuento Rechazado
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Cotización #{{ $quotation_number }}
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">

              <!-- Saludo -->
              <p style="margin:0 0 16px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hola <strong style="font-weight:600;">{{ $recipient_name }}</strong>,
              </p>
              <p style="margin:0 0 20px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                La solicitud de descuento para la cotización <strong>#{{ $quotation_number }}</strong>
                ha sido <strong style="color:#991b1b;">rechazada</strong> por
                <strong>{{ $rejector_name }}</strong> el {{ $rejection_date }}.
              </p>

              <!-- Datos de la cotización -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #dc2626;background:#fef2f2;border-radius:0 10px 10px 0;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#dc2626;margin-bottom:6px;">
                  Datos de la Cotización
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>N° Cotización:</strong> {{ $quotation_number }}<br>
                  @if($plate)
                    <strong>Vehículo:</strong> {{ $plate }}<br>
                  @endif
                  <strong>Solicitado por:</strong> {{ $requester_name }}<br>
                  <strong>Rechazado por:</strong> {{ $rejector_name }}<br>
                  <strong>Fecha de rechazo:</strong> {{ $rejection_date }}<br>
                  <strong>Tipo de descuento:</strong> {{ $type === 'GLOBAL' ? 'General (toda la cotización)' : 'Parcial (ítem específico)' }}<br>
                  <strong>Tipo de ítem:</strong> {{ $item_type === 'PRODUCT' ? 'Repuesto / Producto' : 'Mano de obra / Servicio' }}
                </div>
              </div>

              <!-- Detalle del ítem (solo PARTIAL) -->
              @if($type === 'PARTIAL')
                <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                  <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                    Ítem Solicitado
                  </div>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    <strong>Descripción:</strong> {{ $item_description }}<br>
                    <strong>Cantidad:</strong> {{ $item_quantity }} {{ $item_unit }}<br>
                    <strong>Precio unitario:</strong> S/ {{ number_format($item_unit_price, 2) }}
                  </div>
                </div>
              @endif

              <!-- Resumen del descuento -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:20px;">
                <thead>
                  <tr>
                    <th colspan="2"
                        style="padding:12px 16px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-transform:uppercase;border-bottom:2px solid #e6e8ee;text-align:left;">
                      Resumen del Descuento Solicitado
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                      Precio original
                    </td>
                    <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                      S/ {{ number_format($original_price, 2) }}
                    </td>
                  </tr>
                  <tr style="background:#fbfbfe;">
                    <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                      Descuento solicitado
                    </td>
                    <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#dc2626;border-bottom:1px solid #e6e8ee;text-align:right;">
                      {{ number_format($discount_percentage, 2) }}% &mdash; S/ {{ number_format($discount_amount, 2) }}
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                      Precio que hubiera sido
                    </td>
                    <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;text-align:right;text-decoration:line-through;">
                      S/ {{ number_format($final_price, 2) }}
                    </td>
                  </tr>
                  <tr style="background:#fef2f2;">
                    <td colspan="2" style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#991b1b;text-align:center;">
                      Solicitud Rechazada
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Mensaje informativo -->
              <div
                style="margin:0 0 20px 0;padding:12px 14px;border:1px solid #fed7aa;border-radius:12px;background:#fffbeb;">
                <strong
                  style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#92400e;">
                  Información Importante
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  El descuento solicitado no fue aprobado. La cotización mantendrá su precio original.
                  Si consideras que es necesario, puedes solicitar un nuevo descuento con ajustes.
                </div>
              </div>

              <!-- Botón -->
              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:0 auto 8px auto;">
                  <tr>
                    <td align="center" bgcolor="#dc2626" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 24px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#dc2626;border-radius:10px;border:1px solid #991b1b;">
                        Ver Cotización
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

  <style>
    @media (max-width: 480px) {
      h1 { font-size: 18px !important; }
      p, td, div { font-size: 13px !important; }
    }
  </style>
@endsection