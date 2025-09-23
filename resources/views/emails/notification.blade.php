<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notificación' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .alert-info {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            color: #0d47a1;
        }
        .alert-success {
            background-color: #e8f5e8;
            border-left: 4px solid #4caf50;
            color: #2e7d32;
        }
        .alert-warning {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            color: #ef6c00;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $title ?? 'Notificación del Sistema' }}</h1>
            @if(isset($subtitle))
                <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="content">
            @if(isset($user_name))
                <p>Hola <strong>{{ $user_name }}</strong>,</p>
            @endif

            @if(isset($main_message))
                <p>{{ $main_message }}</p>
            @endif

            @if(isset($alert_type) && isset($alert_message))
                <div class="alert alert-{{ $alert_type }}">
                    {!! $alert_message !!}
                </div>
            @endif

            @if(isset($details) && is_array($details))
                <h3>Detalles:</h3>
                <ul>
                    @foreach($details as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            @endif

            @if(isset($action_needed))
                <div style="background-color: #fff8e1; padding: 15px; border-radius: 4px; margin: 20px 0;">
                    <strong>Acción requerida:</strong> {!! $action_needed !!}
                </div>
            @endif
        </div>

        <div class="footer">
            <p>Fecha: {{ $date ?? now()->format('d/m/Y H:i') }}</p>
            <p>Este es un correo automático, no responder a este mensaje.</p>
        </div>
    </div>
</body>
</html>