{{-- resources/views/emails/layouts/per-diem.blade.php --}}
  <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('email_subject', 'Notificación — Sian')</title>
  <style>
    @media only screen and (max-width: 600px) {
      .pd-half {
        display: block !important;
        width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#ffffff;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#ffffff">
  <tr>
    <td align="center" style="padding:52px 24px 40px 24px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:540px;">

        {{-- Logo --}}
        <tr>
          <td align="left" style="padding-bottom:36px;">
            <img src="{{ $logo ?? 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg' }}" alt="Sian"
                 style="display:block;max-width:100px;height:auto;border:0;outline:none;text-decoration:none;">
          </td>
        </tr>

        {{-- Título --}}
        <tr>
          <td align="left" style="padding-bottom:10px;">
            <h1
              style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:45px;font-weight:600;line-height:1.1;color:#111111;letter-spacing:-1px;">
              @yield('title')
            </h1>
          </td>
        </tr>

        {{-- Subtítulo --}}
        <tr>
          <td align="left" style="padding-bottom:10px;margin-bottom:40px;border-bottom:1px solid #f0f0f0;">
            <p
              style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#6b7280;">
              @yield('subtitle')
            </p>
          </td>
        </tr>

        {{-- Contenido del template --}}
        @yield('content')

        {{-- Footer --}}
        <tr>
          <td style="padding-top:32px;border-top:1px solid #f0f0f0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="vertical-align:middle;padding-right:10px;">
                  <img src="https://namu-storage.nyc3.digitaloceanspaces.com/general/s_gray.svg"
                       alt="Sian" width="20" height="20"
                       style="display:block;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:middle;">
                  <p
                    style="margin:0;font-family:system-ui,-apple-system,sans-serif;font-size:12px;line-height:1.6;color:#9ca3af;">
                    Correo automático — no responder &nbsp;·&nbsp; &copy; {{ date('Y') }} Sian. Todos los derechos
                    reservados.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
