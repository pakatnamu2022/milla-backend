@php
  function getBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) {
      return '';
    }
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }

  $correlativeFormatted = str_pad($mobilityPayroll->correlative, 6, '0', STR_PAD_LEFT);
  $emissionDate = $mobilityPayroll->created_at ? \Carbon\Carbon::parse($mobilityPayroll->created_at)->format('d/m/Y') : now()->format('d/m/Y');
  $emissionTime = $mobilityPayroll->created_at ? \Carbon\Carbon::parse($mobilityPayroll->created_at)->format('H:i') : now()->format('H:i');

  // Format period in Spanish (e.g., "Enero del 2026")
  $periodFormatted = $mobilityPayroll->period;
  if (preg_match('/^(\d{2})\/\s*(\d{4})$/', trim($mobilityPayroll->period), $matches)) {
    $monthNumber = (int)$matches[1];
    $year = $matches[2];
    $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    if ($monthNumber >= 1 && $monthNumber <= 12) {
      $periodFormatted = $months[$monthNumber - 1] . ' del ' . $year;
    }
  }

  // Calculate total rows needed to fill the table (minimum 15 rows for appearance)
  $totalExpenseRows = $expenses->count();
  $minRows = 15;
  $emptyRows = max(0, $minRows - $totalExpenseRows);
@endphp
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
      padding: 20px 25px;
      color: #1a1a6e;
    }

    .main-container {
      border: 2px solid #1a1a6e;
      padding: 15px 20px 10px 20px;
      position: relative;
    }

    /* Title */
    .title {
      font-size: 13px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 10px;
      color: #1a1a6e;
    }

    /* Title table with document number */
    .title-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 0;
    }

    .title-table td {
      border: 1px solid #8888bb;
      padding: 6px 10px;
      font-size: 13px;
      font-weight: bold;
    }

    .title-cell {
      text-align: center;
      width: 70%;
      color: #1a1a6e;
    }

    .doc-number-cell {
      text-align: center;
      width: 30%;
    }

    .doc-number-red {
      color: #cc0000;
      font-size: 14px;
      font-weight: bold;
    }

    .doc-suffix-blue {
      color: #1a1a6e;
      font-size: 10px;
      font-weight: bold;
    }

    /* Wrapper section with vertical borders */
    .content-wrapper {
      border-left: 1px solid #8888bb;
      border-right: 1px solid #8888bb;
      padding: 0;
    }

    /* Inner content padding (except detail table) */
    .inner-content {
      padding: 8px;
    }

    /* Header layout using table */
    .header-layout {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
      margin-top: 5px;
    }

    .header-layout td {
      vertical-align: top;
      padding: 0;
    }

    .header-left {
      width: 55%;
    }

    .header-right {
      width: 45%;
      text-align: right;
    }

    /* Company info */
    .company-info-table {
      width: 90%;
      border-collapse: collapse;
    }

    .company-info-table td {
      padding: 2px 5px;
      font-size: 9px;
      border: 1px solid #8888bb;
    }

    .company-info-table .info-label {
      font-weight: bold;
      width: 25%;
      color: #1a1a6e;
    }

    .company-info-table .info-value {
      width: 75%;
      font-size: 8px;
    }

    /* Emission info and logo */
    .emission-logo-table {
      width: 100%;
      border-collapse: collapse;
    }

    .emission-logo-table td {
      padding: 2px 5px;
      font-size: 9px;
      vertical-align: middle;
    }

    .emission-label {
      font-size: 8px;
      color: #1a1a6e;
    }

    .emission-value {
      border: 1px solid #8888bb;
      padding: 2px 5px;
      min-width: 60px;
      font-size: 8px;
    }

    .logo-cell {
      text-align: center;
      vertical-align: middle;
      padding: 5px;
    }

    .logo-cell img {
      max-width: 120px;
      height: auto;
    }

    .logo-text {
      font-size: 11px;
      font-weight: bold;
      color: #1a1a6e;
      text-align: center;
      line-height: 1.2;
    }

    /* Period */
    .period-row {
      margin-bottom: 8px;
    }

    .period-table {
      width: 40%;
      border-collapse: collapse;
    }

    .period-table td {
      padding: 3px 5px;
      font-size: 9px;
    }

    .period-label {
      font-weight: bold;
      color: #1a1a6e;
    }

    .period-value {
      border: 1px solid #8888bb;
      min-width: 80px;
      padding: 3px 8px;
    }

    /* Worker info */
    .worker-section {
      margin-bottom: 8px;
      border-top: 1px solid #8888bb;
      padding: 5px 0;
    }

    .worker-section-title {
      font-weight: bold;
      font-size: 9px;
      color: #1a1a6e;
      margin-bottom: 2px;
    }

    .worker-table {
      width: 100%;
      border-collapse: collapse;
    }

    .worker-table td {
      padding: 2px 5px;
      font-size: 9px;
    }

    .worker-label {
      font-weight: bold;
      color: #1a1a6e;
    }

    .worker-value {
      border: 1px solid #8888bb;
      min-width: 150px;
      padding: 2px 8px;
    }

    /* Detail table */
    table.detail-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 0;
      border-left: none;
      border-right: none;
    }

    table.detail-table th,
    table.detail-table td {
      border: 1px solid #8888bb;
      padding: 3px 4px;
      vertical-align: middle;
      font-size: 8px;
    }

    /* Remove left border from first column */
    table.detail-table th:first-child,
    table.detail-table td:first-child {
      border-left: none;
    }

    /* Remove right border from last column */
    table.detail-table th:last-child,
    table.detail-table td:last-child {
      border-right: none;
    }

    table.detail-table th {
      background-color: #c8c8e8;
      font-weight: bold;
      text-align: center;
      color: #1a1a6e;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    /* Total row */
    .total-row td {
      font-weight: bold;
      font-size: 9px;
      padding: 4px 6px;
    }

    /* Base Legal footer */
    .base-legal {
      font-size: 7px;
      color: #1a1a6e;
      margin-top: 8px;
      padding: 4px 5px;
      background-color: #e8e8f0;
      border: 1px solid #8888bb;
    }
  </style>
</head>
<body>

<div class="main-container">

  <!-- Title and Document Number Table -->
  <table class="title-table">
    <tr>
      <td class="title-cell">PLANILLA POR GASTO DE MOVILIDAD - POR TRABAJADOR</td>
      <td class="doc-number-cell">
        <span class="doc-number-red">N.° {{ $correlativeFormatted }}</span>
        <span class="doc-suffix-blue"> - APCHI</span>
      </td>
    </tr>
  </table>

  <!-- Content Wrapper with vertical borders -->
  <div class="content-wrapper">
    <div class="inner-content">
      <!-- Header: Company info left, Emission + Logo right -->
      <table class="header-layout">
        <tr>
          <td class="header-left">
            <table class="company-info-table">
              <tr>
                <td class="info-label">Razón Social</td>
                <td class="info-value">{{ $mobilityPayroll->company_name }}</td>
              </tr>
              <tr>
                <td class="info-label">RUC</td>
                <td class="info-value">{{ $mobilityPayroll->num_doc }}</td>
              </tr>
              <tr>
                <td class="info-label">Dirección</td>
                <td class="info-value">{{ $mobilityPayroll->address }}</td>
              </tr>
            </table>
          </td>
          <td class="header-right">
            <table class="emission-logo-table">
              <tr>
                <td>
                  <span class="emission-label">Fecha de Emisión</span><br>
                  <span class="emission-value">{{ $emissionDate }}</span>
                </td>
                <td rowspan="2" class="logo-cell">
                  <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="AP Logo"><br>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="emission-label">Hora de Emisión</span><br>
                  <span class="emission-value">{{ $emissionTime }}</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- Period -->
      <div class="period-row">
        <table class="period-table">
          <tr>
            <td class="period-label">PERIODO</td>
            <td class="period-value">{{ $periodFormatted }}</td>
          </tr>
        </table>
      </div>

      <!-- Worker Data -->
      <div class="worker-section">
        <div class="worker-section-title" style="width: 70%; margin-left: auto; margin-right: auto;">Datos del
          Trabajador
        </div>
        <table class="worker-table" style="width: 70%; margin-left: auto; margin-right: auto;">
          <tr>
            <td class="worker-label" style="width: 25%; white-space: nowrap;">Nombres y Apellidos</td>
            <td class="worker-value"
                style="width: 75%; text-align: left;">{{ $mobilityPayroll->worker->nombre_completo ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="worker-label" style="width: 25%;">D.N.I.</td>
            <td class="worker-value"
                style="width: 75%;">{{ $mobilityPayroll->worker->vat ?? '' }}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Expense Details Table -->
    <table class="detail-table">
      <thead>
      <tr>
        <th colspan="3">Fecha del Gasto (**)</th>
        <th colspan="2">Desplazamiento (**)</th>
        <th colspan="3">Montos gastados por (**):</th>
      </tr>
      <tr>
        <th style="width: 6%;">Día</th>
        <th style="width: 6%;">Mes</th>
        <th style="width: 6%;">Año</th>
        <th style="width: 15%;">Motivo</th>
        <th style="width: 20%;">Destino</th>
        <th style="width: 9%;">Viaje</th>
        <th style="width: 9%;">día</th>
        <th style="width: 8%;">Firma</th>
      </tr>
      </thead>
      <tbody>
      @foreach($expenses as $expense)
        <tr>
          <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d') }}</td>
          <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('m') }}</td>
          <td class="text-center">{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y') }}</td>
          <td>{{ $expense->description ?? $expense->expenseType->name ?? '' }}</td>
          <td>{{ $expense->notes ?? '' }}</td>
          <td class="text-right">{{ number_format($expense->receipt_amount, 2) }}</td>
          <td class="text-right"></td>
          <td></td>
        </tr>
      @endforeach

      <!-- Total Row -->
      <tr class="total-row">
        <td colspan="5" class="text-center">Total</td>
        <td class="text-right" colspan="2">S/ {{ number_format($totalAmount, 2) }}</td>
        <td></td>
      </tr>
      </tbody>
    </table>
  </div>

</div>

<!-- Base Legal -->
<div class="base-legal">
  Base Legal: Inciso a1) del artículo 37° del TUO de la Ley de Impuestos a la Renta e Inciso V) del artículo 21° del
  Reglamento de la Ley del Impuesto a la Renta.
</div>

</body>
</html>
