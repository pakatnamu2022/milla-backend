<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Metas {{ $period['month_name'] }} {{ $period['year'] }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    @page {
      margin: 10mm 8mm;
    }

    body {
      font-family: 'Arial', sans-serif;
      font-size: 9px;
      line-height: 1.2;
      color: #2d3748;
      background-color: #ffffff;
      padding: 8px;
      border: 1px solid #2563eb;
      border-radius: 4px;
      min-height: calc(100vh - 20px);
    }

    .document-container {
      max-width: 100%;
      margin: 0 auto;
      background: #ffffff;
      padding: 12px;
      border-radius: 4px;
      position: relative;
    }

    .document-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, #3b82f6, #1e40af);
      border-radius: 4px 4px 0 0;
    }

    .header {
      text-align: center;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 1px solid #e2e8f0;
      position: relative;
    }

    .header h1 {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 4px;
      color: #1e40af;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .print-date {
      position: absolute;
      top: 0;
      right: 0;
      font-size: 7px;
      color: #6b7280;
      font-style: italic;
    }

    .footer {
      margin-top: 15px;
      padding-top: 8px;
      border-top: 1px solid #e2e8f0;
      font-size: 6px;
      color: #6b7280;
      text-align: center;
      font-style: italic;
    }

    .company-info {
      margin-bottom: 10px;
      background-color: #f8fafc;
      padding: 8px;
      border-radius: 4px;
      border-left: 2px solid #3b82f6;
      font-size: 8px;
    }

    .company-info p {
      margin-bottom: 4px;
      line-height: 1.4;
      color: #334155;
    }

    .summary-section {
      text-align: center;
      margin: 10px 0;
    }

    .summary-table {
      width: 180px;
      margin: 0 auto;
      border-collapse: collapse;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      border-radius: 4px;
      overflow: hidden;
    }

    .summary-table th {
      background: linear-gradient(135deg, #1e40af, #3b82f6);
      color: white;
      padding: 6px 12px;
      text-align: center;
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .summary-table td {
      padding: 8px 12px;
      text-align: center;
      font-weight: 700;
      font-size: 12px;
      color: #1e40af;
      background-color: #ffffff;
    }

    .section-container {
      margin: 12px 0;
      background-color: #ffffff;
      border-radius: 4px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
    }

    .section-title {
      color: white;
      text-align: center;
      padding: 6px;
      font-weight: 700;
      font-size: 10px;
      text-transform: uppercase;
      margin: 0;
    }

    .section-subtitle {
      background-color: #f8fafc;
      padding: 4px 8px;
      margin: 0;
      font-size: 8px;
      font-weight: 600;
      color: #475569;
      border-bottom: 1px solid #e2e8f0;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      background-color: #ffffff;
    }

    .data-table th {
      background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
      color: #1e40af;
      padding: 4px 3px;
      text-align: center;
      font-size: 7px;
      font-weight: 700;
      text-transform: uppercase;
      border: 1px solid #cbd5e1;
    }

    .data-table th:first-child {
      text-align: left;
      width: 140px;
      background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
      padding-left: 6px;
    }

    .data-table td {
      padding: 3px 2px;
      text-align: center;
      border: 1px solid #e2e8f0;
      font-size: 7px;
      color: #374151;
    }

    .data-table td:first-child {
      text-align: left;
      font-weight: 600;
      background-color: #f8fafc;
      padding-left: 6px;
      color: #1f2937;
      font-size: 7px;
    }

    .data-table tbody tr:nth-child(even) {
      background-color: #f9fafb;
    }

    .data-table tr.total-row {
      background: linear-gradient(135deg, #ddd6fe, #c7d2fe) !important;
      font-weight: 700;
      border-top: 1px solid #3b82f6;
    }

    .data-table tr.total-row td {
      color: #1e40af;
      font-weight: 700;
    }

    .data-table tr.total-row td:first-child {
      background: linear-gradient(135deg, #c7d2fe, #a5b4fc);
      font-weight: 700;
    }

    .data-table tr.total-row td:last-child {
      background: linear-gradient(135deg, #a5b4fc, #8b5cf6);
      font-weight: 700;
    }

    .note {
      font-size: 6px;
      margin-top: 6px;
      padding: 6px;
      background-color: #fef3c7;
      border-left: 2px solid #f59e0b;
      border-radius: 0 2px 2px 0;
      line-height: 1.3;
      color: #92400e;
      font-style: italic;
    }

    .brand-header {
      min-width: 25px;
    }

    .total-header {
      background: linear-gradient(135deg, #a5b4fc, #8b5cf6) !important;
      font-weight: 700;
    }

    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 60px;
      color: rgba(59, 130, 246, 0.03);
      font-weight: 900;
      z-index: 0;
      pointer-events: none;
    }

    .content-wrapper {
      position: relative;
      z-index: 1;
    }
  </style>
</head>
<body>
<div class="document-container">
  <div class="watermark">PAKATNAMU</div>
  <div class="content-wrapper">

    <div class="header">
      <h1>Metas {{ $period['month_name'] }} {{ $period['year'] }}</h1>
    </div>

    <div class="company-info">
      <p><strong>Estimados señores AUTOMOTORES PAKATNAMU S.A.C.,</strong></p>
      <p>Sirva la presente para saludarlos cordialmente y a su vez comunicarles los objetivos del PDG Comercial para el
        presente mes:</p>
    </div>

    <!-- Tabla Resumen -->
    <div class="summary-section">
      <table class="summary-table">
        <thead>
        <tr>
          <th style="background: #0B2C83;">SELL IN</th>
          <th style="background: #F0494E;">SELL OUT</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td style="color: #000;">{{ $data['sell_in']['totals']['total'] ?? 0 }}</td>
          <td style="color: #000;">{{ $data['sell_out']['totals']['total'] ?? 0 }}</td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Sección SELL IN -->
    <div class="section-container">
      <div class="section-title" style="background: #0B2C83;">Meta Sell In Unidades</div>
      <div class="section-subtitle">
        <strong>1.- Objetivos Sell In expresado en unidades {{ $period['month_name'] }}</strong>
      </div>

      <table class="data-table">
        <thead>
        <tr>
          <th>Tiendas</th>
          @foreach($data['brands'] as $brand)
            <th class="brand-header">{{ $brand }}</th>
          @endforeach
          <th class="total-header">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data['sell_in']['rows'] as $row)
          <tr>
            <td>{{ $row['shop'] }}</td>
            @foreach($data['brands'] as $brand)
              <td>{{ $row[$brand] ?? 0 }}</td>
            @endforeach
            <td><strong>{{ $row['total'] }}</strong></td>
          </tr>
        @endforeach

        <!-- Filas vacías -->
        @for($i = 0; $i < 3; $i++)
          <tr>
            <td>-</td>
            @foreach($data['brands'] as $brand)
              <td></td>
            @endforeach
            <td></td>
          </tr>
        @endfor

        <!-- Fila de totales -->
        <tr class="total-row">
          <td>{{ $data['sell_in']['totals']['shop'] }}</td>
          @foreach($data['brands'] as $brand)
            <td>{{ $data['sell_in']['totals'][$brand] ?? 0 }}</td>
          @endforeach
          <td>{{ $data['sell_in']['totals']['total'] }}</td>
        </tr>
        </tbody>
      </table>

      <div class="note">
        (*) Para el cálculo de cumplimiento de cuota de Sell In se considera todas las operaciones debidamente
        Facturadas dentro del mes correspondiente.
      </div>
    </div>

    <!-- Sección SELL OUT -->
    <div class="section-container">
      <div class="section-title" style="background: #F0494E;">Meta Sell Out Unidades</div>
      <div class="section-subtitle">
        <strong>1.- Objetivos Sell Out expresado en unidades {{ $period['month_name'] }}</strong>
      </div>

      <table class="data-table">
        <thead>
        <tr>
          <th>Tiendas</th>
          @foreach($data['brands'] as $brand)
            <th class="brand-header">{{ $brand }}</th>
          @endforeach
          <th class="total-header">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data['sell_out']['rows'] as $row)
          <tr>
            <td>{{ $row['shop'] }}</td>
            @foreach($data['brands'] as $brand)
              <td>{{ $row[$brand] ?? 0 }}</td>
            @endforeach
            <td><strong>{{ $row['total'] }}</strong></td>
          </tr>
        @endforeach

        <!-- Filas vacías -->
        @for($i = 0; $i < 3; $i++)
          <tr>
            <td>-</td>
            @foreach($data['brands'] as $brand)
              <td></td>
            @endforeach
            <td></td>
          </tr>
        @endfor

        <!-- Fila de totales -->
        <tr class="total-row">
          <td>{{ $data['sell_out']['totals']['shop'] }}</td>
          @foreach($data['brands'] as $brand)
            <td>{{ $data['sell_out']['totals'][$brand] ?? 0 }}</td>
          @endforeach
          <td>{{ $data['sell_out']['totals']['total'] }}</td>
        </tr>
        </tbody>
      </table>

      <div class="note">
        (*) Para el cálculo de cumplimiento de cuota se considera todas las operaciones debidamente registradas en las
        plataformas correspondientes (Salesforce / Dealer Portal) con fecha de facturación dentro del mes de evaluación.
      </div>
    </div>

    <!-- Footer opcional -->
    <div class="footer">
      {{ date('d/m/Y') }} • AUTOMOTORES PAKATNAMU S.A.C.
    </div>

  </div>
</div>
</body>
</html>
