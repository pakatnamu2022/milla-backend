<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle de Gastos de Viaje</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 20px;
    }

    .header {
      text-align: left;
      margin-bottom: 5px;
    }

    .company-name {
      font-weight: bold;
      font-size: 10px;
      margin-bottom: 3px;
    }

    .title {
      background-color: #e0e0e0;
      padding: 8px;
      font-size: 13px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 10px;
      border: 1px solid #000;
    }

    .info-section {
      margin-bottom: 10px;
      font-size: 9px;
    }

    .info-row {
      margin-bottom: 3px;
      display: flex;
    }

    .info-label {
      font-weight: bold;
      width: 100px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 9px;
    }

    table, th, td {
      border: 1px solid #000;
    }

    td, th {
      padding: 4px 6px;
      vertical-align: top;
    }

    th {
      background-color: #e0e0e0;
      font-weight: bold;
      text-align: center;
      font-size: 8px;
    }

    .category-header {
      background-color: #d0d0d0;
      font-weight: bold;
      padding: 4px;
      text-align: left;
      font-size: 9px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .total-row {
      font-weight: bold;
      background-color: #f0f0f0;
    }

    .footer-section {
      margin-top: 15px;
      font-size: 10px;
    }

    .footer-row {
      display: flex;
      justify-content: space-between;
      padding: 5px 0;
      border-bottom: 1px solid #000;
    }

    .footer-label {
      font-weight: bold;
    }

    .footer-value {
      font-weight: bold;
      text-align: right;
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="company-name">Empresa: {{ $request['company']['name'] ?? 'GRUPO PAKATNAMU SAC' }}</div>
</div>

<!-- Title -->
<div class="title">
  DETALLE DE GASTOS DE VIAJE
</div>

<!-- Info Section -->
<div class="info-section">
  <div class="info-row">
    <span class="info-label">Nombre:</span>
    <span>{{ $request['employee']['name'] ?? '' }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Motivo del Viaje:</span>
    <span>{{ $request['purpose'] ?? '' }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Destino:</span>
    <span>{{ $request['district']['name'] ?? '' }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Periodo:</span>
    <span>{{ date('d/m/Y', strtotime($request['start_date'])) }} - {{ date('d/m/Y', strtotime($request['end_date'])) }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Moneda:</span>
    <span>Soles</span>
  </div>
</div>

<!-- Expenses Table -->
<table>
  <thead>
  <tr>
    <th style="width: 12%;">FECHA</th>
    <th style="width: 15%;">N° COMPROBANTE</th>
    <th style="width: 28%;">RAZÓN SOCIAL</th>
    <th style="width: 30%;">DETALLE</th>
    <th style="width: 15%;">MONTO</th>
  </tr>
  </thead>
  <tbody>

  @if(count($alimentacion) > 0)
    <!-- Alimentación Category -->
    <tr>
      <td colspan="5" class="category-header">ALIMENTACIÓN</td>
    </tr>
    @foreach($alimentacion as $expense)
      <tr>
        <td class="text-center">{{ date('d/m/Y', strtotime($expense['expense_date'])) }}</td>
        <td class="text-center">{{ $expense['receipt_number'] ?? 'S/N' }}</td>
        <td>{{ $expense['supplier_name'] ?? '-' }}</td>
        <td>{{ $expense['description'] ?? '-' }}</td>
        <td class="text-right">S/ {{ number_format($expense['company_amount'], 2) }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="4" class="text-right">TOTAL ALIMENTACIÓN:</td>
      <td class="text-right">S/ {{ number_format($totalAlimentacion, 2) }}</td>
    </tr>
  @endif

  @if(count($hospedaje) > 0)
    <!-- Hospedaje Category -->
    <tr>
      <td colspan="5" class="category-header">HOSPEDAJE</td>
    </tr>
    @foreach($hospedaje as $expense)
      <tr>
        <td class="text-center">{{ date('d/m/Y', strtotime($expense['expense_date'])) }}</td>
        <td class="text-center">{{ $expense['receipt_number'] ?? 'S/N' }}</td>
        <td>{{ $expense['supplier_name'] ?? '-' }}</td>
        <td>{{ $expense['description'] ?? '-' }}</td>
        <td class="text-right">S/ {{ number_format($expense['company_amount'], 2) }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="4" class="text-right">TOTAL HOSPEDAJE:</td>
      <td class="text-right">S/ {{ number_format($totalHospedaje, 2) }}</td>
    </tr>
  @endif

  @if(count($movilidad) > 0)
    <!-- Movilidad Category -->
    <tr>
      <td colspan="5" class="category-header">MOVILIDAD</td>
    </tr>
    @foreach($movilidad as $expense)
      <tr>
        <td class="text-center">{{ date('d/m/Y', strtotime($expense['expense_date'])) }}</td>
        <td class="text-center">{{ $expense['receipt_number'] ?? 'S/N' }}</td>
        <td>{{ $expense['supplier_name'] ?? '-' }}</td>
        <td>{{ $expense['description'] ?? '-' }}</td>
        <td class="text-right">S/ {{ number_format($expense['company_amount'], 2) }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="4" class="text-right">TOTAL MOVILIDAD:</td>
      <td class="text-right">S/ {{ number_format($totalMovilidad, 2) }}</td>
    </tr>
  @endif

  @if(count($otros) > 0)
    <!-- Otros Category -->
    <tr>
      <td colspan="5" class="category-header">OTROS</td>
    </tr>
    @foreach($otros as $expense)
      <tr>
        <td class="text-center">{{ date('d/m/Y', strtotime($expense['expense_date'])) }}</td>
        <td class="text-center">{{ $expense['receipt_number'] ?? 'S/N' }}</td>
        <td>{{ $expense['supplier_name'] ?? '-' }}</td>
        <td>{{ $expense['description'] ?? '-' }}</td>
        <td class="text-right">S/ {{ number_format($expense['company_amount'], 2) }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="4" class="text-right">TOTAL OTROS:</td>
      <td class="text-right">S/ {{ number_format($totalOtros, 2) }}</td>
    </tr>
  @endif

  @if(count($sinComprobante) > 0)
    <!-- Gastos sin Comprobante Category -->
    <tr>
      <td colspan="5" class="category-header">GASTOS SIN COMPROBANTE</td>
    </tr>
    @foreach($sinComprobante as $expense)
      <tr>
        <td class="text-center">{{ date('d/m/Y', strtotime($expense['expense_date'])) }}</td>
        <td class="text-center">S/C</td>
        <td>{{ $expense['supplier_name'] ?? '-' }}</td>
        <td>{{ $expense['description'] ?? '-' }}</td>
        <td class="text-right">S/ {{ number_format($expense['company_amount'], 2) }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="4" class="text-right">TOTAL SIN COMPROBANTE:</td>
      <td class="text-right">S/ {{ number_format($totalSinComprobante, 2) }}</td>
    </tr>
  @endif

  <!-- Total General -->
  <tr class="total-row">
    <td colspan="4" class="text-right" style="font-size: 10px;">TOTAL:</td>
    <td class="text-right" style="font-size: 10px;">S/ {{ number_format($totalGeneral, 2) }}</td>
  </tr>

  </tbody>
</table>

<!-- Footer Section -->
<div class="footer-section">
  <div class="footer-row">
    <span class="footer-label">IMPORTE OTORGADO PARA VIÁTICOS - CAJA</span>
    <span class="footer-value">S/ {{ number_format($importeOtorgado, 2) }}</span>
  </div>
  <div class="footer-row">
    <span class="footer-label">TOTAL GENERAL DE GASTOS</span>
    <span class="footer-value">S/ {{ number_format($totalGeneral, 2) }}</span>
  </div>
  <div class="footer-row">
    <span class="footer-label">MONTO A DEVOLVER Y/O REEMBOLSO DE GASTOS</span>
    <span class="footer-value">
      @if($montoDevolver > 0)
        S/ {{ number_format($montoDevolver, 2) }} (A DEVOLVER)
      @elseif($montoDevolver < 0)
        S/ {{ number_format(abs($montoDevolver), 2) }} (A REEMBOLSAR)
      @else
        S/ 0.00
      @endif
    </span>
  </div>
</div>

</body>
</html>