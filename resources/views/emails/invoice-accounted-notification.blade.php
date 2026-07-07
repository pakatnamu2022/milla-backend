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
                      style="display:inline-block;padding:6px 10px;border:1px solid #e6e8ee;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#059669;background:#d1fae5;">
                      Contabilizado
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Comprobante Contabilizado
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                OC {{ $purchase_order_number }} &mdash; Movimiento de inventario generado
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
                El comprobante de la orden de compra <strong>{{ $purchase_order_number }}</strong> ha sido contabilizado exitosamente y se ha generado el movimiento de inventario correspondiente.
              </p>

              <!-- Datos del comprobante -->
              <div style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #059669;background:#d1fae5;border-radius:0 10px 10px 0;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#059669;margin-bottom:6px;">
                  Información del Comprobante
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>N° Orden de Compra:</strong> {{ $purchase_order_number }}<br>
                  <strong>N° Factura Dynamics:</strong> {{ $invoice_dynamics }}<br>
                  <strong>N° Recibo Dynamics:</strong> {{ $receipt_dynamics }}<br>
                  <strong>Fecha de Factura:</strong> {{ $invoice_date }}<br>
                  <strong>Fecha de Emisión:</strong> {{ $emission_date }}<br>
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
                  <strong>RUC:</strong> {{ $supplier_ruc }}
                </div>
              </div>

              <!-- Datos del vehículo (si existe) -->
              @if($vehicle_plate !== 'N/A' || $vehicle_vin !== 'N/A')
                <div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                  <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                    Vehículo
                  </div>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    @if($vehicle_plate !== 'N/A')
                      <strong>Placa:</strong> {{ $vehicle_plate }}<br>
                    @endif
                    @if($vehicle_vin !== 'N/A')
                      <strong>VIN:</strong> {{ $vehicle_vin }}
                    @endif
                  </div>
                </div>
              @endif

              <!-- Total -->
              <div style="margin:0 0 20px 0;padding:14px 16px;border:1px solid #e6e8ee;border-radius:12px;background:#fbfbfe;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                  Total
                </div>
                <div style="font:700 18px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  {{ $currency_symbol }} {{ $total }}
                </div>
              </div>

              <!-- Información -->
              <div
                style="margin:0 0 20px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                <strong
                  style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#059669;">
                  Información
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  El comprobante ha sido contabilizado y el movimiento de inventario se ha generado correctamente en el sistema.
                </div>
              </div>

              <!-- Botón -->
              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:0 auto 8px auto;">
                  <tr>
                    <td align="center" bgcolor="#059669" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 24px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#059669;border-radius:10px;border:1px solid #047857;">
                        Ver Orden de Compra
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