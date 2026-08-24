@extends('emails.layouts.main')

@push('styles')
<style>
  @media only screen and (max-width: 600px) {
    .spec-cell { display: block !important; width: 100% !important; padding: 8px 0 !important; border-right: none !important; border-bottom: 1px solid #f0f0f0 !important; }
    .spec-cell:last-child { border-bottom: none !important; }
  }
</style>
@endpush

@section('email_subject', 'Informe de llegada de repuestos en Almacén PAKATNAMU ' . $sede_abbreviation)

@section('title')
  <span style="color:#111111;">Entrada de repuestos</span><br>
  <span style="color:#111111;">realizada.</span>
@endsection

@section('subtitle')
  Se ha recepcionado la orden de compra {{ $purchase_order_number }} en el almacén de {{ $sede_name }}.
@endsection

@section('content')

  {{-- Saludo personalizado --}}
  <tr>
    <td style="padding: 4px 0 28px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.75;color:#374151;">
        Estimado/a <strong style="color:#111111;font-weight:600;">{{ $recipient_name }}</strong>,
        le informamos que se ha completado la recepción de repuestos correspondiente a la orden de compra
        <strong style="color:#111111;font-weight:600;">{{ $purchase_order_number }}</strong>.
      </p>
    </td>
  </tr>

  {{-- Tarjeta de información --}}
  <tr>
    <td style="padding-bottom:28px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="background:#111111;border-radius:14px;overflow:hidden;">
        <tr>
          <td style="padding:28px 28px 10px 28px;">
            <p style="margin:0 0 2px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;letter-spacing:1.5px;color:#9ca3af;text-transform:uppercase;">Datos de la recepción</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:26px;font-weight:300;color:#ffffff;line-height:1.2;letter-spacing:-0.5px;">
              {{ $purchase_order_number }}
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:0 28px 28px 28px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px;border-top:1px solid #2d2d2d;">
              <tr>
                <td class="spec-cell" style="padding:16px 20px 16px 0;border-right:1px solid #2d2d2d;vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;letter-spacing:1px;color:#6b7280;text-transform:uppercase;">Proveedor</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#e5e7eb;">{{ $supplier_name }}</p>
                </td>
                <td class="spec-cell" style="padding:16px 20px 16px 20px;border-right:1px solid #2d2d2d;vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;letter-spacing:1px;color:#6b7280;text-transform:uppercase;">Responsable</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#e5e7eb;">{{ $responsible_name }}</p>
                </td>
                <td class="spec-cell" style="padding:16px 0 16px 20px;vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;letter-spacing:1px;color:#6b7280;text-transform:uppercase;">Sede</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#e5e7eb;">{{ $sede_abbreviation }}</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Documento adjunto --}}
  <tr>
    <td style="padding-bottom:12px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
        <tr>
          <td style="padding:18px 20px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:40px;vertical-align:middle;padding-right:14px;">
                  <img src="https://api.iconify.design/lucide/file-text.svg?color=%23111111&width=32&height=32" alt=""
                       width="32" height="32"
                       style="display:block;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:middle;">
                  <p style="margin:0 0 2px 0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;color:#111111;">Detalle de Recepción</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.5;">Encontrarás el detalle completo de los repuestos recibidos en el documento adjunto.</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Mensaje de cierre --}}
  <tr>
    <td style="padding: 20px 0 8px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.8;color:#6b7280;">
        Este correo es de carácter informativo. Si tienes alguna consulta sobre esta recepción,
        puedes contactar con el equipo de almacén de <strong style="color:#374151;">{{ $sede_name }}</strong>.
      </p>
    </td>
  </tr>

  {{-- Firma --}}
  <tr>
    <td style="padding: 24px 0 0 0;">
      <p style="margin:0 0 2px 0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;color:#111111;font-weight:500;">Atentamente,</p>
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;color:#6b7280;">Almacén Automotores Pakatnamu</p>
    </td>
  </tr>

@endsection