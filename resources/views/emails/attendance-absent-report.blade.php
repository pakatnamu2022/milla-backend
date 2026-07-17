@extends('emails.layouts.main')

@section('title', 'Reporte de Ausencias.')

@section('subtitle')
  {{ $absent_count }} colaborador{{ $absent_count !== 1 ? 'es' : '' }} sin marcación al corte de las 9:30 a.m.
@endsection

@section('content')
  {{-- Saludo --}}
  <tr>
    <td style="padding:0 0 20px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.7;color:#111111;">
        El siguiente reporte corresponde al día <strong style="font-weight:600;">{{ $report_date }}</strong>.
        Se adjunta el archivo Excel con el detalle completo.
      </p>
    </td>
  </tr>

  {{-- Resumen --}}
  <tr>
    <td style="padding:0 0 24px 0;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/calendar-x.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $report_date }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Fecha del reporte</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/users.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $absent_count }}</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Total sin marcación</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/clock.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:16px;font-weight:600;color:#111111;line-height:1.2;">09:30 a.m.</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.4;">Hora de corte</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Tabla de ausentes (primeros 20) --}}
  @if(count($workers) > 0)
    <tr>
      <td style="padding:0 0 8px 0;">
        <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:0.05em;">
          Detalle{{ count($workers) > 20 ? ' (primeros 20 — ver adjunto para lista completa)' : '' }}
        </p>
      </td>
    </tr>
    <tr>
      <td style="padding:0 0 32px 0;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="border-collapse:collapse;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">
          <thead>
            <tr style="background:#111111;">
              <th style="padding:10px 14px;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:600;color:#ffffff;text-align:left;">DNI</th>
              <th style="padding:10px 14px;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:600;color:#ffffff;text-align:left;">Nombre Completo</th>
              <th style="padding:10px 14px;font-family:system-ui,-apple-system,sans-serif;font-size:12px;font-weight:600;color:#ffffff;text-align:left;">Estado</th>
            </tr>
          </thead>
          <tbody>
            @foreach(array_slice($workers, 0, 20) as $i => $worker)
              <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f9fafb' }};">
                <td style="padding:10px 14px;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">{{ $worker['dni'] }}</td>
                <td style="padding:10px 14px;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">{{ $worker['full_name'] }}</td>
                <td style="padding:10px 14px;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#b91c1c;font-weight:600;border-bottom:1px solid #e5e7eb;">No marcó</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </td>
    </tr>
  @endif

  {{-- Nota --}}
  <tr>
    <td style="padding:0 0 32px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;line-height:1.7;color:#6b7280;">
        El archivo Excel adjunto contiene la lista completa de ausentes con DNI y nombre. Este reporte se genera automáticamente de lunes a sábado a las 9:30 a.m.
      </p>
    </td>
  </tr>
@endsection
