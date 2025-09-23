<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Email</title>
</head>
<body>
    <h1>Debug Email</h1>
    <p>Variables recibidas:</p>
    <pre>{{ json_encode(get_defined_vars(), JSON_PRETTY_PRINT) }}</pre>
</body>
</html>