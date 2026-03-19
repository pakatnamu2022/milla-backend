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
                      Requiere Aprobación
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Nueva Cotización Solicitada por Gerencia
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Cotización #{{ $quotation_number }} &mdash; Requiere tu revisión
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
                Se ha generado una nueva cotización solicitada por gerencia que requiere tu revisión y aprobación.
              </p>

              <!-- Datos de la cotización -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #01237E;background:#f0f4ff;border-radius:0 10px 10px 0;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;margin-bottom:6px;">
                  Información de la Cotización
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>N° Cotización:</strong> {{ $quotation_number }}<br>
                  <strong>Fecha:</strong> {{ $quotation_date }}<br>
                  <strong>Vencimiento:</strong> {{ $expiration_date }}<br>
                  <strong>Sede:</strong> {{ $sede_name }}<br>
                  <strong>Área:</strong> {{ $area }}
                </div>
              </div>

              <!-- Datos del cliente -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Datos del Cliente
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Cliente:</strong> {{ $customer_name }}<br>
                  <strong>Documento:</strong> {{ $customer_document }}<br>
                  @if($customer_phone !== 'N/A')
                    <strong>Teléfono:</strong> {{ $customer_phone }}<br>
                  @endif
                  @if($vehicle_plate !== 'N/A')
                    <strong>Vehículo:</strong> {{ $vehicle_brand }} {{ $vehicle_model }} - Placa: {{ $vehicle_plate }}
                  @endif
                </div>
              </div>

              <!-- Asesor -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Asesor Responsable
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

              <!-- Detalles de productos/servicios -->
              @if(count($details) > 0)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                       style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                  <thead>
                    <tr>
                      <th colspan="4"
                          style="padding:12px 16px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-transform:uppercase;border-bottom:2px solid #e6e8ee;text-align:left;">
                        Detalle de Productos/Servicios
                      </th>
                    </tr>
                    <tr style="background:#f9fafc;">
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:left;">
                        Descripción
                      </th>
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:center;">
                        Cant.
                      </th>
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:right;">
                        P. Unit.
                      </th>
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:right;">
                        Total
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($details as $index => $detail)
                      <tr style="{{ $index % 2 === 0 ? '' : 'background:#fbfbfe;' }}">
                        <td style="padding:10px 12px;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;">
                          @if($detail['code'] !== 'N/A')
                            <span style="font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">{{ $detail['code'] }}</span><br>
                          @endif
                          {{ $detail['description'] }}
                          @if($detail['observations'])
                            <br><em style="font-size:11px;color:#6b7280;">{{ $detail['observations'] }}</em>
                          @endif
                        </td>
                        <td style="padding:10px 12px;font:600 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:center;">
                          {{ number_format($detail['quantity'], 2) }}
                        </td>
                        <td style="padding:10px 12px;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                          {{ $currency }} {{ number_format($detail['unit_price'], 2) }}
                        </td>
                        <td style="padding:10px 12px;font:600 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                          {{ $currency }} {{ number_format($detail['total_amount'], 2) }}
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              @endif

              <!-- Totales -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                <thead>
                  <tr>
                    <th colspan="2"
                        style="padding:12px 16px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-transform:uppercase;border-bottom:2px solid #e6e8ee;text-align:left;">
                      Resumen de Totales
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                      Subtotal
                    </td>
                    <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                      {{ $currency }} {{ number_format($subtotal, 2) }}
                    </td>
                  </tr>
                  @if($discount_amount > 0)
                    <tr style="background:#fbfbfe;">
                      <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                        Descuento ({{ number_format($discount_percentage, 2) }}%)
                      </td>
                      <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#dc2626;border-bottom:1px solid #e6e8ee;text-align:right;">
                        - {{ $currency }} {{ number_format($discount_amount, 2) }}
                      </td>
                    </tr>
                  @endif
                  <tr>
                    <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                      IGV (18%)
                    </td>
                    <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                      {{ $currency }} {{ number_format($tax_amount, 2) }}
                    </td>
                  </tr>
                  <tr style="background:#f0fdf4;">
                    <td style="padding:12px 16px;font:700 15px/1.6 Inter,Arial,Helvetica,sans-serif;color:#15803d;">
                      Total
                    </td>
                    <td style="padding:12px 16px;font:700 16px/1.6 Inter,Arial,Helvetica,sans-serif;color:#15803d;text-align:right;">
                      {{ $currency }} {{ number_format($total_amount, 2) }}
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Observaciones -->
              @if($observations)
                <div style="margin:0 0 20px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                  <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                    Observaciones
                  </div>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    {{ $observations }}
                  </div>
                </div>
              @endif

              <!-- Acción requerida -->
              <div
                style="margin:0 0 20px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                <strong
                  style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                  Acción requerida
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Por favor, revisa esta cotización y procede con su aprobación a la brevedad posible.
                </div>
              </div>

              <!-- Botón -->
              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:0 auto 8px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 24px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
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