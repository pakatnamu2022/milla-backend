{{-- resources/views/emails/layouts/base.blade.php --}}
  <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width"/>
  <title>{{ $title ?? 'Correo electrónico' }}</title>
  <style>
    /* Paleta primary */
    /* 50:#e6edff 100:#b3c8fe 200:#81a3fe 300:#4e7efe 400:#1b59fd 500:#023fe4 600:#0131b1 700:#01237e */
    body {
      margin: 0;
      padding: 0;
      background: #f2f4f8;
    }

    img {
      border: 0;
      outline: none;
      text-decoration: none;
      display: block;
      height: auto;
      max-width: 100%;
    }

    /* Wrapper y contenedor centrado (ancho fijo) */
    .wrapper {
      width: 100%;
      background: #f2f4f8;
      padding: 24px 12px;
    }

    .outer {
      width: 100%;
      border-spacing: 0;
      border-collapse: collapse;
    }

    .container {
      width: 100%;
      max-width: 680px;
      background: #ffffff;
      border: 1px solid #e6e8ee;
      border-radius: 16px;
      overflow: hidden;
    }

    /* Header con logo y título */
    .header {
      background: #ffffff;
      border-bottom: 1px solid #e6e8ee;
      padding: 20px 24px 16px 24px;
      text-align: left;
    }

    .brand-row {
      width: 100%;
    }

    .brand-left {
      vertical-align: middle;
      text-align: left;
    }

    .brand-right {
      vertical-align: middle;
      text-align: right;
    }

    .logo {
      max-width: 164px;
      max-height: 48px
    }

    .badge {
      display: inline-block;
      padding: 6px 10px;
      border: 1px solid #b3c8fe;
      border-radius: 999px;
      font: 600 12px/1 Inter, Arial, Helvetica, sans-serif;
      color: #01237e;
      background: #e6edff;
    }

    .title {
      margin: 12px 0 4px 0;
      font: 700 20px/1.25 Inter, Arial, Helvetica, sans-serif;
      color: #111827;
    }

    .subtitle {
      margin: 0;
      font: 400 14px/1.6 Inter, Arial, Helvetica, sans-serif;
      color: #4b5563;
    }

    /* Contenido tipo cards */
    .content {
      padding: 24px;
    }

    .card {
      margin: 0 0 16px 0;
      padding: 16px;
      border: 1px solid #e6e8ee;
      border-radius: 12px;
      background: #ffffff;
    }

    .card-muted {
      background: #fbfbfe;
    }

    .muted {
      color: #4b5563
    }

    /* Llamados (primary/20) */
    .callout {
      padding: 14px;
      border-radius: 12px;
      margin: 16px 0;
      background: #e6edff;
      border-left: 4px solid #01237e;
      color: #0f172a;
    }

    .callout-title {
      font: 600 13px/1.5 Inter, Arial, Helvetica, sans-serif;
      color: #01237e;
      margin: 0 0 6px 0;
    }

    /* Botones */
    .btn {
      display: inline-block;
      padding: 12px 20px;
      border-radius: 10px;
      text-decoration: none;
      font: 600 14px/1 Inter, Arial, Helvetica, sans-serif;
    }

    .btn-primary {
      background: #01237e;
      color: #ffffff;
      border: 1px solid #0131b1;
    }

    .btn-primary:hover {
      background: #0131b1;
    }

    .btn-secondary {
      background: #F60404;
      color: #ffffff;
      border: 1px solid #c50303;
    }

    .btn-secondary:hover {
      background: #c50303;
    }

    /* Tabla corporativa */
    .table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #01237e;
      border-radius: 12px;
      overflow: hidden;
    }

    .table th {
      background: #01237e;
      color: #ffffff;
      font: 600 12px/1.4 Inter, Arial, Helvetica, sans-serif;
      text-transform: uppercase;
      padding: 10px 12px;
      text-align: left;
    }

    .table td {
      border: 1px solid #01237e;
      padding: 10px 12px;
      font: 400 14px/1.6 Inter, Arial, Helvetica, sans-serif;
      color: #111827;
    }

    .table tr:nth-child(even) {
      background: #f3f7ff;
    }

    /* tono muy suave */
    /* Barra de progreso */
    .progress {
      height: 10px;
      border-radius: 999px;
      background: #b3c8fe;
      overflow: hidden;
    }

    .progress > b {
      display: block;
      height: 10px;
      background: #01237e;
    }

    /* Footer */
    .footer {
      background: #f9fafc;
      border-top: 1px solid #e6e8ee;
      padding: 20px 24px;
      text-align: left;
    }

    .footer p {
      margin: 0 0 6px 0;
      font: 400 12px/1.6 Inter, Arial, Helvetica, sans-serif;
      color: #6b7280;
    }

    .company {
      margin-top: 8px;
      padding-top: 8px;
      border-top: 1px solid #e6e8ee;
    }

    @media (max-width: 640px) {
      .header, .content, .footer {
        padding: 20px
      }

      .badge {
        margin-top: 10px
      }
    }
  </style>
</head>
<body>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" class="wrapper">
  <tr>
    <td align="center">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="outer">
        <tr>
          <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="container">
              <tr>
                <td class="header">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="brand-row">
                    <tr>
                      <td class="brand-left">
                        @isset($logo)
                          <img src="{{ $logo }}" alt="Logo" class="logo"/>
                        @endisset
                      </td>
                      <td class="brand-right">
                        @isset($badge)
                          <span class="badge">{{ $badge }}</span>
                        @endisset
                      </td>
                    </tr>
                  </table>
                  @isset($title)
                    <div class="title">{{ $title }}</div>
                  @endisset
                  @isset($subtitle)
                    <div class="subtitle">{{ $subtitle }}</div>
                  @endisset
                </td>
              </tr>

              <tr>
                <td class="content">
                  @yield('content')
                </td>
              </tr>

              <tr>
                <td class="footer">
                  <p>{{ $footer_date_label ?? 'Fecha' }}: {{ $date ?? now()->format('d/m/Y H:i') }}</p>
                  <p>{{ $footer_note ?? 'Este es un correo automático, no responder a este mensaje.' }}</p>
                  @isset($company_name)
                    <div class="company">
                      <p>&copy; {{ date('Y') }} {{ $company_name }}. Todos los derechos reservados.</p>
                    </div>
                  @endisset
                  @isset($contact_info)
                    <p>Contacto: {{ $contact_info }}</p>
                  @endisset
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
