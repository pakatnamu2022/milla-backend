<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report_title ?? 'Reporte' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .summary-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .data-table th {
            background-color: #343a40;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $report_title ?? 'Reporte del Sistema' }}</h1>
            @if(isset($report_subtitle))
                <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $report_subtitle }}</p>
            @endif
            <p style="font-size: 14px; margin: 10px 0 0 0;">
                Generado el {{ $generated_date ?? now()->format('d/m/Y H:i:s') }}
            </p>
        </div>

        <div class="content">
            @if(isset($recipient_name))
                <p>Estimado/a <strong>{{ $recipient_name }}</strong>,</p>
            @endif

            @if(isset($introduction))
                <p>{{ $introduction }}</p>
            @endif

            @if(isset($summary_data) && is_array($summary_data))
                <div class="summary-box">
                    <h3 style="margin-top: 0; color: #28a745;">📊 Resumen Ejecutivo</h3>
                    @foreach($summary_data as $key => $value)
                        <p><strong>{{ $key }}:</strong> {{ $value }}</p>
                    @endforeach
                </div>
            @endif

            @if(isset($table_data) && is_array($table_data) && count($table_data) > 0)
                <h3>📋 Datos Detallados</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            @if(isset($table_headers) && is_array($table_headers))
                                @foreach($table_headers as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            @else
                                @foreach(array_keys($table_data[0]) as $key)
                                    <th>{{ ucfirst($key) }}</th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($table_data as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(isset($important_notes) && is_array($important_notes))
                @foreach($important_notes as $note)
                    <div class="highlight">
                        <strong>📌 Nota importante:</strong> {{ $note }}
                    </div>
                @endforeach
            @endif

            @if(isset($conclusions))
                <h3>🎯 Conclusiones</h3>
                <p>{{ $conclusions }}</p>
            @endif

            @if(isset($next_steps) && is_array($next_steps))
                <h3>📋 Próximos Pasos</h3>
                <ol>
                    @foreach($next_steps as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            @endif
        </div>

        <div class="footer">
            <p><strong>{{ $company_name ?? 'Sistema de Reportes' }}</strong></p>
            <p style="font-size: 12px; margin: 5px 0 0 0;">
                Este reporte se generó automáticamente el {{ now()->format('d/m/Y \a \l\a\s H:i:s') }}
            </p>
            @if(isset($contact_info))
                <p style="font-size: 12px; margin: 10px 0 0 0;">
                    Para consultas: {{ $contact_info }}
                </p>
            @endif
        </div>
    </div>
</body>
</html>