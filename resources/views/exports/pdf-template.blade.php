<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    @page {
      margin: 10mm 8mm; /* Márgenes mínimos */
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      margin: 0;
      padding: 0;
    }

    .header {
      text-align: center;
      margin-bottom: 15px;
      border-bottom: 2px solid #00227d;
      padding-bottom: 8px;
    }

    .header h1 {
      margin: 0 0 5px 0;
      font-size: 18px;
      color: #00227d;
    }

    .header p {
      margin: 0;
      font-size: 10px;
      color: #666;
    }

    .summary {
      background-color: #f8f9fa;
      padding: 10px;
      margin-bottom: 15px;
      border-left: 4px solid #00227d;
      border-radius: 3px;
    }

    .summary h3 {
      margin: 0 0 8px 0;
      font-size: 12px;
      color: #00227d;
    }

    .summary-grid {
      display: table;
      width: 100%;
    }

    .summary-item {
      display: table-cell;
      width: 25%;
      padding: 2px 8px 2px 0;
      vertical-align: top;
      font-size: 9px;
    }

    .summary-item strong {
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 9px;
    }

    th {
      background-color: #f1f5f9;
      color: #64748B;
      padding: 8px 4px;
      text-align: left;
      font-weight: bold;
      font-size: 9px;
      border-bottom: 1px solid #cbd5e1;
    }

    td {
      padding: 6px 4px;
      border-bottom: 1px solid #e2e8f0;
      font-size: 8px;
      line-height: 1.2;
    }

    tr:nth-child(even) {
      background-color: #f8fafc;
    }

    .numeric {
      text-align: right;
    }

    .center {
      text-align: center;
    }

    .footer {
      margin-top: 10px;
      padding-top: 5px;
      border-top: 1px solid #dee2e6;
      text-align: center;
      color: #666;
      font-size: 8px;
    }

    /* Ajustes para columnas específicas */
    .col-id {
      width: 5%;
    }

    .col-name {
      width: 25%;
    }

    .col-date {
      width: 10%;
    }

    .col-status {
      width: 12%;
    }

    .col-percentage {
      width: 8%;
    }

    .col-boolean {
      width: 8%;
      text-align: center;
    }

    /* Evitar quiebres de página en lugares inapropiados */
    .summary {
      page-break-inside: avoid;
    }

    table {
      page-break-inside: auto;
    }

    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    thead {
      display: table-header-group;
    }

    tfoot {
      display: table-footer-group;
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
    <div class="summary-grid">
      @foreach($summary as $key => $value)
        <div class="summary-item">
          <strong>{{ $key }}:</strong> {{ $value }}
        </div>
      @endforeach
    </div>
  </div>
@endif

<table>
  <thead>
  <tr>
    @foreach($columns as $key => $column)
      <th class="{{ $getColumnClass($key) }}">
        {{ is_array($column) ? $column['label'] : $column }}
      </th>
    @endforeach
  </tr>
  </thead>
  <tbody>
  @foreach($data as $row)
    <tr>
      @foreach($columns as $key => $column)
        <td class="{{ $getColumnClass($key) }} {{ is_numeric(data_get($row, $key)) ? 'numeric' : '' }}">
          @php
            $value = is_array($row) ? ($row[$key] ?? '') : data_get($row, $key, '');

            if (is_array($column) && isset($column['formatter'])) {
                switch($column['formatter']) {
                    case 'currency':
                        $value = is_numeric($value) ?  number_format($value, 2) : $value;
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
                            $value = $value->format('d/m/Y H:i');
                        } elseif (is_string($value) && strtotime($value)) {
                            $value = date('d/m/Y H:i', strtotime($value));
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

            // Truncar texto muy largo
            if (is_string($value) && strlen($value) > 50) {
                $value = substr($value, 0, 47) . '...';
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
  <p>Total de registros: {{ $data->count() }} | Página 1</p>
</div>
</body>
</html>
