<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Planilla de Movilidad</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 30px;
    }

    .title {
      background-color: #e0e0e0;
      padding: 10px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 20px;
      border: 1px solid #000;
    }

    .header-section {
      margin-bottom: 20px;
    }

    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    .header-table td {
      border: 1px solid #000;
      padding: 5px 8px;
      font-size: 9px;
    }

    .header-table .label {
      font-weight: bold;
      background-color: #f0f0f0;
      width: 20%;
    }

    .header-table .value {
      width: 30%;
    }

    table.detail-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    table.detail-table, table.detail-table th, table.detail-table td {
      border: 1px solid #000;
    }

    table.detail-table td, table.detail-table th {
      padding: 4px 6px;
      vertical-align: middle;
    }

    table.detail-table th {
      background-color: #e0e0e0;
      font-weight: bold;
      text-align: center;
      font-size: 8px;
    }

    .group-header {
      font-size: 9px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .subtotal-row {
      background-color: #f0f0f0;
      font-weight: bold;
      font-size: 9px;
    }

    .total-row {
      background-color: #e0e0e0;
      font-weight: bold;
      font-size: 10px;
    }

    .footer-section {
      margin-top: 30px;
      text-align: right;
      font-weight: bold;
      font-size: 11px;
    }

    .signature-section {
      margin-top: 50px;
      text-align: center;
    }

    .signature-line {
      border-top: 1px solid #000;
      width: 250px;
      margin: 0 auto;
      padding-top: 5px;
    }

    .note {
      font-size: 8px;
      margin-top: 10px;
      font-style: italic;
    }
  </style>
</head>
<body>

<div class="title">
  PLANILLA POR GASTOS DE MOVILIDAD - POR TRABAJADOR
</div>

<!-- Header Information -->
<div class="header-section">
  <table class="header-table">
    <tr>
      <td class="label">DOCUMENTO:</td>
      <td class="value">{{ $mobilityPayroll->serie }}-{{ $mobilityPayroll->correlative }}</td>
      <td class="label">PERIODO:</td>
      <td class="value">{{ $mobilityPayroll->period }}</td>
    </tr>
    <tr>
      <td class="label">FECHA DE EMISION:</td>
      <td
        class="value">{{ $mobilityPayroll->created_at ? \Carbon\Carbon::parse($mobilityPayroll->created_at)->format('d/m/Y') : now()->format('d/m/Y') }}</td>
      <td class="label"></td>
      <td class="value"></td>
    </tr>
    <tr>
      <td class="label">EMPRESA:</td>
      <td class="value">{{ $mobilityPayroll->company_name }}</td>
      <td class="label">SEDE:</td>
      <td class="value">{{ $mobilityPayroll->sede->abreviatura ?? 'N/A' }}</td>
    </tr>
    @if($mobilityPayroll->address)
      <tr>
        <td class="label">DIRECCION:</td>
        <td class="value" colspan="3">{{ $mobilityPayroll->address }}</td>
      </tr>
    @endif
  </table>

  <table class="header-table">
    <tr>
      <td class="label">TRABAJADOR:</td>
      <td class="value">{{ $mobilityPayroll->worker->nombre_completo ?? 'N/A' }}</td>
      <td class="label">DNI:</td>
      <td class="value">{{ $mobilityPayroll->worker->vat }}</td>
    </tr>
  </table>
</div>

<!-- Expense Details Table -->
<table class="detail-table">
  <thead>
  <tr>
    <th colspan="3" class="group-header">Fecha de Gasto (**)</th>
    <th colspan="2" class="group-header">Desplazamiento (**)</th>
    <th colspan="2" class="group-header">Montos gastados por (**)</th>
  </tr>
  <tr>
    <th style="width: 8%;">Dia</th>
    <th style="width: 8%;">Mes</th>
    <th style="width: 8%;">Año</th>
    <th style="width: 25%;">Motivo</th>
    <th style="width: 21%;">Destino</th>
    <th style="width: 15%;">Importe</th>
    <th style="width: 15%;">Firma</th>
  </tr>
  </thead>
  <tbody>
  @foreach($groupedExpenses as $date => $dateExpenses)
    @foreach($dateExpenses as $expense)
      <tr>
        <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d') }}</td>
        <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('m') }}</td>
        <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y') }}</td>
        <td>{{ $expense->expenseType->name ?? 'N/A' }}</td>
        <td>{{ $expense->notes ?? '-' }}</td>
        <td class="text-right">S/ {{ number_format($expense->receipt_amount, 2) }}</td>
        <td></td>
      </tr>
    @endforeach
    <!-- Subtotal per date -->
    <tr class="subtotal-row">
      <td colspan="5" class="text-right">Subtotal {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}:</td>
      <td class="text-right">S/ {{ number_format($dateExpenses->sum('receipt_amount'), 2) }}</td>
      <td></td>
    </tr>
  @endforeach

  <!-- Total Row -->
  <tr class="total-row">
    <td colspan="5" class="text-right">TOTAL:</td>
    <td class="text-right">S/ {{ number_format($totalAmount, 2) }}</td>
    <td></td>
  </tr>
  </tbody>
</table>

<p class="note">(**) Datos obligatorios</p>

<!-- Footer -->
<div class="footer-section">
  <p>Total de gastos: {{ $expenses->count() }}</p>
  <p>Importe total: S/ {{ number_format($totalAmount, 2) }}</p>
</div>

</body>
</html>
