{{-- resources/views/emails/layouts/evaluation.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('email_subject', 'Evaluación de Desempeño — Sian')</title>
  <style>
    @media only screen and (max-width: 600px) {
      .ev-wrap   { padding: 0 !important; }
      .ev-card   { border-radius: 0 !important; }
      .ev-pad    { padding-left: 24px !important; padding-right: 24px !important; }
      .ev-col3   { display: block !important; width: 100% !important; padding: 10px 0 !important; }
      .ev-spacer { display: none !important; }
      .ev-stats td { display: block !important; width: 100% !important;
                     text-align: left !important; padding-bottom: 12px !important; }
    }
  </style>
  @stack('ev_styles')
</head>
<body style="margin:0;padding:0;background:#f5f5f7;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#f5f5f7">
  <tr>
    <td align="center" class="ev-wrap" style="padding:48px 24px;">

      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             class="ev-card"
             style="max-width:540px;background:#ffffff;border-radius:20px;overflow:hidden;
                    box-shadow:0 4px 32px rgba(0,0,0,0.07);">

        {{-- Logo --}}
        <tr>
          <td align="center" class="ev-pad" style="padding:44px 40px 36px;">
            <img src="{{ $logo ?? 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg' }}"
                 alt="Sian"
                 style="display:block;max-width:80px;height:auto;border:0;outline:none;text-decoration:none;">
          </td>
        </tr>

        {{-- Divider --}}
        <tr>
          <td class="ev-pad" style="padding:0 40px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr><td height="1" bgcolor="#f0f0f0" style="font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
          </td>
        </tr>

        {{-- Title --}}
        <tr>
          <td class="ev-pad" style="padding:32px 40px 6px;">
            <h1 style="margin:0;
                       font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                       font-size:28px;font-weight:600;line-height:1.2;
                       color:#1d1d1f;letter-spacing:-0.4px;">
              @yield('title')
            </h1>
          </td>
        </tr>

        {{-- Subtitle --}}
        <tr>
          <td class="ev-pad" style="padding:8px 40px 28px;">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                      font-size:15px;line-height:1.6;color:#6e6e73;">
              @yield('subtitle')
            </p>
          </td>
        </tr>

        {{-- Content --}}
        <tr>
          <td class="ev-pad" style="padding:0 40px;">
            @yield('content')
          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td class="ev-pad" style="padding:32px 40px 40px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                   style="margin-bottom:20px;">
              <tr><td height="1" bgcolor="#f0f0f0" style="font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
            <p style="margin:0;
                      font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                      font-size:12px;line-height:1.6;color:#aeaeb2;text-align:center;">
              Este correo fue enviado automáticamente &nbsp;·&nbsp;
              &copy; {{ date('Y') }} {{ $company_name ?? 'Sian' }}
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
