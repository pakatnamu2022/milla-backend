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
                      Aprobación Pendiente
                    </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Solicitud de Ajuste de Margen
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Cotización #{{ $quote_number }} &mdash; Requiere tu aprobación
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">

              <p style="margin:0 0 20px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                <strong>{{ $requester_name }}</strong> ha solicitado modificar los bonos/descuentos de una
                cotización ya pagada. Esto mueve el margen real del vehículo, por lo que requiere tu revisión
                y aprobación.
              </p>

              <div
                style="margin:0 0 16px 0;padding:14px 16px;border-left:4px solid #01237E;background:#f0f4ff;border-radius:0 10px 10px 0;">
                <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;margin-bottom:6px;">
                  Datos de la Cotización
                </div>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>N° Cotización:</strong> {{ $quote_number }}<br>
                  @if($holder_name)
                    <strong>Titular:</strong> {{ $holder_name }}<br>
                  @endif
                  @if($reason)
                    <strong>Motivo:</strong> {{ $reason }}
                  @endif
                </div>
              </div>

              <!-- Líneas de cambio -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                <thead>
                <tr>
                  <th style="padding:10px 14px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-align:left;border-bottom:2px solid #e6e8ee;">Cambio</th>
                  <th style="padding:10px 14px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-align:left;border-bottom:2px solid #e6e8ee;">Concepto</th>
                  <th style="padding:10px 14px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-align:right;border-bottom:2px solid #e6e8ee;">Antes → Después</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                  <tr>
                    <td style="padding:10px 14px;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">
                      {{ ['create' => 'Agregar', 'update' => 'Editar', 'delete' => 'Eliminar'][$item['action']] ?? $item['action'] }}
                    </td>
                    <td style="padding:10px 14px;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;">
                      {{ $item['concept'] ?? '-' }}
                    </td>
                    <td style="padding:10px 14px;font:600 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                      S/ {{ number_format($item['previous_precio_unitario'] ?? 0, 2) }} → S/ {{ number_format($item['new_precio_unitario'] ?? 0, 2) }}
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>

              <!-- Impacto en margen -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border:1px solid #e6e8ee;border-radius:12px;overflow:hidden;margin-bottom:16px;">
                <thead>
                <tr>
                  <th colspan="2" style="padding:12px 16px;background:#f9fafc;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;text-transform:uppercase;border-bottom:2px solid #e6e8ee;text-align:left;">
                    Impacto en el Margen
                  </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td style="padding:12px 16px;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;border-bottom:1px solid #e6e8ee;">Margen actual</td>
                  <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #e6e8ee;text-align:right;">
                    S/ {{ number_format($margin_amount_before, 2) }} ({{ number_format($margin_pct_before, 2) }}%)
                  </td>
                </tr>
                <tr style="background:#fbfbfe;">
                  <td style="padding:12px 16px;font:600 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#01237E;">Margen simulado</td>
                  <td style="padding:12px 16px;font:700 15px/1.6 Inter,Arial,Helvetica,sans-serif;color:#01237E;text-align:right;">
                    S/ {{ number_format($margin_amount_after, 2) }} ({{ number_format($margin_pct_after, 2) }}%)
                  </td>
                </tr>
                </tbody>
              </table>

              <div
                style="margin:0 0 20px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                <strong style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                  Acción requerida
                </strong>
                <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Revisa el detalle y aprueba o rechaza esta solicitud a la brevedad posible.
                </div>
              </div>

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
