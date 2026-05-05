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
                      Aprobación Requerida
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Pedido a Proveedor Pendiente de Aprobación
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Pedido #{{ $order_number }} &mdash; Requiere tu aprobación
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
                Se ha generado un nuevo pedido a proveedor que requiere tu aprobación como <strong>{{ $recipient_role }}</strong>.
              </p>

              <!-- Datos del pedido -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #01237E;background:#f0f4ff;border-radius:0 10px 10px 0;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;margin-bottom:6px;">
                  Información del Pedido
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>N° Pedido:</strong> {{ $order_number }}<br>
                  <strong>Fecha:</strong> {{ $order_date }}<br>
                  <strong>Almacén:</strong> {{ $warehouse_name }}<br>
                  <strong>Sede:</strong> {{ $sede_name }}
                </div>
              </div>

              <!-- Datos del proveedor -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Proveedor
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Nombre:</strong> {{ $supplier_name }}<br>
                  @if($supplier_document !== 'N/A')
                    <strong>RUC/DNI:</strong> {{ $supplier_document }}
                  @endif
                </div>
              </div>

              <!-- Datos del creador -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Creado por
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Nombre:</strong> {{ $created_by_name }}<br>
                  @if($created_by_email !== 'N/A')
                    <strong>Email:</strong> {{ $created_by_email }}
                  @endif
                </div>
              </div>

              <!-- Resumen de montos -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Resumen de Montos
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Subtotal:</strong> {{ $currency_symbol }} {{ $net_amount }}<br>
                  <strong>IGV (18%):</strong> {{ $currency_symbol }} {{ $tax_amount }}<br>
                  <strong style="font-size:16px;color:#01237E;">Total:</strong> <strong style="font-size:16px;color:#01237E;">{{ $currency_symbol }} {{ $total_amount }}</strong>
                </div>
              </div>

              <!-- Detalles de productos -->
              @if(count($details) > 0)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                       style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                  <thead>
                    <tr>
                      <th colspan="4"
                          style="padding:12px 16px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-transform:uppercase;border-bottom:2px solid #e6e8ee;text-align:left;">
                        Productos del Pedido
                      </th>
                    </tr>
                    <tr style="background:#f9fafc;">
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:left;">
                        Código
                      </th>
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:left;">
                        Descripción
                      </th>
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:center;">
                        Cantidad
                      </th>
                      <th style="padding:10px 12px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;text-align:right;">
                        Total
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($details as $index => $detail)
                      <tr style="{{ $index % 2 === 0 ? '' : 'background:#fbfbfe;' }}">
                        <td style="padding:10px 12px;font:600 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                          {{ $detail['product_code'] }}
                        </td>
                        <td style="padding:10px 12px;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;">
                          {{ $detail['product_name'] }}
                          @if($detail['note'])
                            <br><em style="font-size:11px;color:#6b7280;">{{ $detail['note'] }}</em>
                          @endif
                        </td>
                        <td style="padding:10px 12px;font:600 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:center;">
                          {{ number_format($detail['quantity'], 2) }}
                        </td>
                        <td style="padding:10px 12px;font:600 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                          {{ $currency_symbol }} {{ number_format($detail['total'], 2) }}
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              @endif

              <!-- Acción requerida -->
              <div
                style="margin:0 0 20px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                <strong
                  style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                  Acción requerida
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Por favor, revisa este pedido a proveedor y aprueba o rechaza la solicitud a la brevedad posible.
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
                        Ver Pedido a Proveedor
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