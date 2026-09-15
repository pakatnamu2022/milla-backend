<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.6; }
    .header { text-align: center; margin-bottom: 24px; }
    .header h1 { font-size: 16px; margin: 0; }
    .content { padding: 0 16px; }
    .footer { margin-top: 40px; font-size: 10px; color: #666; text-align: center; }
  </style>
</head>
<body>
  <div class="header">
    <h1>{{ $subject }}</h1>
  </div>
  <div class="content">
    {!! $body !!}
  </div>
  <div class="footer">
    Documento generado el {{ now()->format('d/m/Y') }} — Grupo Pakatnamu
  </div>
</body>
</html>
