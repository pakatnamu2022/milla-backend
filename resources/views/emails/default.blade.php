<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Correo electrónico' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border: 1px solid #e9ecef;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-radius: 0 0 8px 8px;
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? 'Notificación' }}</h1>
    </div>

    <div class="content">
        @if(isset($greeting))
            <p><strong>{{ $greeting }}</strong></p>
        @endif

        @if(isset($message))
            <p>{!! $message !!}</p>
        @endif

        @if(isset($button_text) && isset($button_url))
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $button_url }}" class="btn">{{ $button_text }}</a>
            </div>
        @endif

        @if(isset($additional_info))
            <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                {!! $additional_info !!}
            </div>
        @endif
    </div>

    <div class="footer">
        <p>{{ $footer_text ?? 'Este es un correo electrónico automático, por favor no responder.' }}</p>
        @if(isset($company_name))
            <p>&copy; {{ date('Y') }} {{ $company_name }}. Todos los derechos reservados.</p>
        @endif
    </div>
</body>
</html>