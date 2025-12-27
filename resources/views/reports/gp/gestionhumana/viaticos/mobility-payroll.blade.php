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

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    table, th, td {
      border: 1px solid #000;
    }

    td, th {
      padding: 6px 8px;
      vertical-align: top;
    }

    th {
      background-color: #e0e0e0;
      font-weight: bold;
      text-align: center;
      font-size: 9px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .total-row {
      background-color: #f5f5f5;
      font-weight: bold;
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
  </style>
</head>
<body>

<div class="title">
  PLANILLA DE GASTOS DE MOVILIDAD
</div>

<!-- Header Information -->
<div class="header-section">
  <table class="header-table">
    <tr>
      <td class="label">DOCUMENTO:</td>
      <td class="value">{{ $mobilityPayroll->serie }}-{{ $mobilityPayroll->correlative }}</td>
      <td class="label">PERÍODO:</td>
      <td class="value">{{ $mobilityPayroll->period }}</td>
    </tr>
    <tr>
      <td class="label">TRABAJADOR:</td>
      <td class="value" colspan="3">{{ $mobilityPayroll->worker->nombre_completo ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td class="label">DNI/RUC:</td>
      <td class="value">{{ $mobilityPayroll->num_doc }}</td>
      <td class="label">SEDE:</td>
      <td class="value">{{ $mobilityPayroll->sede->abreviatura ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td class="label">EMPRESA:</td>
      <td class="value" colspan="3">{{ $mobilityPayroll->company_name }}</td>
    </tr>
    @if($mobilityPayroll->address)
    <tr>
      <td class="label">DIRECCIÓN:</td>
      <td class="value" colspan="3">{{ $mobilityPayroll->address }}</td>
    </tr>
    @endif
  </table>
</div>

<!-- Expense Details Table -->
<table>
  <thead>
  <tr>
    <th style="width: 8%;">N°</th>
    <th style="width: 12%;">FECHA</th>
    <th style="width: 25%;">TIPO GASTO</th>
    <th style="width: 12%;">TIPO COMP.</th>
    <th style="width: 15%;">N° COMP.</th>
    <th style="width: 13%;">IMPORTE</th>
    <th style="width: 15%;">OBSERVACIÓN</th>
  </tr>
  </thead>
  <tbody>
  @php $counter = 1; @endphp
  @foreach($expenses as $expense)
    <tr>
      <td class="text-center">{{ $counter++ }}</td>
      <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}</td>
      <td>{{ $expense->expenseType->name ?? 'N/A' }}</td>
      <td class="text-center">
        @if($expense->receipt_type === 'invoice')
          Factura
        @elseif($expense->receipt_type === 'boleta')
          Boleta
        @elseif($expense->receipt_type === 'ticket')
          Ticket
        @elseif($expense->receipt_type === 'no_receipt')
          Sin Comp.
        @else
          {{ $expense->receipt_type }}
        @endif
      </td>
      <td class="text-center">{{ $expense->receipt_number ?? '-' }}</td>
      <td class="text-right">S/ {{ number_format($expense->receipt_amount, 2) }}</td>
      <td>{{ $expense->notes ?? '-' }}</td>
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

<!-- Footer -->
<div class="footer-section">
  <p>Total de gastos: {{ $expenses->count() }}</p>
  <p>Importe total: S/ {{ number_format($totalAmount, 2) }}</p>
</div>

<!-- Signature Section -->
<div class="signature-section">
  <br><br><br>
  <div class="signature-line">
    Firma del Trabajador
  </div>
</div>

</body>
</html>
