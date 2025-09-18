<!DOCTYPE html >
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      margin: 20px;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
      border-bottom: 2px solid #333;
      padding-bottom: 10px;
    }

    .summary {
      background-color: #f8f9fa;
      padding: 15px;
      margin-bottom: 20px;
      border-left: 4px solid #007bff;
    }

    .summary-item {
      display: inline-block;
      margin-right: 20px;
      margin-bottom: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    th {
      background-color: #343a40;
      color: white;
      padding: 12px 8px;
      text-align: left;
      font-weight: bold;
      font-size: 11px;
    }

    td {
      padding: 8px;
      border-bottom: 1px solid #dee2e6;
      font-size: 10px;
    }

    tr:nth-child(even) {
      background-color: #f8f9fa;
    }

    .numeric {
      text-align: right;
    }

    .center {
      text-align: center;
    }

    .footer {
      margin-top: 30px;
      padding-top: 10px;
      border-top: 1px solid #dee2e6;
      text-align: center;
      color: #666;
      font-size: 10px;
    }
  </style>
</head>
<body>
<div class="header">
  <h1>{{ $title }}</h1>
  <p>Generado el: {{ now()->format('d/m/Y H:i:s') }}</p>
</div>

@if($summary)
  <div class="summary">
    <h3>Resumen</h3>
    @foreach($summary as $key => $value)
      <div class="summary-item">
        <strong>{{ $key }}:</strong> {{ $value }}
      </div>
    @endforeach
  </div>
@endif

<table>
  <thead>
  <tr>
    @foreach($columns as $key => $column)
      <th>{{ is_array($column) ? $column['label'] : $column }}</th>
    @endforeach
  </tr>
  </thead>
  <tbody>
  @foreach($data as $row)
    <tr>
      @foreach($columns as $key => $column)
        <td class="{{ is_numeric(data_get($row, $key)) ? 'numeric' : '' }}">
          @php
            $value = is_array($row) ? ($row[$key] ?? '') : data_get($row, $key, '');

            if (is_array($column) && isset($column['formatter'])) {
                switch($column['formatter']) {
                    case 'currency':
                        $value = is_numeric($value) ? '$' . number_format($value, 2) : $value;
                        break;
                    case 'percentage':
                        $value = is_numeric($value) ? number_format($value * 100, 2) . '%' : $value;
                        break;
                    case 'date':
                        if ($value instanceof \Carbon\Carbon) {
                            $value = $value->format('d/m/Y');
                        } elseif (is_string($value) && strtotime($value)) {
                            $value = date('d/m/Y', strtotime($value));
                        }
                        break;
                    case 'datetime':
                        if ($value instanceof \Carbon\Carbon) {
                            $value = $value->format('d/m/Y H:i:s');
                        } elseif (is_string($value) && strtotime($value)) {
                            $value = date('d/m/Y H:i:s', strtotime($value));
                        }
                        break;
                    case 'boolean':
                        $value = $value ? 'Sí' : 'No';
                        break;
                    case 'number':
                        $value = is_numeric($value) ? number_format($value) : $value;
                        break;
                }
            }
          @endphp
          {{ $value }}
        </td>
      @endforeach
    </tr>
  @endforeach
  </tbody>
</table>

<div class="footer">
  <p>Total de registros: {{ $data->count() }}</p>
</div>
</body>
</html>
