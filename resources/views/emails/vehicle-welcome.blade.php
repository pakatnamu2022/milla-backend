@extends('emails.layouts.main')

@push('styles')
<style>
  @media only screen and (max-width: 600px) {
    .spec-cell { display: block !important; width: 100% !important; padding: 8px 0 !important; border-right: none !important; border-bottom: 1px solid #f0f0f0 !important; }
    .spec-cell:last-child { border-bottom: none !important; }
  }
</style>
@endpush

@section('email_subject', '¡Bienvenido a la familia! Tu ' . $model_code . ' ' . $model_year . ' te espera')

@section('title')
  <span style="color:#111111;">Bienvenido</span><br>
  <span style="color:#111111;">a la familia.</span>
@endsection

@section('subtitle')
  Estamos felices de que tu {{ $model_code }} {{ $model_year }} esté en tus manos. A partir de hoy, inicia una nueva experiencia.
@endsection

@section('content')

  {{-- Saludo personalizado --}}
  <tr>
    <td style="padding: 4px 0 28px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.75;color:#374151;">
        Estimado/a <strong style="color:#111111;font-weight:600;">{{ $client_name }}</strong>, en nombre de todo el equipo de
        <strong style="color:#111111;font-weight:600;">{{ $sede_name }}</strong> queremos felicitarte por esta gran decisión.
        Tu nuevo vehículo fue entregado el {{ $delivery_date }} y nos llena de orgullo acompañarte en este momento.
      </p>
    </td>
  </tr>

  {{-- Tarjeta del vehículo --}}
  <tr>
    <td style="padding-bottom:28px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="background:#111111;border-radius:14px;overflow:hidden;">
        <tr>
          <td style="padding:28px 28px 10px 28px;">
            <p style="margin:0 0 4px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;letter-spacing:1.5px;color:#9ca3af;text-transform:uppercase;">Tu vehículo</p>
            <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:26px;font-weight:300;color:#ffffff;line-height:1.2;letter-spacing:-0.5px;">
              {{ $model_code }}
              @if($model_year)
                <span style="color:#6b7280;">{{ $model_year }}</span>
              @endif
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:0 28px 28px 28px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px;border-top:1px solid #2d2d2d;">
              <tr>
                @if($vehicle_vin)
                <td class="spec-cell" style="padding:16px 20px 16px 0;border-right:1px solid #2d2d2d;vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;letter-spacing:1px;color:#6b7280;text-transform:uppercase;">VIN</p>
                  <p style="margin:0;font-family:'SF Mono',ui-monospace,monospace;font-size:13px;color:#e5e7eb;letter-spacing:0.5px;">{{ $vehicle_vin }}</p>
                </td>
                @endif
                @if($color_name)
                <td class="spec-cell" style="padding:16px 20px 16px 20px;border-right:1px solid #2d2d2d;vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;letter-spacing:1px;color:#6b7280;text-transform:uppercase;">Color</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#e5e7eb;">{{ $color_name }}</p>
                </td>
                @endif
                @if($advisor_name)
                <td class="spec-cell" style="padding:16px 0 16px 20px;vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:system-ui,-apple-system,sans-serif;font-size:11px;letter-spacing:1px;color:#6b7280;text-transform:uppercase;">Tu asesor</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:13px;color:#e5e7eb;">{{ $advisor_name }}</p>
                </td>
                @endif
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Carta de bienvenida adjunta --}}
  @if($has_letter)
  <tr>
    <td style="padding-bottom:16px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="background:#f9fafb;border:1px solid #f0f0f0;border-radius:10px;">
        <tr>
          <td style="padding:18px 20px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:40px;vertical-align:middle;padding-right:14px;">
                  <img src="https://api.iconify.design/lucide/file-heart.svg?color=%23111111&width=32&height=32" alt=""
                       width="32" height="32"
                       style="display:block;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:middle;">
                  <p style="margin:0 0 2px 0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;color:#111111;">Carta de Bienvenida</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.5;">Encontrarás este documento adjunto en el correo. Guárdalo, te será útil.</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  @endif

  {{-- Video de bienvenida --}}
  @if($video_url)
  <tr>
    <td style="padding-bottom:16px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="background:#f9fafb;border:1px solid #f0f0f0;border-radius:10px;">
        <tr>
          <td style="padding:18px 20px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:40px;vertical-align:middle;padding-right:14px;">
                  <img src="https://api.iconify.design/lucide/play-circle.svg?color=%23111111&width=32&height=32" alt=""
                       width="32" height="32"
                       style="display:block;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:middle;">
                  <p style="margin:0 0 2px 0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;font-weight:600;color:#111111;">Video de Bienvenida</p>
                  <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;color:#6b7280;line-height:1.5;">Hemos preparado un video especialmente para ti. También lo encontrarás adjunto en este correo.</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  @endif

  {{-- Mensaje de cierre --}}
  <tr>
    <td style="padding: 20px 0 8px 0;">
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.8;color:#6b7280;">
        Recuerda que nuestro equipo siempre está disponible para apoyarte. Si tienes alguna consulta sobre tu vehículo, no dudes en contactar a tu asesor
        @if($advisor_name) <strong style="color:#374151;">{{ $advisor_name }}</strong>@endif
        en nuestra sede <strong style="color:#374151;">{{ $sede_name }}</strong>.
      </p>
    </td>
  </tr>

  {{-- Firma --}}
  <tr>
    <td style="padding: 24px 0 0 0;">
      <p style="margin:0 0 2px 0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;color:#111111;font-weight:500;">Con cariño,</p>
      <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:14px;color:#6b7280;">El equipo de {{ $sede_name }}</p>
    </td>
  </tr>

@endsection
