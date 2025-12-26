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
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gastos Aprobados</title>
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
      padding: 8px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 15px;
      border: 1px solid #000;
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
      padding: 5px 8px;
      vertical-align: top;
    }

    th {
      background-color: #e0e0e0;
      font-weight: bold;
      text-align: center;
    }

    .label {
      font-weight: bold;
      font-size: 9px;
    }

    .section-title {
      background-color: #d0d0d0;
      font-weight: bold;
      padding: 6px;
      text-align: center;
      font-size: 11px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .page-break {
      page-break-after: always;
    }

    .attachment-container {
      margin-top: 20px;
      text-align: center;
    }

    .attachment-image {
      max-width: 100%;
      max-height: 600px;
      margin: 10px auto;
      display: block;
    }

    .expense-detail {
      margin-bottom: 10px;
      padding: 8px;
      background-color: #f5f5f5;
      border: 1px solid #ccc;
    }
  </style>
</head>
<body>

<!-- Primera página: Resumen -->
<div class="title">GASTOS APROBADOS - VIÁTICOS</div>

<!-- Datos Generales -->
<table>
  <tr>
    <th colspan="6" class="section-title">DATOS GENERALES</th>
  </tr>
  <tr>
    <td class="label" style="width: 20%;">Código:</td>
    <td>{{ $request['code'] ?? '' }}</td>
    <td class="label" style="width: 20%;">Estado:</td>
    <td colspan="3">
      @if($request['status'] === 'approved')
        <span style="color: green; font-weight: bold;">APROBADO</span>
      @else
        {{ strtoupper($request['status']) }}
      @endif
    </td>
  </tr>
  <tr>
    <td class="label">Nombre del empleado:</td>
    <td colspan="3">{{ $request['employee']['full_name'] ?? '' }}</td>
    <td class="label" style="width: 15%;">Área:</td>
    <td>{{ $request['employee']['position']['area']['name'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Cargo del empleado:</td>
    <td colspan="5">{{ $request['employee']['position']['name'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Empresa de servicio:</td>
    <td colspan="5">{{ $request['company_service']['name'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Lugar de destino:</td>
    <td colspan="3">{{ $request['district']['name'] ?? '' }}</td>
    <td class="label">Zona:</td>
    <td>{{ ($request['district']['zone'] ?? 'Nacional') }}</td>
  </tr>
  <tr>
    <td class="label">Objetivo del viaje:</td>
    <td colspan="5">{{ $request['purpose'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Fecha de inicio:</td>
    <td>{{ \Carbon\Carbon::parse($request['start_date'])->format('d/m/Y') }}</td>
    <td class="label" style="width: 15%;">Fecha fin:</td>
    <td>{{ \Carbon\Carbon::parse($request['end_date'])->format('d/m/Y') }}</td>
    <td class="label">Moneda:</td>
    <td>S/. - Nuevos Soles</td>
  </tr>
  <tr>
    <td class="label">Importe presupuestado:</td>
    <td colspan="5">S/. {{ number_format($request['total_budget'] ?? 0, 2) }}</td>
  </tr>
</table>

<!-- Tabla de Gastos Aprobados -->
<table>
  <tr>
    <th colspan="6" class="section-title">GASTOS APROBADOS</th>
  </tr>
  <tr>
    <th style="width: 10%;">Fecha</th>
    <th style="width: 25%;">Tipo de Gasto</th>
    <th style="width: 15%;">Monto Recibo</th>
    <th style="width: 15%;">Monto Empresa</th>
    <th style="width: 15%;">Tipo Recibo</th>
    <th style="width: 20%;">Observaciones</th>
  </tr>

  @if($approvedExpenses->count() > 0)
    @foreach($approvedExpenses as $expense)
      <tr>
        <td class="text-center">{{ \Carbon\Carbon::parse($expense['expense_date'])->format('d/m/Y') }}</td>
        <td>{{ $expense['expense_type']['name'] ?? 'N/A' }}</td>
        <td class="text-right">S/. {{ number_format($expense['receipt_amount'] ?? 0, 2) }}</td>
        <td class="text-right">S/. {{ number_format($expense['company_amount'] ?? 0, 2) }}</td>
        <td class="text-center">
          @if($expense['receipt_type'] === 'no_receipt')
            Sin comprobante
          @elseif($expense['receipt_type'] === 'invoice')
            Factura
          @elseif($expense['receipt_type'] === 'receipt')
            Boleta
          @else
            {{ $expense['receipt_type'] }}
          @endif
        </td>
        <td>{{ $expense['notes'] ?? '' }}</td>
      </tr>
    @endforeach
  @else
    <tr>
      <td colspan="6" class="text-center">No hay gastos aprobados</td>
    </tr>
  @endif

  <tr>
    <td colspan="3" class="label text-right">TOTAL DE GASTOS APROBADOS:</td>
    <td class="text-right label">S/. {{ number_format($totalApproved, 2) }}</td>
    <td colspan="2"></td>
  </tr>
</table>

<!-- Resumen -->
<table>
  <tr>
    <th colspan="2" class="section-title">RESUMEN</th>
  </tr>
  <tr>
    <td class="label" style="width: 70%;">Importe presupuestado:</td>
    <td class="text-right">S/. {{ number_format($request['total_budget'] ?? 0, 2) }}</td>
  </tr>
  <tr>
    <td class="label">Total de gastos aprobados:</td>
    <td class="text-right">S/. {{ number_format($totalApproved, 2) }}</td>
  </tr>
  @php
    $balance = ($request['total_budget'] ?? 0) - $totalApproved;
  @endphp
  <tr>
    <td class="label">Saldo {{ $balance >= 0 ? '(a favor de la empresa)' : '(a favor del empleado)' }}:</td>
    <td class="text-right label">S/. {{ number_format(abs($balance), 2) }}</td>
  </tr>
</table>

<!-- Páginas siguientes: Archivos adjuntos de cada gasto -->
@if($expensesWithAttachments->count() > 0)
  @foreach($expensesWithAttachments as $index => $expense)
    <div class="page-break"></div>

    <div class="title">COMPROBANTE DE GASTO {{ $index + 1 }}</div>

    <div class="expense-detail">
      <strong>Tipo de Gasto:</strong> {{ $expense['expense_type']['name'] ?? 'N/A' }}<br>
      <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($expense['expense_date'])->format('d/m/Y') }}<br>
      <strong>Monto Recibo:</strong> S/. {{ number_format($expense['receipt_amount'] ?? 0, 2) }}<br>
      <strong>Monto Empresa:</strong> S/. {{ number_format($expense['company_amount'] ?? 0, 2) }}<br>
      @if(!empty($expense['receipt_number']))
        <strong>Número de Recibo:</strong> {{ $expense['receipt_number'] }}<br>
      @endif
      @if(!empty($expense['notes']))
        <strong>Observaciones:</strong> {{ $expense['notes'] }}<br>
      @endif
    </div>

    @if(!empty($expense['receipt_path']))
      <div class="attachment-container">
        @php
          $imageSrc = getBase64Image($expense['receipt_path']);
        @endphp
        @if($imageSrc)
          <img src="{{ $imageSrc }}" class="attachment-image" alt="Comprobante">
        @else
          <p>No se pudo cargar la imagen del comprobante</p>
          <p style="font-size: 9px;">Ruta: {{ $expense['receipt_path'] }}</p>
        @endif
      </div>
    @endif
  @endforeach
@endif

</body>
</html>
