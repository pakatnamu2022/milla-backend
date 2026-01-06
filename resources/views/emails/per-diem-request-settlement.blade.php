@extends('emails.layouts.base')

@section('content')
  <!-- Wrapper -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
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
                  <span
                    style="display:inline-block;padding:6px 10px;border:1px solid #f59e0b;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#78350f;background:#fef3c7;">
                    Liquidación
                  </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Liquidación de Viáticos
              </h1>
              <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Es momento de liquidar tus gastos
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                Hola <strong style="font-weight:600;color:#111827;">{{ $employee_name }}</strong>,
              </p>

              <div
                style="margin:0 0 16px 0;padding:16px;border:1px solid #eef0f5;border-radius:12px;background:#fbfbfe;">
                <p style="margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Has enviado tu liquidación de viáticos <strong>{{ $request_code }}</strong> para revisión. A
                  continuación, encontrarás el detalle de los gastos que has registrado.
                </p>
              </div>

              <div
                style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #f59e0b;background:#fffbeb;border-radius:10px;">
                <div
                  style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#78350f;margin-bottom:4px;">
                  Detalles del Viaje
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Código:</strong> {{ $request_code }}<br>
                  <strong>Destino:</strong> {{ $destination }}<br>
                  <strong>Fecha inicio:</strong> {{ $start_date }}<br>
                  <strong>Fecha fin:</strong> {{ $end_date }}<br>
                  <strong>Presupuesto asignado:</strong> S/ {{ number_format($total_budget, 2) }}
                </div>
              </div>

              <!-- Resumen de Totales -->
              <div
                style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid #10b981;background:#ecfdf5;border-radius:10px;">
                <div
                  style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#065f46;margin-bottom:8px;">
                  Resumen de Liquidación
                </div>
                <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  <strong>Total gastado:</strong> S/ {{ number_format($total_general_comprobante, 2) }}<br>
                  <strong>Total que asume la empresa:</strong> S/ {{ number_format($total_general_asume_empresa, 2) }}
                  <br>
                  <strong>Total que asumes tú:</strong> S/ {{ number_format($total_general_asume_colaborador, 2) }}
                </div>
              </div>

              @if(count($gastos_empresa) > 0)
                <!-- Gastos de la Empresa -->
                <div style="margin:0 0 16px 0;">
                  <h3 style="margin:0 0 8px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    Gastos Asumidos por la Empresa
                  </h3>
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                         style="border:1px solid #e6e8ee;border-radius:8px;overflow:hidden;">
                    <thead>
                    <tr style="background:#f9fafc;">
                      <th
                        style="padding:8px;text-align:left;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Fecha
                      </th>
                      <th
                        style="padding:8px;text-align:left;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Tipo
                      </th>
                      <th
                        style="padding:8px;text-align:right;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Monto
                      </th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($gastos_empresa as $gasto)
                      <tr>
                        <td
                          style="padding:8px;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $gasto['fecha'] }}</td>
                        <td
                          style="padding:8px;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $gasto['tipo'] }}</td>
                        <td
                          style="padding:8px;text-align:right;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">
                          S/ {{ number_format($gasto['asume_empresa'], 2) }}</td>
                      </tr>
                    @endforeach
                    <tr style="background:#f9fafc;">
                      <td colspan="2"
                          style="padding:8px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">Total
                        Empresa
                      </td>
                      <td
                        style="padding:8px;text-align:right;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                        S/ {{ number_format($total_empresa_asume_empresa, 2) }}</td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              @endif

              @if(count($gastos_colaborador) > 0)
                <!-- Gastos del Colaborador -->
                <div style="margin:0 0 16px 0;">
                  <h3 style="margin:0 0 8px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    Tus Gastos Personales
                  </h3>
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                         style="border:1px solid #e6e8ee;border-radius:8px;overflow:hidden;">
                    <thead>
                    <tr style="background:#f9fafc;">
                      <th
                        style="padding:8px;text-align:left;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Fecha
                      </th>
                      <th
                        style="padding:8px;text-align:left;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Tipo
                      </th>
                      <th
                        style="padding:8px;text-align:right;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Empresa
                      </th>
                      <th
                        style="padding:8px;text-align:right;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;border-bottom:1px solid #e6e8ee;">
                        Tú
                      </th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($gastos_colaborador as $gasto)
                      <tr>
                        <td
                          style="padding:8px;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $gasto['fecha'] }}</td>
                        <td
                          style="padding:8px;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $gasto['tipo'] }}</td>
                        <td
                          style="padding:8px;text-align:right;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">
                          S/ {{ number_format($gasto['asume_empresa'], 2) }}</td>
                        <td
                          style="padding:8px;text-align:right;font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;border-bottom:1px solid #f3f4f6;">
                          S/ {{ number_format($gasto['asume_colaborador'], 2) }}</td>
                      </tr>
                    @endforeach
                    <tr style="background:#f9fafc;">
                      <td colspan="2"
                          style="padding:8px;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">Total
                      </td>
                      <td
                        style="padding:8px;text-align:right;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                        S/ {{ number_format($total_colaborador_asume_empresa, 2) }}</td>
                      <td
                        style="padding:8px;text-align:right;font:600 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                        S/ {{ number_format($total_colaborador_asume_colaborador, 2) }}</td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              @endif

              @if(isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:20px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 20px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        Liquidar Gastos
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
      table, td {
        background-color: #0b0f1a !important;
      }

      .invert-bg {
        background-color: #0b0f1a !important;
      }

      h1, h2, h3, p, div, span, strong {
        color: #e5e7eb !important;
      }
    }

    /* Mobile responsive */
    @media (max-width: 480px) {
      table[style*="padding:24px"] > tr > td {
        padding: 15px !important;
      }

      span[style*="padding:6px 10px"] {
        font-size: 11px !important;
        padding: 4px 10px !important;
      }

      h1 {
        font-size: 20px !important;
        line-height: 1.3 !important;
      }

      p, td, div {
        font-size: 13px !important;
      }

      a[style*="padding:12px"] {
        padding: 10px 16px !important;
        font-size: 13px !important;
      }

      table[style*="margin:20px"] {
        margin: 15px auto !important;
      }
    }

    /* Table optimization for mobile */
    @media (max-width: 600px) {
      table[style*="border-collapse:collapse"] {
        display: block !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
    }
  </style>
@endsection
