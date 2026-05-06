{{-- resources/views/emails/layouts/evaluation.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <meta name="x-apple-disable-message-reformatting"/>
  <title>@yield('email_subject', 'Evaluación de Desempeño — Sian')</title>
  <style>
    body,table,td,a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
    table           { border-collapse:collapse !important; }
    body            { margin:0; padding:0; background:#ffffff; }
    img             { border:0; height:auto; line-height:100%; outline:none; text-decoration:none; }

    @media only screen and (max-width:600px) {
      .ev-wrap    { padding:0 !important; }
      .ev-card    { border-radius:0 !important; border-left:0 !important; border-right:0 !important; }
      .ev-pad     { padding-left:24px !important; padding-right:24px !important; }
      .ev-col3    { display:block !important; width:100% !important; padding:10px 0 !important; }
      .ev-spacer  { display:none !important; }
      .ev-stats td {
        display:block !important; width:100% !important;
        text-align:left !important; padding-right:0 !important;
        padding-bottom:14px !important;
      }
    }
  </style>
  @stack('ev_styles')
</head>
<body bgcolor="#ffffff" style="margin:0;padding:0;background:#ffffff;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#ffffff">
  <tr>
    <td align="center" class="ev-wrap" style="padding:36px 20px 52px;">

      {{-- Card --}}
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             class="ev-card"
             style="max-width:540px;background:#ffffff;border-radius:20px;
                    border:1px solid #e8e8e8;
                    box-shadow:0 4px 32px rgba(0,0,0,0.06);overflow:hidden;">

        {{-- Logo --}}
        <tr>
          <td align="center" class="ev-pad" style="padding:44px 40px 28px;">
            <img src="{{ $logo ?? 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg' }}"
                 alt="{{ $company_name ?? 'Sian' }}"
                 style="display:block;max-width:80px;height:auto;border:0;">
          </td>
        </tr>

        {{-- Title --}}
        <tr>
          <td class="ev-pad" style="padding:0 40px 8px;">
            <h1 style="margin:0;
                       font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                       font-size:30px;font-weight:700;line-height:1.15;
                       color:#1d1d1f;letter-spacing:-0.6px;">
              @yield('title')
            </h1>
          </td>
        </tr>

        {{-- Subtitle --}}
        <tr>
          <td class="ev-pad" style="padding:0 40px 28px;">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                      font-size:15px;line-height:1.6;color:#86868b;">
              @yield('subtitle')
            </p>
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

        {{-- Spacer --}}
        <tr>
          <td style="height:28px;font-size:0;line-height:0;">&nbsp;</td>
        </tr>

        {{-- Content --}}
        <tr>
          <td class="ev-pad" style="padding:0 40px;">
            @yield('content')
          </td>
        </tr>

        {{-- Spacer --}}
        <tr>
          <td style="height:16px;font-size:0;line-height:0;">&nbsp;</td>
        </tr>

        {{-- Footer divider --}}
        <tr>
          <td class="ev-pad" style="padding:0 40px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr><td height="1" bgcolor="#f0f0f0" style="font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
          </td>
        </tr>

        {{-- Footer text --}}
        <tr>
          <td align="center" class="ev-pad" style="padding:20px 40px 36px;">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                      font-size:12px;line-height:1.6;color:#c7c7cc;text-align:center;">
              Este correo fue enviado automáticamente &nbsp;·&nbsp;
              &copy; {{ date('Y') }} {{ $company_name ?? 'Sian' }}
            </p>
          </td>
        </tr>

      </table>{{-- /ev-card --}}

    </td>
  </tr>
</table>

</body>
</html>
