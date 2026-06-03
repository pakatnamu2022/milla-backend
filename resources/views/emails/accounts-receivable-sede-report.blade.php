@extends('emails.layouts.per-diem')

@section('title', 'Cuentas por Cobrar.')

@section('subtitle')
  Reporte de saldos pendientes &mdash; {{ $sede_name }} &mdash; {{ $report_date }}
@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p
        style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        Hola <strong style="font-weight:600;">{{ $worker_name }}</strong>,
      </p>
    </td>
  </tr>

  {{-- KPIs --}}
  <tr>
    <td>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- Total documentos --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f8f8f8;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img
                    src="https://api.iconify.design/lucide/file-text.svg?color=%23111111&width=28&height=28" alt=""
                    width="28" height="28"
                    style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ number_format($summary['total_documents']) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Documentos pendientes</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Saldo total --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f8f8f8;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img
                    src="https://api.iconify.design/lucide/banknote.svg?color=%23111111&width=28&height=28" alt=""
                    width="28" height="28"
                    style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">S/&nbsp;{{ number_format($summary['total_balance_pen'], 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Saldo total pendiente</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Saldo vencido --}}
        <tr>
          <td style="padding:14px 0;border-bottom:1px solid #f8f8f8;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img
                    src="https://api.iconify.design/lucide/alert-circle.svg?color=%23dc2626&width=28&height=28" alt=""
                    width="28" height="28"
                    style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#dc2626;line-height:1.2;">S/&nbsp;{{ number_format($summary['overdue_balance_pen'], 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Saldo vencido</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Saldo por vencer --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img
                    src="https://api.iconify.design/lucide/clock.svg?color=%2316a34a&width=28&height=28" alt=""
                    width="28" height="28"
                    style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p
                    style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#16a34a;line-height:1.2;">S/&nbsp;{{ number_format($summary['current_balance_pen'], 2) }}</p>
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Saldo por vencer</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>

  {{-- Tabla de documentos --}}
  <tr>
    <td style="padding:28px 0 8px 0;">
      <p
        style="margin:0 0 12px 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;line-height:1.4;">
        Documentos pendientes
        @if(count($records) > 50)
          <span
            style="font-weight:400;color:#6b7280;font-size:12px;"> (primeros 50 de {{ count($records) }} &mdash; ver PDF adjunto)</span>
        @endif
      </p>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <thead>
        <tr>
          <th
            style="padding:0 6px 10px 0;text-align:left;font-family:system-ui,-apple-system,sans-serif;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">
            Cliente
          </th>
          <th
            style="padding:0 6px 10px 6px;text-align:left;font-family:system-ui,-apple-system,sans-serif;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">
            N&deg; Doc
          </th>
          <th
            style="padding:0 6px 10px 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">
            Vencim.
          </th>
          <th
            style="padding:0 6px 10px 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">
            D&iacute;as
          </th>
          <th
            style="padding:0 0 10px 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;border-bottom:1px solid #f0f0f0;">
            Saldo S/
          </th>
        </tr>
        </thead>
        <tbody>
        @foreach(array_slice($records, 0, 50) as $record)
          @php $isOverdue = ($record['overdue_days'] ?? 0) > 0; @endphp
          <tr>
            <td
              style="padding:8px 6px 8px 0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#111111;border-bottom:1px solid #f8f8f8;max-width:160px;word-break:break-word;">{{ $record['client_name'] }}</td>
            <td
              style="padding:8px 6px;font-family:system-ui,-apple-system,sans-serif;font-size:11px;color:#6b7280;border-bottom:1px solid #f8f8f8;white-space:nowrap;">{{ $record['document_number'] }}</td>
            <td
              style="padding:8px 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:11px;color:#6b7280;border-bottom:1px solid #f8f8f8;white-space:nowrap;">{{ $record['document_due_date'] ?? '&mdash;' }}</td>
            <td
              style="padding:8px 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:600;color:{{ $isOverdue ? '#dc2626' : '#16a34a' }};border-bottom:1px solid #f8f8f8;">{{ $record['overdue_days'] ?? 0 }}</td>
            <td
              style="padding:8px 0 8px 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:600;color:#111111;border-bottom:1px solid #f8f8f8;white-space:nowrap;">{{ number_format($record['balance_pen'], 2) }}</td>
          </tr>
        @endforeach
        <tr>
          <td colspan="4"
              style="padding:12px 6px 0 0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;">
            Total
          </td>
          <td
            style="padding:12px 0 0 6px;text-align:right;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#111111;white-space:nowrap;">
            S/&nbsp;{{ number_format($summary['total_balance_pen'], 2) }}
          </td>
        </tr>
        </tbody>
      </table>
    </td>
  </tr>

  {{-- Nota PDF --}}
  <tr>
    <td style="padding:20px 0 0 0;">
      <p
        style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.6;">
        El reporte completo se encuentra en el PDF adjunto a este correo.
      </p>
    </td>
  </tr>

  {{-- Spacer --}}
  <tr>
    <td style="padding-bottom:32px;font-size:0;line-height:0;">&nbsp;</td>
  </tr>
@endsection
