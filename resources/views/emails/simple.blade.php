<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Email</title>
</head>
<body>
    <h1>Test Email</h1>
    <p>Hola {{ $name ?? 'Usuario' }},</p>
    <p>{{ $message ?? 'Este es un mensaje de prueba.' }}</p>
    <p>Saludos!</p>
</body>
</html>